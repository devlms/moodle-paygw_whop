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

namespace paygw_whop\event;

/**
 * A Whop payment failed or was canceled.
 *
 * Other data: component, paymentarea, itemid (what was paid for), amount and currency (charged), gateway.
 * Workflow tools such as Autoflow can trigger on it.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_failed extends \core\event\base {
    /**
     * Init.
     */
    protected function init() {
        $this->data['objecttable'] = 'paygw_whop';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:paymentfailed', 'paygw_whop');
    }

    /**
     * Description.
     *
     * @return string
     */
    public function get_description() {
        $o = $this->other;
        return "A Whop payment of {$o['amount']} {$o['currency']}" .
            " by the user with id '{$this->relateduserid}' failed or was canceled" .
            " ({$o['component']}, item {$o['itemid']}).";
    }

    /**
     * Other data needed.
     */
    protected function validate_data() {
        parent::validate_data();
        foreach (['component', 'paymentarea', 'itemid', 'amount', 'currency', 'gateway'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '$key' value must be set in other.");
            }
        }
    }

    /**
     * Other data mapping for backup and restore: none of it points at a restorable object.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }

    /**
     * The checkout row is not restored with a course.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'paygw_whop', 'restore' => \core\event\base::NOT_MAPPED];
    }
}
