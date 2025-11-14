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
 * The Snap course TOC progress bar component.
 *
 * @module     theme_snap/progress_bar
 * @class      theme_snap/progress_bar
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import Ajax from "../../../../lib/amd/src/ajax";

export default class extends BaseComponent {

    /**
     * Constructor hook.
     *
     * @param {Object} descriptor
     */
    create(descriptor) {
        this.userid = descriptor.userid;
        this.courseid = descriptor.courseid;
        this.selectors = {
            COURSE_PROGRESS: `courseprogress`,
            PROGRESS_PERCENTAGE: `progresspercentage`,
            PROGRESS_BAR: `.progress-bar`,
        };
    }

    /**
     * Static method to create a component instance.
     *
     * @param {string} target the DOM main element or its ID
     * @param {object} selectors optional css selector overrides
     * @param {object} params additional params
     * @return {Component}
     */
    static init(target, selectors, params) {
        const reactiveCourseEditor = getCurrentCourseEditor();
        return new this({
            element: document.querySelector(target),
            selectors,
            userid: params.userid,
            courseid: params.courseid,
            reactive: reactiveCourseEditor
        });
    }

    /**
     * Watch for changes to the activity completion state.
     *
     * @return {Array} A list of watchers.
     */
    getWatchers() {
        return [
            {watch: `cm.completionstate:updated`, handler: this.updateProgressBarValues},
            {watch: `cm:created`, handler: this.updateProgressBarValues},
            {watch: `cm:deleted`, handler: this.updateProgressBarValues},
        ];
    }

    /**
     * Updates the values of the progress bar in Snap's course TOC.
     */
    updateProgressBarValues() {
        const selectors = this.selectors;
        Ajax.call([{
            methodname: 'theme_snap_update_course_toc_progressbar',
            args: {
                userid: this.userid,
                courseid: this.courseid,
            },
            done: function(response) {
                document.getElementById(selectors.COURSE_PROGRESS)
                    .textContent = response.courseprogress;
                document.getElementById(selectors.PROGRESS_PERCENTAGE)
                    .textContent = `${response.progresspercentage}%`;
                document.querySelector(selectors.PROGRESS_BAR)
                    .setAttribute('aria-valuenow', response.progresspercentage);
                document.querySelector(selectors.PROGRESS_BAR)
                    .style.width = `${response.progresspercentage}%`;
            }
        }]);
    }
}
