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
 *
 * Course section format component.
 * Override Core behavior from course/format/amd/src/local/content/section.js
 *
 * @module     theme_snap/courseformat/content/section
 * @copyright  Copyright (c) 2026 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseSection from 'core_courseformat/local/content/section';

export default class Section extends BaseSection {
    /**
     * Override Constructor hook.
     */
    create() {
        super.create();
        const isTopics = document.body.classList.contains('format-topics');
        const isWeeks = document.body.classList.contains('format-weeks');
        if (isTopics || isWeeks) {
            // Change Selector according to Snap HTML structure in weeks and topics formats.
            this.selectors.ACTIONMENU = '.snap-section-editing.section-actions';
        }
    }
    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        let watchers = [
            {watch: `section[${this.id}]:updated`, handler: this._refreshSection},
        ];
        // Set watcher for parent Section changes, if we are in a delegated section (Subsection)
        const parentSectionId = this.reactive.state.section.get(this.id)?.parentsectionid;
        if (parentSectionId !== null) {
            watchers.push({watch: `section[${parentSectionId}]:updated`, handler: this._refreshSection});
        }
        return watchers;
    }

    /**
     * Update a content section using the state information.
     *
     * @param {object} param
     * @param {Object} param.element details the update details.
     */
    _refreshSection({element}) {
        const parentSectionId = this.reactive.state.section.get(this.id)?.parentsectionid;

        // The element ID (The one that triggers the event) is the same as Parent Section ID.
        // It means the parent changed, and we need to update the subsection.
        if (parentSectionId === element.id) {
            const isParentVisible = element.visible;
            const subsection = this.element.closest('ul.sections > .section.main');
            const visibilityControl = subsection.querySelector('.snap-visibility');

            if (visibilityControl) {
                if (!isParentVisible) {
                    // Parent Hidden, Do not show visibility button on subsection.
                    visibilityControl.classList.add('d-none');
                } else {
                    // Parent is visible, restore subsection visibility button.
                    visibilityControl.classList.remove('d-none');
                }
            }
            return;
        }
        super._refreshSection({element});
    }
}