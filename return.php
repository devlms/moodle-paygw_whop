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
 * Back from Whop: check the checkout with Whop, never trust the redirect, then go on.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_whop\local\checkout_manager;

require_once(__DIR__ . '/../../../config.php');

require_login();
$id = required_param('id', PARAM_INT);
$row = $DB->get_record('paygw_whop', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/payment/gateway/whop/return.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('paywith', 'paygw_whop'));
$PAGE->set_heading(get_string('paywith', 'paygw_whop'));

// Whop adds the payment id to the return URL when it has one; the webhook settles it otherwise.
$whoppaymentid = optional_param('payment_id', '', PARAM_ALPHANUMEXT);
try {
    $row = checkout_manager::sync($id, $whoppaymentid);
} catch (\moodle_exception $e) {
    // Whop could not be reached; the webhook will still settle it.
    debugging($e->getMessage(), DEBUG_DEVELOPER);
}

if ($row->status === checkout_manager::PAID && $row->owner === checkout_manager::SELF) {
    redirect(
        helper::get_success_url($row->component, $row->paymentarea, (int) $row->itemid),
        get_string('paid', 'paygw_whop'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
if ($row->status === checkout_manager::PAID) {
    // A hub started it: the hub knows where to go next.
    $next = component_class_callback('\\' . $row->owner . '\\hub', 'success_url', [(string) $row->reference], null);
    redirect(
        $next ?: new moodle_url('/'),
        get_string('paid', 'paygw_whop'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$key = match ($row->status) {
    checkout_manager::FAILED => 'failed',
    checkout_manager::CANCELED => 'canceled',
    default => 'waiting',
};
echo $OUTPUT->notification(get_string($key, 'paygw_whop'), $key === 'waiting' ? 'info' : 'warning', false);
echo html_writer::div(
    $key === 'waiting'
        ? html_writer::link($PAGE->url, get_string('checkagain', 'paygw_whop'), ['class' => 'btn btn-primary'])
        : html_writer::link(
            helper::get_success_url($row->component, $row->paymentarea, (int) $row->itemid),
            get_string('back', 'paygw_whop'),
            ['class' => 'btn btn-secondary']
        ),
    'mt-3'
);
echo $OUTPUT->footer();
