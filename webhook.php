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

/**
 * Whop posts payment events here. The signature is checked with the account's webhook secret, then the
 * payment is read back from Whop before anything changes.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Called by Whop's servers: no session, no login.
define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../../config.php');

use paygw_whop\local\webhook;

$status = webhook::handle((string) file_get_contents('php://input'), [
    'webhook-id' => (string) ($_SERVER['HTTP_WEBHOOK_ID'] ?? ''),
    'webhook-timestamp' => (string) ($_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? ''),
    'webhook-signature' => (string) ($_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? ''),
]);
http_response_code($status);
echo $status === 200 ? 'ok' : 'refused';
