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
     * We keep Core's own watcher untouched (own section id updates keep using Core's
     * _refreshSection), and add a dedicated watcher/handler only for the case where this
     * section is a delegated section (subsection) that needs to react to its parent's
     * visibility changes.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        const watchers = super.getWatchers();
        // Set watcher for parent Section changes, if we are in a delegated section (Subsection).
        const parentSectionId = this.reactive.state.section.get(this.id)?.parentsectionid;
        if (parentSectionId) {
            watchers.push({watch: `section[${parentSectionId}]:updated`, handler: this._refreshParentSection});
        }
        return watchers;
    }

    /**
     * React to the parent section (of a delegated section/subsection) changing.
     *
     * When the parent section is hidden, the subsection is implicitly hidden too, so its own
     * visibility toggle should be disabled (hidden). When the parent becomes visible again, the
     * subsection's own visibility toggle should be restored.
     *
     * Note: this.element is always this component's own bound element (the subsection's own
     * section container)
     *
     * @param {object} param
     * @param {Object} param.element the updated parent section state.
     */
    _refreshParentSection({element}) {
        const visibilityControl = this.element.querySelector('.snap-visibility');
        if (!visibilityControl) {
            return;
        }
        visibilityControl.classList.toggle(this.classes.HIDE, !element.visible);
    }
}