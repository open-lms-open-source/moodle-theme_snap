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

import ajax from "core/ajax";

/**
 * Ensures that all course index links have a title attribute for accessibility.
 *
 * @param {HTMLElement|Document} root - The root node to search for course index links.
 */
const injectTitles = (root) => {
    root.querySelectorAll('a.courseindex-link, a.aalink.stretched-link').forEach((link) => {
        if (!link.hasAttribute('title')) {
            const text = link.textContent.trim();
            if (text) {
                link.setAttribute('title', text);
            }
        }
    });
};

/**
 * Processes a newly added node by injecting icons and titles if applicable.
 *
 * @param {Node} node - The node added to the DOM.
 */
const processNode = (node) => {
    if (node.nodeType !== 1) {
        return;
    }
    injectTitles(node);
};

const getCourseState = async() => {

    const courseStateData = await ajax.call([{
        methodname: 'core_courseformat_get_state',
        args: {
            courseid: M.cfg.courseId,
        }
    }])[0];
    return JSON.parse(courseStateData);
};

/**
 * Initializes the course index adjustments.
 *
 * - Adds missing title attributes to links.
 * - Observes changes in the course index and applies the same adjustments to new nodes.
 */
export const init = () => {
    injectTitles(document);

    const target = document.querySelector('#courseindex');
    if (target) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                m.addedNodes.forEach(processNode);
            });
            getCourseState().then(courseState => {
                const sections = document.querySelectorAll('#courseindex-content .courseindex-section');
                const currentSectionId = courseState.section.filter(el => el.current)[0]?.id;
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
