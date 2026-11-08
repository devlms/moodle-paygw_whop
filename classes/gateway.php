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

namespace paygw_whop;

/**
 * Whop: cards, Apple Pay, Google Pay, PayPal and more from buyers anywhere, with Whop as merchant of record.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Currencies a course can be priced in. Whop shows prices in many more; these are the ones it settles.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['AUD', 'BRL', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD', 'JPY', 'MXN', 'NOK', 'NZD', 'PLN', 'SEK', 'SGD', 'USD'];
    }

    /**
     * Settings for a payment account.
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_whop'), [
            'sandbox' => get_string('environment:sandbox', 'paygw_whop'),
            'live' => get_string('environment:live', 'paygw_whop'),
        ]);
        $mform->addHelpButton('environment', 'environment', 'paygw_whop');
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'paygw_whop'));
        $mform->setType('apikey', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('apikey', 'apikey', 'paygw_whop');
        $mform->addElement('text', 'companyid', get_string('companyid', 'paygw_whop'), ['size' => 30]);
        $mform->setType('companyid', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('companyid', 'companyid', 'paygw_whop');
        $mform->addElement('passwordunmask', 'webhooksecret', get_string('webhooksecret', 'paygw_whop'));
        $mform->setType('webhooksecret', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('webhooksecret', 'webhooksecret', 'paygw_whop');
        $mform->addElement(
            'static',
            'webhookurl',
            get_string('webhookurl', 'paygw_whop'),
            \html_writer::tag('code', (new \moodle_url('/payment/gateway/whop/webhook.php'))->out(false)) .
            \html_writer::div(get_string('webhookurl_help', 'paygw_whop'), 'form-text text-muted')
        );
    }

    /**
     * All keys are needed to enable it.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        if (empty($data->enabled)) {
            return;
        }
        if (trim((string) ($data->apikey ?? '')) === '') {
            $errors['apikey'] = get_string('error:required', 'paygw_whop');
        }
        if (!preg_match('/^biz_[A-Za-z0-9]+$/', (string) ($data->companyid ?? ''))) {
            $errors['companyid'] = get_string('error:companyid', 'paygw_whop');
        }
        if (!str_starts_with((string) ($data->webhooksecret ?? ''), 'ws_')) {
            $errors['webhooksecret'] = get_string('error:webhooksecret', 'paygw_whop');
        }
    }
}
