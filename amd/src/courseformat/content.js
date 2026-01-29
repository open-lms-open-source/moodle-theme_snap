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
 * Course index main component.
 * Override Core behavior from course/format/amd/src/local/content.js
 *
 * @module     theme_snap/courseformat/content
 * @copyright Copyright (c) 2026 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseSectionComponent from 'core_courseformat/local/content';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

export default class Component extends BaseSectionComponent {

    /**
     * Override static init to ensure it creates an instance of THIS class.
     *
     * @param {string} target the DOM main element or its ID
     * @param {object} selectors optional CSS selector overrides
     * @param {number} sectionReturn the section number of the displayed page
     * @param {number} pageSectionId the section ID of the displayed page
     * @return {Component}
     */
    static init(target, selectors, sectionReturn, pageSectionId) {
        return new Component({
            element: document.getElementById(target),
            reactive: getCurrentCourseEditor(),
            selectors,
            sectionReturn,
            pageSectionId,
        });
    }

    /**
     * Override Constructor hook.
     *
     * @param {Object} descriptor the component descriptor
     */
    create(descriptor) {
        super.create(descriptor);

        const isTopics = document.body.classList.contains('format-topics');
        const isWeeks = document.body.classList.contains('format-weeks');
        if (isTopics || isWeeks) {
            // Change Selector according to Snap HTML structure in weeks and topics formats.
            this.selectors.SECTION = ".single-section > ul > li[data-for='section']";
        }
    }
    /**
     * Override _scrollHandler to do nothing, so it does not make Weird jumps in the course while navigating.
     */
    _scrollHandler() {
        return;
    }
}