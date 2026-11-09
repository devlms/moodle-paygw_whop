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

namespace paygw_whop\local;

use core_payment\helper;
use paygw_whop\api\client;
use stdClass;

/**
 * The life of a checkout: created as pending, then paid, failed or canceled, and never back.
 *
 * Every change of status happens here, under a lock per checkout, and only after reading the payment from
 * Whop's API: a webhook or a redirect only says "look again", never "it is paid". A paid checkout is delivered
 * once, whichever of the webhook and the return page arrives first.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkout_manager {
    /** @var string Waiting for the payer. */
    public const PENDING = 'pending';
    /** @var string Paid and delivered. */
    public const PAID = 'paid';
    /** @var string The payment failed. */
    public const FAILED = 'failed';
    /** @var string The payer canceled, or the checkout expired. */
    public const CANCELED = 'canceled';

    /** @var string This gateway, when it delivers the order itself. */
    public const SELF = 'paygw_whop';

    /** @var \curl|null For tests. */
    public static ?\curl $curl = null;

    /**
     * Start paying for an item: create the checkout and return the URL to send the payer to.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param int $userid
     * @param string $description
     * @param string $owner Component that delivers the order when paid (a hub), or this gateway.
     * @param string|null $reference The owner's own reference.
     * @param float|null $amount Amount to charge, for an owner that priced the item itself.
     * @param string|null $currency Currency of $amount.
     * @return string Whop's checkout URL.
     */
    public static function start(
        string $component,
        string $paymentarea,
        int $itemid,
        int $userid,
        string $description,
        string $owner = self::SELF,
        ?string $reference = null,
        ?float $amount = null,
        ?string $currency = null
    ): string {
        global $DB;
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'whop');
        if ($amount === null) {
            $currency = $payable->get_currency();
            $amount = helper::get_rounded_cost($payable->get_amount(), $currency, helper::get_gateway_surcharge('whop'));
        }
        if (!in_array($currency, \paygw_whop\gateway::get_supported_currencies(), true)) {
            throw new \moodle_exception('error:currency', 'paygw_whop');
        }
        $now = time();
        $row = (object) [
            'accountid' => $payable->get_account_id(),
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'userid' => $userid,
            'amount' => round($amount, 2),
            'currency' => $currency,
            'status' => self::PENDING,
            'owner' => $owner,
            'reference' => $reference,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $row->id = $DB->insert_record('paygw_whop', $row);
        $client = self::client($config);
        $companyid = (string) ($config['companyid'] ?? '');
        $checkout = $client->create_checkout(
            $companyid,
            self::product($client, $companyid),
            (float) $row->amount,
            $currency,
            format_string($description, true, ['escape' => false]),
            (new \moodle_url('/payment/gateway/whop/return.php', ['id' => $row->id]))->out(false),
            ['moodle_checkout' => (string) $row->id]
        );
        $DB->update_record('paygw_whop', (object) ['id' => $row->id, 'checkoutid' => (string) $checkout['id'],
            'timemodified' => time()]);
        return (string) $checkout['purchase_url'];
    }

    /**
     * The hidden Whop product that this business's Moodle checkouts are sold under: created once, then remembered.
     *
     * @param client $client
     * @param string $companyid biz_...
     * @return string prod_...
     */
    private static function product(client $client, string $companyid): string {
        $key = 'product_' . preg_replace('/[^A-Za-z0-9_]/', '', $companyid);
        $productid = (string) get_config('paygw_whop', $key);
        if ($productid === '') {
            $productid = $client->create_product($companyid, get_string('productname', 'paygw_whop'));
            set_config($key, $productid, 'paygw_whop');
        }
        return $productid;
    }

    /**
     * Read a payment from Whop and move our checkout on: deliver it if paid, close it if it failed.
     *
     * @param int $id Our checkout row.
     * @param string $whoppaymentid The Whop payment (pay_...) to check, from a webhook or the return URL.
     * @return stdClass The row after the update.
     */
    public static function sync(int $id, string $whoppaymentid = ''): stdClass {
        global $DB;
        $lock = \core\lock\lock_config::get_lock_factory('paygw_whop')->get_lock('checkout_' . $id, 15);
        if (!$lock) {
            throw new \moodle_exception('error:busy', 'paygw_whop');
        }
        try {
            $row = $DB->get_record('paygw_whop', ['id' => $id], '*', MUST_EXIST);
            $whoppaymentid = $whoppaymentid !== '' ? $whoppaymentid : (string) $row->whoppaymentid;
            if ($row->status !== self::PENDING || empty($row->checkoutid) || $whoppaymentid === '') {
                return $row;
            }
            $remote = self::client(self::account_config((int) $row->accountid))->get_payment($whoppaymentid);
            // The payment must belong to the checkout we created, in our currency, for at least our amount.
            if ((string) ($remote['checkout_configuration_id'] ?? '') !== (string) $row->checkoutid) {
                return $row;
            }
            $row->whoppaymentid = $whoppaymentid;
            $DB->set_field('paygw_whop', 'whoppaymentid', $whoppaymentid, ['id' => $row->id]);
            $status = (string) ($remote['status'] ?? '');
            if ($status === 'paid') {
                $total = (float) ($remote['total']['amount'] ?? $remote['total'] ?? -1);
                $samecurrency = strtolower((string) ($remote['currency'] ?? '')) === strtolower((string) $row->currency);
                if ($total + 0.001 < (float) $row->amount || !$samecurrency) {
                    self::move($row, self::FAILED);
                    debugging("Whop payment {$whoppaymentid}: amount or currency does not match", DEBUG_NORMAL);
                    return $row;
                }
                // Saving the payment, delivering the order and marking the checkout paid succeed or fail together,
                // so a retry never records the payment twice.
                $transaction = $DB->start_delegated_transaction();
                try {
                    $row->paymentid = self::deliver($row);
                    self::move($row, self::PAID);
                    $transaction->allow_commit();
                } catch (\Throwable $e) {
                    $row->status = self::PENDING;
                    $transaction->rollback($e);
                }
            } else if (in_array($status, ['void', 'uncollectible'], true)) {
                self::move($row, self::FAILED);
            }
            return $row;
        } finally {
            $lock->release();
        }
    }

    /**
     * Hand the paid order over: save the payment and deliver it here, or let the owner (a hub) do it.
     *
     * @param stdClass $row
     * @return int core payments id
     */
    private static function deliver(stdClass $row): int {
        if ($row->owner !== self::SELF) {
            $paymentid = component_class_callback(
                '\\' . $row->owner . '\\hub',
                'complete',
                [(string) $row->reference, 'whop', (float) $row->amount, (string) $row->currency],
                null
            );
            if (!$paymentid) {
                throw new \moodle_exception('error:owner', 'paygw_whop', '', $row->owner);
            }
            return (int) $paymentid;
        }
        $paymentid = helper::save_payment(
            (int) $row->accountid,
            $row->component,
            $row->paymentarea,
            (int) $row->itemid,
            (int) $row->userid,
            (float) $row->amount,
            (string) $row->currency,
            'whop'
        );
        helper::deliver_order($row->component, $row->paymentarea, (int) $row->itemid, $paymentid, (int) $row->userid);
        return $paymentid;
    }

    /**
     * Record a new status. Only pending checkouts change.
     *
     * @param stdClass $row
     * @param string $status
     */
    private static function move(stdClass $row, string $status): void {
        global $DB;
        $row->status = $status;
        $row->timemodified = time();
        $DB->update_record('paygw_whop', (object) ['id' => $row->id, 'status' => $status,
            'paymentid' => $row->paymentid ?? null, 'timemodified' => $row->timemodified]);
        $event = $status === self::PAID ? \paygw_whop\event\payment_received::class
            : \paygw_whop\event\payment_failed::class;
        $event::create([
            'context' => \context_system::instance(),
            'objectid' => $row->id,
            'relateduserid' => $row->userid,
            'other' => ['component' => $row->component, 'paymentarea' => $row->paymentarea, 'itemid' => (int) $row->itemid,
                'amount' => (float) $row->amount, 'currency' => $row->currency, 'gateway' => 'whop'],
        ])->trigger();
    }

    /**
     * The checkout row for a Whop checkout configuration id (ch_...).
     *
     * @param string $checkoutid
     * @return stdClass|null
     */
    public static function find(string $checkoutid): ?stdClass {
        global $DB;
        return $DB->get_record('paygw_whop', ['checkoutid' => $checkoutid]) ?: null;
    }

    /**
     * The Whop settings of a payment account.
     *
     * @param int $accountid
     * @return array
     */
    public static function account_config(int $accountid): array {
        // All gateways, not only enabled ones: a checkout started before the gateway was switched off must still settle.
        $gateways = (new \core_payment\account($accountid))->get_gateways(false);
        return isset($gateways['whop']) ? $gateways['whop']->get_configuration() : [];
    }

    /**
     * An API client for some settings.
     *
     * @param array $config
     * @return client
     */
    private static function client(array $config): client {
        return new client((string) ($config['apikey'] ?? ''), (string) ($config['environment'] ?? 'sandbox'), self::$curl);
    }
}
