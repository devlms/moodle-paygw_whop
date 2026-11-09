<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace paygw_whop\api;

/**
 * Whop API: create a checkout, read a payment, check a webhook signature (Standard Webhooks).
 *
 * Every call goes through Moodle's curl, so the site's proxy and blocked hosts apply and PHPUnit can mock the
 * responses.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /** @var string Live API. */
    public const LIVE_URL = 'https://api.whop.com/api/v1';
    /** @var string Sandbox API. */
    public const SANDBOX_URL = 'https://sandbox-api.whop.com/api/v1';
    /** @var int Seconds a webhook timestamp may be off. */
    public const TOLERANCE = 300;

    /** @var string */
    private string $base;

    /**
     * Constructor.
     *
     * @param string $apikey
     * @param string $environment sandbox or live
     * @param \curl|null $curl For tests.
     */
    public function __construct(
        /** @var string The API key. */
        private readonly string $apikey,
        string $environment,
        /** @var \curl|null Curl to use, for tests. */
        private readonly ?\curl $curl = null
    ) {
        if ($apikey === '') {
            throw new api_exception(get_string('error:required', 'paygw_whop'));
        }
        $this->base = $environment === 'live' ? self::LIVE_URL : self::SANDBOX_URL;
    }

    /**
     * The request body for a one-time checkout: a hidden variant on the business's Moodle product.
     *
     * Whop's current API takes a variant (or variant_id); an inline plan is refused.
     *
     * @param string $companyid biz_...
     * @param string $productid prod_... (see create_product)
     * @param float $amount Major units.
     * @param string $currency ISO code.
     * @param string $title What is paid for; Whop caps variant titles at 30 characters.
     * @param string $redirecturl Where Whop sends the payer afterwards.
     * @param array $metadata Copied to the payment, so the webhook can find our checkout.
     * @return array
     */
    public static function checkout_body(
        string $companyid,
        string $productid,
        float $amount,
        string $currency,
        string $title,
        string $redirecturl,
        array $metadata
    ): array {
        return [
            'mode' => 'payment',
            'redirect_url' => $redirecturl,
            'metadata' => $metadata,
            'variant' => [
                'account_id' => $companyid,
                'product_id' => $productid,
                'currency' => strtolower($currency),
                'billing_type' => 'one_time',
                'initial_price' => round($amount, 2),
                'title' => \core_text::substr($title, 0, 30),
                'description' => shorten_text($title, 400),
                'visibility' => 'hidden',
                'release_method' => 'buy_now',
                'unlimited_stock' => true,
                'force_create_new_plan' => true,
            ],
        ];
    }

    /**
     * Create the hidden product that Moodle checkouts are sold under.
     *
     * @param string $companyid biz_...
     * @param string $title
     * @return string prod_...
     */
    public function create_product(string $companyid, string $title): string {
        $product = $this->request('post', '/products', ['account_id' => $companyid, 'title' => $title, 'visibility' => 'hidden']);
        if (empty($product['id'])) {
            throw new api_exception(get_string('error:api', 'paygw_whop', 'no product id'));
        }
        return (string) $product['id'];
    }

    /**
     * Create a one-time checkout and return it with an absolute purchase URL.
     *
     * @param string $companyid biz_...
     * @param string $productid prod_...
     * @param float $amount Major units.
     * @param string $currency ISO code.
     * @param string $title What is paid for.
     * @param string $redirecturl Where Whop sends the payer afterwards.
     * @param array $metadata Copied to the payment.
     * @return array With id and purchase_url.
     */
    public function create_checkout(
        string $companyid,
        string $productid,
        float $amount,
        string $currency,
        string $title,
        string $redirecturl,
        array $metadata
    ): array {
        $checkout = $this->request(
            'post',
            '/checkout_configurations',
            self::checkout_body($companyid, $productid, $amount, $currency, $title, $redirecturl, $metadata)
        );
        if (empty($checkout['id']) || empty($checkout['purchase_url'])) {
            throw new api_exception(get_string('error:api', 'paygw_whop', 'no purchase url'));
        }
        $url = (string) $checkout['purchase_url'];
        // The purchase URL may come back as a path.
        $checkout['purchase_url'] = str_starts_with($url, 'http') ? $url : 'https://whop.com/' . ltrim($url, '/');
        return $checkout;
    }

    /**
     * Read a payment: the only source of truth for its status.
     *
     * @param string $id pay_...
     * @return array
     */
    public function get_payment(string $id): array {
        if (!preg_match('/^pay_[A-Za-z0-9]{1,60}$/', $id)) {
            throw new api_exception(get_string('error:api', 'paygw_whop', 'bad payment id'));
        }
        return $this->request('get', '/payments/' . $id);
    }

    /**
     * Whether a webhook was signed with this secret (Standard Webhooks: HMAC-SHA256 over id.timestamp.body).
     *
     * @param string $body Raw body.
     * @param string $id webhook-id header.
     * @param string $timestamp webhook-timestamp header.
     * @param string $signatures webhook-signature header: one or more "v1,<base64>" separated by spaces.
     * @param string $secret The ws_ secret, exactly as Whop gives it.
     * @param int|null $now For tests.
     * @return bool
     */
    public static function valid_signature(
        string $body,
        string $id,
        string $timestamp,
        string $signatures,
        string $secret,
        ?int $now = null
    ): bool {
        $late = abs(($now ?? time()) - (int) $timestamp) > self::TOLERANCE;
        if ($id === '' || !ctype_digit($timestamp) || $secret === '' || $late) {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', "$id.$timestamp.$body", $secret, true));
        foreach (preg_split('/\s+/', trim($signatures)) as $candidate) {
            [$version, $sig] = array_pad(explode(',', $candidate, 2), 2, '');
            if ($version === 'v1' && hash_equals($expected, $sig)) {
                return true;
            }
        }
        return false;
    }

    /**
     * One API call.
     *
     * @param string $verb get or post
     * @param string $path
     * @param array|null $body Sent as JSON.
     * @return array
     */
    private function request(string $verb, string $path, ?array $body = null): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = $this->curl ?? new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $this->apikey,
            'Accept: application/json',
            'Content-Type: application/json',
        ]);
        $options = ['CURLOPT_TIMEOUT' => 20, 'CURLOPT_CONNECTTIMEOUT' => 8];
        $raw = $verb === 'post'
            ? $curl->post($this->base . $path, json_encode($body), $options)
            : $curl->get($this->base . $path, [], $options);
        if ($curl->get_errno()) {
            throw new api_exception(get_string('error:api', 'paygw_whop', $curl->error));
        }
        $status = (int) ($curl->get_info()['http_code'] ?? 0);
        $data = json_decode((string) $raw, true);
        if ($status >= 400 || !is_array($data)) {
            $why = is_array($data) && !empty($data['error']['message']) ? (string) $data['error']['message']
                : (is_array($data) && !empty($data['message']) ? (string) $data['message'] : "HTTP $status");
            throw new api_exception(get_string('error:api', 'paygw_whop', shorten_text($why, 200)));
        }
        return $data;
    }
}
