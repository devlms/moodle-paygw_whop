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
 * Whop checkouts from start to delivery, with Whop's answers mocked.
 *
 * @package    paygw_whop
 * @category   test
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_whop\local\checkout_manager
 * @covers     \paygw_whop\local\webhook
 * @covers     \paygw_whop\api\client
 * @covers     \paygw_whop\gateway
 * @covers     \paygw_whop\event\payment_received
 * @covers     \paygw_whop\event\payment_failed
 */
final class checkout_manager_test extends \advanced_testcase {
    /** @var string Webhook secret. */
    private const SECRET = 'ws_test_secret_value';
    /** @var \stdClass */
    private \stdClass $course;
    /** @var \stdClass */
    private \stdClass $student;
    /** @var int enrol_fee instance id */
    private int $instanceid;

    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->libdir . '/filelib.php');
        set_config('enrol_plugins_enabled', 'manual,fee');
        \core\plugininfo\paygw::enable_plugin('whop', 1);
        $gen = $this->getDataGenerator();
        $account = $gen->get_plugin_generator('core_payment')->create_payment_account();
        \core_payment\helper::save_payment_gateway((object) ['accountid' => $account->get('id'), 'gateway' => 'whop',
            'enabled' => 1, 'config' => json_encode(['environment' => 'sandbox', 'apikey' => 'key', 'companyid' => 'biz_1',
            'webhooksecret' => self::SECRET])]);
        $this->course = $gen->create_course();
        $this->instanceid = (int) enrol_get_plugin('fee')->add_instance($this->course, [
            'cost' => 49, 'currency' => 'USD', 'customint1' => $account->get('id'),
            'roleid' => $DB->get_field('role', 'id', ['shortname' => 'student']),
        ]);
        $this->student = $gen->create_user();
        $this->setUser($this->student);
        // The business already has its Moodle product, so checkouts need one answer from Whop.
        set_config('product_biz_1', 'prod_known', 'paygw_whop');
    }

    /**
     * Queue Whop's answers.
     *
     * @param array ...$answers Decoded JSON answers.
     */
    private function answers(array ...$answers): void {
        // Moodle's mock is a stack (array_pop): push the last answer first so they come back in order.
        foreach (array_reverse($answers) as $answer) {
            \curl::mock_response(json_encode($answer));
        }
    }

    /**
     * Start a checkout and return its row.
     *
     * @return \stdClass
     */
    private function started(): \stdClass {
        global $DB;
        $this->answers(['id' => 'ch_1', 'purchase_url' => '/checkout/ch_1/']);
        $url = checkout_manager::start('enrol_fee', 'fee', $this->instanceid, (int) $this->student->id, 'Physics');
        $this->assertSame('https://whop.com/checkout/ch_1/', $url);
        return $DB->get_record('paygw_whop', ['checkoutid' => 'ch_1'], '*', MUST_EXIST);
    }

    /**
     * A payment as Whop returns it.
     *
     * @param string $status
     * @param string $amount
     * @param string $checkout
     * @return array
     */
    private static function payment(string $status, string $amount = '49.00', string $checkout = 'ch_1'): array {
        return ['id' => 'pay_1', 'status' => $status, 'currency' => 'usd', 'checkout_configuration_id' => $checkout,
            'total' => ['amount' => $amount, 'currency' => 'usd']];
    }

    public function test_a_paid_payment_enrols_once_and_fires_an_event(): void {
        global $DB;
        $row = $this->started();
        $this->assertEquals(49, $row->amount);
        $this->answers(self::payment('paid'));
        $sink = $this->redirectEvents();
        $after = checkout_manager::sync((int) $row->id, 'pay_1');
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \paygw_whop\event\payment_received);
        $this->assertCount(1, $events);
        $sink->close();
        $this->assertSame(checkout_manager::PAID, $after->status);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course->id), $this->student));
        $this->assertSame('USD', $DB->get_field('payments', 'currency', ['id' => $after->paymentid]));
        checkout_manager::sync((int) $row->id, 'pay_1');
        $this->assertSame(1, $DB->count_records('payments'));
    }

    public function test_a_payment_of_another_checkout_or_for_less_is_not_delivered(): void {
        $row = $this->started();
        $this->answers(self::payment('paid', '49.00', 'ch_other'));
        $this->assertSame(checkout_manager::PENDING, checkout_manager::sync((int) $row->id, 'pay_1')->status);
        $this->answers(self::payment('paid', '1.00'));
        $after = checkout_manager::sync((int) $row->id, 'pay_1');
        $this->assertDebuggingCalled();
        $this->assertSame(checkout_manager::FAILED, $after->status);
        $this->assertFalse(is_enrolled(\context_course::instance($this->course->id), $this->student));
    }

    public function test_webhook_signatures(): void {
        $this->started();
        $body = json_encode(['type' => 'payment.succeeded', 'data' => ['id' => 'pay_1', 'checkout_configuration_id' => 'ch_1']]);
        $now = 1800000000;
        $sign = fn($secret, $time) => 'v1,' . base64_encode(hash_hmac('sha256', "msg_1.$time.$body", $secret, true));
        $headers = fn($sig, $time) => ['webhook-id' => 'msg_1', 'webhook-timestamp' => (string) $time, 'webhook-signature' => $sig];
        $this->assertSame(403, webhook::handle($body, $headers($sign('ws_wrong', $now), $now), $now));
        $this->assertSame(
            403,
            webhook::handle($body, $headers($sign(self::SECRET, $now - 3600), $now - 3600), $now),
            'An old timestamp is refused.'
        );
        $this->assertSame(200, webhook::handle(
            json_encode(['type' => 'membership.went_valid', 'data' => []]),
            $headers('v1,x', $now),
            $now
        ));
        $this->assertSame(checkout_manager::PENDING, checkout_manager::find('ch_1')->status);
        $this->answers(self::payment('paid'));
        $this->assertSame(
            200,
            webhook::handle($body, $headers('v1,old ' . $sign(self::SECRET, $now), $now), $now),
            'Any one valid signature in the header is enough.'
        );
        $this->assertSame(checkout_manager::PAID, checkout_manager::find('ch_1')->status);
    }

    public function test_client_and_gateway(): void {
        $this->assertFalse(client::valid_signature('{}', 'a', 'notanumber', 'v1,x', self::SECRET));
        $this->assertContains('USD', \paygw_whop\gateway::get_supported_currencies());
        $this->expectException(\paygw_whop\api\api_exception::class);
        (new client('key', 'sandbox'))->get_payment('not a payment id');
    }

    public function test_through_the_hub(): void {
        global $DB;
        if (!class_exists(\paygw_payhub\hub::class)) {
            $this->markTestSkipped('The payment hub is not installed.');
        }
        \core\plugininfo\paygw::enable_plugin('payhub', 1);
        $accountid = (int) $DB->get_field('payment_gateways', 'accountid', ['gateway' => 'whop']);
        \core_payment\helper::save_payment_gateway((object) ['accountid' => $accountid, 'gateway' => 'payhub',
            'enabled' => 1, 'config' => json_encode(['use_whop' => 1])]);
        $this->answers(['id' => 'ch_hub', 'purchase_url' => 'https://whop.com/checkout/ch_hub/']);
        \paygw_payhub\hub::start('enrol_fee', 'fee', $this->instanceid, (int) $this->student->id, 'whop', 'whop', 'Physics');
        $row = checkout_manager::find('ch_hub');
        $this->assertSame('paygw_payhub', $row->owner);
        $this->answers(self::payment('paid', '49.00', 'ch_hub'));
        $this->assertSame(checkout_manager::PAID, checkout_manager::sync((int) $row->id, 'pay_1')->status);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course->id), $this->student));
        $this->assertSame('paid', $DB->get_field('paygw_payhub', 'status', ['reference' => $row->reference]));
    }
    public function test_checkout_body_uses_a_variant_on_the_moodle_product(): void {
        // Whop's current API refuses an inline plan ("variant_id is required for payment mode"): found in a sandbox run.
        $body = client::checkout_body(
            'biz_1',
            'prod_9',
            49.5,
            'USD',
            'Introduction to a very long course title indeed',
            'https://moodle.example/return.php?id=3',
            ['moodle_checkout' => '3']
        );
        $this->assertArrayNotHasKey('plan', $body);
        $this->assertSame('payment', $body['mode']);
        $this->assertSame(['moodle_checkout' => '3'], $body['metadata']);
        $this->assertSame('biz_1', $body['variant']['account_id']);
        $this->assertSame('prod_9', $body['variant']['product_id']);
        $this->assertSame('one_time', $body['variant']['billing_type']);
        $this->assertSame(49.5, $body['variant']['initial_price']);
        $this->assertSame('usd', $body['variant']['currency']);
        $this->assertSame('hidden', $body['variant']['visibility']);
        $this->assertTrue($body['variant']['force_create_new_plan']);
        $this->assertLessThanOrEqual(30, \core_text::strlen($body['variant']['title']), 'Whop caps variant titles at 30.');
    }

    public function test_the_moodle_product_is_created_once_per_business(): void {
        unset_config('product_biz_1', 'paygw_whop');
        // First checkout: create the product, then the checkout.
        $this->answers(
            ['id' => 'prod_new', 'title' => 'Moodle course payments'],
            ['id' => 'ch_a', 'purchase_url' => '/checkout/ch_a/']
        );
        $this->assertSame(
            'https://whop.com/checkout/ch_a/',
            checkout_manager::start('enrol_fee', 'fee', $this->instanceid, (int) $this->student->id, 'Physics')
        );
        $this->assertSame('prod_new', get_config('paygw_whop', 'product_biz_1'));
        // Second checkout reuses it: only a checkout answer is queued.
        $this->answers(['id' => 'ch_b', 'purchase_url' => '/checkout/ch_b/']);
        $this->assertSame(
            'https://whop.com/checkout/ch_b/',
            checkout_manager::start('enrol_fee', 'fee', $this->instanceid, (int) $this->student->id, 'Physics')
        );
    }
    public function test_legacy_webhooks_name_the_event_in_action(): void {
        // Webhooks created on Whop's legacy API version send "action" instead of "type".
        $this->started();
        $body = json_encode(['action' => 'payment.succeeded', 'data' => ['id' => 'pay_1', 'checkout_configuration_id' => 'ch_1']]);
        $now = 1800000000;
        $sig = 'v1,' . base64_encode(hash_hmac('sha256', "msg_9.$now.$body", self::SECRET, true));
        $this->answers(self::payment('paid'));
        $headers = ['webhook-id' => 'msg_9', 'webhook-timestamp' => (string) $now, 'webhook-signature' => $sig];
        $this->assertSame(200, webhook::handle($body, $headers, $now));
        $this->assertSame(checkout_manager::PAID, checkout_manager::find('ch_1')->status);
    }
}
