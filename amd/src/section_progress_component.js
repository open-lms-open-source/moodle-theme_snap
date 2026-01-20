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
 * The Snap course index section progress component.
 *
 * @module     theme_snap/section_progress_component
 * @class      theme_snap/section_progress_component
 */

import {BaseComponent} from 'core/reactive';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';
import {getSectionProgress} from './repository';
import log from 'core/log';

export default class extends BaseComponent {

    /**
     * Constructor hook.
     *
     * @param {Object} descriptor
     */
    create(descriptor) {
        this.userid = descriptor.userid;
        this.courseid = descriptor.courseid;
        // Default query selectors.
        this.selectors = {
            PROGRESS_WRAPPER: '.snap-section-progress-wrapper',
            PROGRESS_COUNT: '.snap-section-progress-count',
            PROGRESS_ICON: '.snap-section-progress-icon',
        };
        // Default CSS classes.
        this.classes = {
            PROGRESS_COMPLETE: 'snap-section-progress-complete',
        };
        // Default data attributes.
        this.attributes = {
            SECTION_ID: 'data-sectionid',
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
        const element = document.querySelector(target) || document.body;

        return new this({
            element: element,
            selectors,
            userid: params.userid,
            courseid: params.courseid,
            reactive: reactiveCourseEditor
        });
    }

    /**
     * Initial state ready method.
     */
    stateReady() {
        // Load progress for all sections when the component is ready.
        this._loadAllSectionsProgress();
    }

    /**
     * Component watchers.
     *
     * @returns {Array} A list of watchers.
     */
    getWatchers() {
        return [
            {watch: `cm.completionstate:updated`, handler: this._onCompletionChanged},
            {watch: `cm:created`, handler: this._onCompletionChanged},
            {watch: `cm:deleted`, handler: this._onCompletionChanged},
            {watch: `cm.sectionid:updated`, handler: this._onSectionIdChanged},
        ];
    }

    /**
     * Handle completion changes and update affected sections.
     *
     * @param {Object} param the watcher parameter
     */
    _onCompletionChanged(param) {
        // Contains the cm data object with id and sectionid.
        const cm = param.element;
        if (cm && cm.sectionid) {
            this._updateSectionProgress(cm.sectionid);
        }
    }

    /**
     * Handle section ID changes (when a module is moved between sections).
     *
     * @param {Object} param the watcher parameter
     */
    _onSectionIdChanged(param) {
        const cm = param.element;
        if (!cm || !cm.id) {
            return;
        }

        // When a module is moved between sections, we need to update progress for both
        this._loadAllSectionsProgress();
    }


    /**
     * Load progress for all sections using a single bulk call.
     * @private
     */
    _loadAllSectionsProgress() {
        getSectionProgress(0, this.userid, this.courseid)
            .then((response) => {
                if (!response || !response.sections) {
                    return;
                }

                // Update all sections with the bulk data.
                response.sections.forEach((sectionData) => {
                    this._updateSectionProgressFromData(sectionData.sectionid, sectionData);
                });
            })
            .catch((error) => {
                log.warn('Failed to load section progress:', error);
            });
    }

    /**
     * Update the progress indicator for a specific section.
     *
     * @param {Number} sectionid The section ID
     * @private
     */
    _updateSectionProgress(sectionid) {
        getSectionProgress(sectionid, this.userid, this.courseid)
            .then((response) => {
                if (!response || !response.sections || response.sections.length === 0) {
                    return;
                }

                // Extract the single section data from the response.
                const sectionData = response.sections[0];
                this._updateSectionProgressFromData(sectionid, sectionData);
            })
            .catch((error) => {
                log.warn('Failed to load section progress:', error);
            });
    }

    /**
     * Update the UI for a section with the provided progress data.
     *
     * @param {Number} sectionid The section ID
     * @param {Object} sectionData The progress data for the section
     * @private
     */
    _updateSectionProgressFromData(sectionid, sectionData) {
        const wrapper = document.querySelector(
            `${this.selectors.PROGRESS_WRAPPER}[${this.attributes.SECTION_ID}="${sectionid}"]`
        );
        if (!wrapper) {
            return;
        }

        if (!sectionData.hasprogress) {
            wrapper.style.display = 'none';
            wrapper.classList.remove(this.classes.PROGRESS_COMPLETE);
            return;
        }

        // Show the progress indicator.
        wrapper.style.display = 'block';

        const countElement = wrapper.querySelector(this.selectors.PROGRESS_COUNT);
        if (countElement) {
            countElement.textContent = `${sectionData.completed}/${sectionData.total}`;
        }

        const iconElement = wrapper.querySelector(this.selectors.PROGRESS_ICON);
        if (sectionData.iscomplete) {
            if (iconElement) {
                iconElement.style.display = 'inline-block';
            }
            wrapper.classList.add(this.classes.PROGRESS_COMPLETE);
        } else {
            if (iconElement) {
                iconElement.style.display = 'none';
            }
            wrapper.classList.remove(this.classes.PROGRESS_COMPLETE);
        }
    }
}