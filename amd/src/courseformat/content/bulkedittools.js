// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The bulk editor tools bar.
 * This overrides course/format/amd/src/local/content/bulkedittools.js
 * So Snap bulkselection is used instead
 *
 * @module     theme_snap/courseformat/content/bulkedittools
 * @class      theme_snap/courseformat/content/bulkedittools
 * @copyright  2026 OpenLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseComponent from 'core_courseformat/local/content/bulkedittools';
import {
    selectAllBulk,
    switchBulkSelection,
    checkAllBulkSelected
} from 'theme_snap/courseformat/content/actions/bulkselection';
import Pending from 'core/pending';
export default class Component extends BaseComponent {


    /**
     * Refresh the select all element.
     *
     * @param {object} param
     * @param {Object} param.element the affected element (bulk in this case).
     */
    _refreshSelectAll({element: bulk}) {
        const selectall = this.getElement(this.selectors.SELECTALL);
        if (!selectall) {
            return;
        }
        selectall.disabled = (bulk.selectedType === '');
        // The changechecker module can prevent the checkbox form changing it's value.
        // To avoid that we leave the sniffer to act before changing the value.
        const pending = new Pending(`courseformat/bulktools:refreshSelectAll`);
        setTimeout(
            () => {
                selectall.checked = checkAllBulkSelected(this.reactive);
                pending.resolve();
            },
            100
        );
    }


    /**
     * Handle special select all cases.
     * @param {Event} event
     */
    _selectAllClick(event) {
        event.preventDefault();
        if (event.altKey) {
            switchBulkSelection(this.reactive);
            return;
        }
        if (checkAllBulkSelected(this.reactive)) {
            this._handleUnselectAll();
            return;
        }
        selectAllBulk(this.reactive, true);
    }

    /**
     * Process unselect all elements.
     */
    _handleUnselectAll() {
        const pending = new Pending(`courseformat/content:bulktUnselectAll`);
        selectAllBulk(this.reactive, false);
        // Wait for a while and focus on the first checkbox.
        setTimeout(() => {
            document.querySelector(this.selectors.SELECTABLE)?.focus();
            pending.resolve();
        }, 150);
    }
}