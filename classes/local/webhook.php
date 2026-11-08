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

use paygw_whop\api\client;

/**
 * Handles one Whop webhook. Kept apart from webhook.php so it can be tested without HTTP.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook {
    /**
     * Check and act on a webhook.
     *
     * @param string $body Raw body.
     * @param array $headers webhook-id, webhook-timestamp, webhook-signature.
     * @param int|null $now For tests.
     * @return int HTTP status to answer with.
     */
    public static function handle(string $body, array $headers, ?int $now = null): int {
        $event = json_decode($body, true);
        $payment = is_array($event) ? ($event['data'] ?? []) : [];
        $checkoutid = (string) ($payment['checkout_configuration_id'] ?? '');
        $paymentid = (string) ($payment['id'] ?? '');
        if (!is_array($event) || empty($headers['webhook-signature'])) {
            return 400;
        }
        if (!str_starts_with((string) ($event['type'] ?? ''), 'payment.') || $checkoutid === '' || $paymentid === '') {
            return 200;
        }
        $row = checkout_manager::find($checkoutid);
        if (!$row) {
            // Not a checkout this site started: acknowledge so Whop stops retrying.
            return 200;
        }
        $config = checkout_manager::account_config((int) $row->accountid);
        if (
            !client::valid_signature(
                $body,
                (string) ($headers['webhook-id'] ?? ''),
                (string) ($headers['webhook-timestamp'] ?? ''),
                (string) $headers['webhook-signature'],
                (string) ($config['webhooksecret'] ?? ''),
                $now
            )
        ) {
            return 403;
        }
        try {
            checkout_manager::sync((int) $row->id, $paymentid);
        } catch (\moodle_exception $e) {
            return 503;
        }
        return 200;
    }
}
