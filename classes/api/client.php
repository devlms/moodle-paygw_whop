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
     * Create a one-time checkout for an amount.
     *
     * @param string $companyid biz_...
     * @param float $amount In major units (dollars, euros).
     * @param string $currency ISO 4217.
     * @param string $reference Our stable identifier for the item, so Whop reuses one product per item.
     * @param string $title Shown on the checkout.
     * @param string $redirecturl Where Whop sends the payer afterwards.
     * @param array $metadata Kept on the checkout.
     * @return array With at least id and purchase_url.
     */
    public function create_checkout(
        string $companyid,
        float $amount,
        string $currency,
        string $reference,
        string $title,
        string $redirecturl,
        array $metadata
    ): array {
        $checkout = $this->request('post', '/checkout_configurations', [
            'mode' => 'payment',
            'redirect_url' => $redirecturl,
            'metadata' => $metadata,
            'plan' => [
                'company_id' => $companyid,
                'currency' => strtolower($currency),
                'plan_type' => 'one_time',
                'initial_price' => round($amount, 2),
                'product' => ['external_identifier' => $reference, 'title' => shorten_text($title, 80)],
            ],
        ]);
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
