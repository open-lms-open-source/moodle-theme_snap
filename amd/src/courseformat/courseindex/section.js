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
 * Course index section component.
 * Override Core behavior from course/format/amd/src/local/courseindex/section.js
 *
 * @module     theme_snap/courseformat/courseindex/section
 * @copyright Copyright (c) 2026 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseSectionComponent from 'core_courseformat/local/courseindex/section';

export default class Component extends BaseSectionComponent {

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
     */
    stateReady(state) {
        super.stateReady(state);
        // Scroll to a newly created module, if we get confirmation there is one.
        if (M.cfg.theme_snap_coursemodulecreatedid) {
            const createdModule = document.getElementById('module-' + M.cfg.theme_snap_coursemodulecreatedid);
            createdModule.scrollIntoView(false);
            window.scrollBy({top: window.innerHeight / 3});
            createdModule.querySelector('.activity-item').classList.add('highlight-new-activity');
        }
    }

    /**
     * Handle a page item update.
     *
     * @param {Object} details the update details
     * @param {Object} details.state the state data.
     * @param {Object} details.element the course state data.
     */
    _refreshPageItem({element, state}) {
        super._refreshPageItem({element, state});
        // Scroll to a newly created module, if we get confirmation there is one.
        if (M.cfg.theme_snap_coursemodulecreatedid) {
            const createdModule = document.getElementById('module-' + M.cfg.theme_snap_coursemodulecreatedid);
            createdModule.scrollIntoView(false);
            window.scrollBy({top: window.innerHeight / 3});
            createdModule.querySelector('.activity-item').classList.add('highlight-new-activity');
        }
    }
}
