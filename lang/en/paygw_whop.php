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
 * Strings for paygw_whop.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['apikey'] = 'API key';
$string['apikey_help'] = 'A company API key from the Developer page of your Whop dashboard. Sandbox and live have separate keys.';
$string['back'] = 'Back';
$string['canceled'] = 'The payment was canceled. Nothing was charged.';
$string['checkagain'] = 'Check again';
$string['companyid'] = 'Company ID';
$string['companyid_help'] = 'Your Whop company ID, starting with biz_. It is in the URL of your Whop dashboard.';
$string['continue'] = 'Continue to payment';
$string['environment'] = 'Environment';
$string['environment:live'] = 'Live';
$string['environment:sandbox'] = 'Sandbox (test)';
$string['environment_help'] = 'Sandbox uses Whop\'s test environment, where no money moves and the test card 4242 4242 4242 4242 works. Switch to Live with your live keys.';
$string['error:api'] = 'Whop: {$a}';
$string['error:busy'] = 'This payment is being checked right now. Try again in a moment.';
$string['error:companyid'] = 'Enter your Whop company ID, starting with biz_.';
$string['error:currency'] = 'Whop cannot take payments in this currency.';
$string['error:generic'] = '{$a}';
$string['error:owner'] = 'The payment went through, but {$a} could not record it. Contact the site administrator.';
$string['error:required'] = 'Required to enable Whop.';
$string['error:webhooksecret'] = 'Enter the webhook signing secret from Whop. It starts with ws_.';
$string['event:paymentfailed'] = 'Whop payment failed';
$string['event:paymentreceived'] = 'Whop payment received';
$string['failed'] = 'The payment did not go through. You can try again.';
$string['gatewaydescription'] = 'Cards, Apple Pay, Google Pay, PayPal and more, through Whop.';
$string['gatewayname'] = 'Card or wallet (Whop)';
$string['method:whop'] = 'Card, Apple Pay, Google Pay or PayPal';
$string['paid'] = 'Payment received. Thank you.';
$string['paywith'] = 'Pay with Whop';
$string['pluginname'] = 'Whop';
$string['pluginname_desc'] = 'Take course payments from buyers anywhere through Whop, which acts as merchant of record.';
$string['privacy:metadata:paygw_whop'] = 'Whop checkouts started on this site.';
$string['privacy:metadata:paygw_whop:amount'] = 'The amount asked.';
$string['privacy:metadata:paygw_whop:checkoutid'] = 'The checkout id at Whop.';
$string['privacy:metadata:paygw_whop:status'] = 'Whether the checkout is pending, paid, failed or canceled.';
$string['privacy:metadata:paygw_whop:timecreated'] = 'When the checkout was started.';
$string['privacy:metadata:paygw_whop:userid'] = 'The person paying.';
$string['privacy:metadata:whop'] = 'To take a payment, the amount, the currency, the name of what is paid for and a reference are sent to Whop. The payer enters their payment details on Whop\'s page, not in Moodle.';
$string['privacy:metadata:whop:amount'] = 'The amount to pay.';
$string['privacy:metadata:whop:description'] = 'What is paid for.';
$string['privacy:metadata:whop:metadata'] = 'A reference to this payment in Moodle.';
$string['redirectnote'] = 'You pay on Whop\'s secure page, then come back here.';
$string['waiting'] = 'Whop has not confirmed this payment yet. This usually takes a few seconds.';
$string['webhooksecret'] = 'Webhook secret';
$string['webhooksecret_help'] = 'In the Whop dashboard, create a webhook for the address below with the payment events, then paste its signing secret (ws_...).';
$string['webhookurl'] = 'Webhook URL';
$string['webhookurl_help'] = 'Create a webhook for this address in the Whop dashboard (Developer, Create webhook), with the payment.succeeded and payment.failed events.';
