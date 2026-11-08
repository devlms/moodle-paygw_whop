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
 * Whop is a hosted checkout: leave the modal for the page that sends the payer to Whop.
 *
 * @module     paygw_whop/gateways_modal
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Url from 'core/url';

/**
 * Called by core when the payer chooses Whop.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @param {string} description Description of the payment
 * @returns {Promise<string>} Never resolves: the page is replaced.
 */
export const process = (component, paymentArea, itemId, description) => {
    window.location.href = Url.relativeUrl('/payment/gateway/whop/pay.php', {
        component,
        paymentarea: paymentArea,
        itemid: itemId,
        description,
    });
    return new Promise(() => null);
};
