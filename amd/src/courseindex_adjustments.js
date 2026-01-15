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
 * Additional settings in the course index.
 *
 * @module theme_snap/courseindex_adjustments
 * @copyright  Copyright (c) 2025 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import snapsection from 'theme_snap/section_asset_management';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

const CLASSES = {
    FRONTIER_TRANSITION: 'toc-frontier-transition',
    PRESCROLL: 'sticky-pre-scroll',
    STICKY_STATES: [
        'sticky-no-header-no-footer',
        'sticky-no-header-yes-footer',
        'sticky-yes-header-no-footer',
        'sticky-yes-header-yes-footer',
        'sticky-under-pinned',
    ],
    STICKY_RESIZE: 'sticky-resize',
};

const SELECTORS = {
    COURSE_FOOTER: 'moodle-footer',
    COURSE_WRAPPER: 'snap-course-wrapper',
    HEADER: 'header#mr-nav',
    NAV_PINNED: '#mr-nav.headroom--pinned',
    NAV_UNPINNED: '#mr-nav.headroom--unpinned',
    PAGE_HEADER: 'page-header',
    SNAP_COURSE_FOOTER: 'snap-course-footer',
    TOCDRAWER: 'theme_boost-drawers-courseindex',
    TOCDRAWER_CONTROLS: '#theme_boost-drawers-courseindex > .drawerheader',
    TOCDRAWER_CONTENT: '#theme_boost-drawers-courseindex > .drawercontent',
};

/**
 * Get hidden TOC activities list from config.
 * @returns {number[]} Array of hidden course module IDs
 */
const getHiddenTocActivities = () => {
    const config = require('core/config');
    return config.hiddenTocActivities || [];
};

/**
 * Remove hidden activities from the course index DOM.
 */
const filterHiddenActivitiesFromDOM = () => {
    const hiddencmids = getHiddenTocActivities();
    if (!hiddencmids || hiddencmids.length === 0) {
        return;
    }

    const courseindex = document.querySelector('#courseindex');
    if (!courseindex) {
        return;
    }

    // Remove activities that are marked as hidden.
    // Course index activities use data-id and data-for="cm" attributes.
    hiddencmids.forEach(cmid => {
        const activityElement = courseindex.querySelector(`[data-id="${cmid}"][data-for="cm"]`);
        if (activityElement) {
            activityElement.remove();
        }
    });
};

/**
 * Initializes the course index adjustments.
 *
 * - Adds missing title attributes to links.
 * - Observes changes in the course index and applies the same adjustments to new nodes.
 */
export const init = () => {

    const reactiveCourseEditor = getCurrentCourseEditor();

    const target = document.querySelector('#courseindex');
    if (target) {
        // Filter hidden activities immediately and after DOM changes.
        filterHiddenActivitiesFromDOM();
        const observer = new MutationObserver(() => {
            let state = reactiveCourseEditor.state;

            snapsection.setNavigationObservers();
            // Filter hidden activities after DOM mutations.
            filterHiddenActivitiesFromDOM();
            const sections = document.querySelectorAll('#courseindex-content .courseindex-section');
            const currentSectionId = [...state.section.values()].find(el => el.current)?.id;
            sections.forEach(section => {
                if (currentSectionId === section.dataset.id) {
                    section.classList.add('current');
                    if (document.querySelector('body:not(.path-course-view-section)')) {
                        section.querySelector('.courseindex-item').classList.add('pageitem');
                    }
                } else {
                    section.classList.remove('current');
                    if (document.querySelector('body:not(.path-course-view-section)')) {
                        section.querySelector('.courseindex-item').classList.remove('pageitem');
                    }
                }
            });

            const sectionsInView = document.querySelectorAll('body:not(.path-course-view-section)' +
                ' #courseindex-content .courseindex-section');
            sectionsInView.forEach((section) => {
                if (section.classList.contains('current')) {
                    section.querySelector('.courseindex-item').classList.add('pageitem');
                } else {
                    section.querySelector('.courseindex-item').classList.remove('pageitem');
                }
            });
        });
        observer.observe(target, {childList: true, subtree: true});
    }
};

export const stickyTOCHandler = () => {
    const courseWrapper = document.getElementById(SELECTORS.COURSE_WRAPPER);
    let courseWrapperWidth = courseWrapper.getBoundingClientRect().width * 0.3 + 'px';
    const tocdrawer = document.getElementById(SELECTORS.TOCDRAWER);

    let moodleFooter = document.getElementById(SELECTORS.SNAP_COURSE_FOOTER);
    if (moodleFooter === null) {
        moodleFooter = document.getElementById(SELECTORS.COURSE_FOOTER);
    }

    const pageHeader = document.getElementById(SELECTORS.PAGE_HEADER);
    const header = document.querySelector(SELECTORS.HEADER);
    const tocdrawerControls = document.querySelector(SELECTORS.TOCDRAWER_CONTROLS);
    const tocdrawerContent = document.querySelector(SELECTORS.TOCDRAWER_CONTENT);
    if (tocdrawerControls && tocdrawerContent) {
        tocdrawerContent.prepend(tocdrawerControls);
    }
    tocdrawer.style.width = courseWrapperWidth;

    document.addEventListener('scroll', () => {
        setTimeout(() => {
            // Assume we are not at a frontier (if we are we will be dealt with later on).
            tocdrawer.classList.remove(CLASSES.FRONTIER_TRANSITION);

            const pageHeaderBottom = pageHeader.getBoundingClientRect().bottom;
            const pageFooterTop = moodleFooter.getBoundingClientRect().top;
            const mrnavHeight = header.getBoundingClientRect().height;
            const mrnavBotton = header.getBoundingClientRect().bottom;
            const isNavPinned = document.querySelector(SELECTORS.NAV_PINNED);
            const isNavUnpinned = document.querySelector(SELECTORS.NAV_UNPINNED);
            if (Math.abs(Math.floor(pageFooterTop) - window.innerHeight) <= 20 ||
                Math.abs(Math.floor(pageHeaderBottom) - mrnavHeight) <= 20) {
                tocdrawer.classList.add(CLASSES.FRONTIER_TRANSITION);
            }

            CLASSES.STICKY_STATES.forEach(state => {
                tocdrawer.classList.remove(state);
            });
            if (pageHeaderBottom < 0 && pageFooterTop > window.innerHeight) {
                tocdrawer.classList.add('sticky-no-header-no-footer');
                tocdrawer.style.top = '0';
                if (isNavPinned || (!isNavPinned && !isNavUnpinned)) {
                    tocdrawer.style.top = 'auto';
                }
            } else if (pageHeaderBottom < 0 && pageFooterTop <= window.innerHeight) {
                tocdrawer.classList.add('sticky-no-header-yes-footer');
                tocdrawer.style.top = 'auto';
            } else if (pageHeaderBottom >= 0 && pageFooterTop > window.innerHeight) {
                tocdrawer.classList.add('sticky-yes-header-no-footer');
                tocdrawer.style.top = '0';
                if (isNavPinned || (!isNavPinned && !isNavUnpinned)) {
                    if (pageHeaderBottom <= mrnavHeight) {
                        tocdrawer.classList.add('sticky-under-pinned');
                        tocdrawer.style.top = `${mrnavHeight}px`;
                    }
                }
            } else { // pageHeaderBottom >= 0 && pageFooterTop <= window.innerHeight
                tocdrawer.classList.add('sticky-yes-header-yes-footer');
                tocdrawer.style.top = 'auto';
            }

            // The defining equation for the height of a sticky TOC that responds to header and footer positions.
            tocdrawer.style.height = `${window.innerHeight - Math.max(0, pageHeaderBottom, mrnavBotton)
            - Math.max(0, window.innerHeight - pageFooterTop)}px`;
        }, 30);
    });

    window.addEventListener('resize', () => {
        const pageFooterTop = moodleFooter.getBoundingClientRect().top;
        if (pageFooterTop <= window.innerHeight) {
            tocdrawer.classList.add(CLASSES.STICKY_RESIZE);
        }
    });
};
