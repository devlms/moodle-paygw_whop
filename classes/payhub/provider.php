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

namespace paygw_whop\payhub;

use paygw_whop\gateway;
use paygw_whop\local\checkout_manager;
use paygw_payhub\request;

defined('MOODLE_INTERNAL') || die();

// The hub is optional: without it this class is not declared at all, so loading the file never fails.
if (!interface_exists(\paygw_payhub\provider::class)) {
    return;
}

/**
 * Lets the DevLMS payment hub offer Whop. Only the hub loads this class.
 *
 * @package    paygw_whop
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \paygw_payhub\provider {
    /**
     * Gateway name.
     *
     * @return string
     */
    public static function name(): string {
        return 'whop';
    }

    /**
     * Buyers from anywhere.
     *
     * @return string
     */
    public static function region(): string {
        return self::INTERNATIONAL;
    }

    /**
     * The currencies Whop settles.
     *
     * @return string[]
     */
    public static function currencies(): array {
        return gateway::get_supported_currencies();
    }

    /**
     * One way in: Whop's checkout offers the payer's cards and wallets.
     *
     * @param array $config
     * @return string[]
     */
    public static function methods(array $config): array {
        return ['whop' => get_string('method:whop', 'paygw_whop')];
    }

    /**
     * Start a Whop checkout for the hub; Whop's confirmation goes back to the hub.
     *
     * @param request $request
     * @return string
     */
    public static function start(request $request): string {
        return checkout_manager::start(
            $request->component,
            $request->paymentarea,
            $request->itemid,
            $request->userid,
            $request->description,
            'paygw_payhub',
            $request->reference,
            $request->amount,
            $request->currency
        );
    }
}
