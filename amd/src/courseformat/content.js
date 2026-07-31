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
import Section from 'theme_snap/courseformat/content/section';
import CmItem from 'core_courseformat/local/content/section/cmitem';
import Fragment from 'core/fragment';
import Pending from 'core/pending';
import Config from 'core/config';
import Templates from 'core/templates';
import {debounce} from 'core/utils';

class SnapCmItem extends CmItem {
    /**
     * Override configDragDrop to keep activities static when the real Edit Mode is off.
     *
     * The front page simulates edit mode so AJAX control menu actions work with the
     * Edit Mode toggle off, but activities must not be draggable then.
     *
     * @param {number} cmid course module id
     */
    configDragDrop(cmid) {
        if (!document.body.classList.contains('editing')) {
            this.id = cmid;
            return;
        }
        super.configDragDrop(cmid);
    }
}

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
        let element = document.querySelector(target);
        if (!element) {
            element = document.getElementById(target);
        }

        if (!element) {
            return null;
        }

        // If already initialized, return.
        if (element?.dataset.initialized) {
            return null;
        }

        // Mark the element as initialized to avoid re-start of reactive component.
        element.dataset.initialized = true;
        return new Component({
            element: element,
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
            this.selectors.SECTION = ".single-section > ul li[data-for='section']";
        }
    }
    /**
     * Override _scrollHandler to do nothing, so it does not make Weird jumps in the course while navigating.
     */
    _scrollHandler() {
        return;
    }

    _indexContents() {
        // Let's use our Snap Section to use our CSS selectors.
        this._scanIndex(
            this.selectors.SECTION,
            this.sections,
            (item) => {
                return new Section(item);
            }
        );

        // Using Snap CmItem to disable drag and drop when the real Edit Mode is off.
        this._scanIndex(
            this.selectors.CM,
            this.cms,
            (item) => {
                return new SnapCmItem(item);
            }
        );
    }
    /**
     * Override _getDebouncedReloadCm, so it calls theme_snap_courseformat_output_fragment_cmitem instead of core one.
     * Only for FrontPage.
     *
     * Generate or get a reload CM debounced function.
     * @param {Number} cmId
     * @returns {Function} the debounced reload function
     */
    _getDebouncedReloadCm(cmId) {
        const onHomePage = document.getElementById('page-site-index');
        if (!onHomePage) {
            return super._getDebouncedReloadCm(cmId);
        }
        const pendingKey = `courseformat/content:reloadCm_${cmId}`;
        let debouncedReload = this.debouncedReloads.get(pendingKey);
        if (debouncedReload) {
            return debouncedReload;
        }
        const reload = () => {
            const pendingReload = new Pending(pendingKey);
            this.debouncedReloads.delete(pendingKey);
            const cmitem = this.getElement(this.selectors.CM, cmId);
            if (!cmitem) {
                return pendingReload.resolve();
            }
            const promise = Fragment.loadFragment(
                'theme_snap',
                'cmitem',
                Config.courseContextId,
                {
                    id: cmId,
                    courseid: Config.courseId,
                    sr: this.reactive?.sectionReturn ?? null,
                    pagesectionid: this.reactive?.pageSectionId ?? null,
                }
            );
            promise.then((html, js) => {
                // Other state change can reload the CM or the section before this one.
                if (!document.contains(cmitem)) {
                    pendingReload.resolve();
                    return false;
                }
                Templates.replaceNode(cmitem, html, js);
                this._indexContents();
                pendingReload.resolve();
                return true;
            }).catch(() => {
                pendingReload.resolve();
            });
            return pendingReload;
        };
        debouncedReload = debounce(
            reload,
            200,
            {
                cancel: true, pending: true
            }
        );
        this.debouncedReloads.set(pendingKey, debouncedReload);
        return debouncedReload;
    }
}