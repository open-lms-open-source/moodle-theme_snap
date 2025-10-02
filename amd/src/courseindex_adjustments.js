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

import cfg from 'core/config';

/**
 * Build the URL for a module icon.
 *
 * @param {string} modname - The short name of the module (e.g. quiz, forum).
 * @returns {string} The absolute URL of the icon.
 */
const getIconUrl = (modname) => {
    return `${cfg.wwwroot}/theme/image.php/${cfg.theme}/${modname}/${cfg.themerev}/monologo?filtericon=1`;
};

/**
 * Injects icons into activity containers that do not already have one.
 *
 * @param {HTMLElement|Document} root - The root node to search for activity containers.
 */
const injectIcons = (root) => {
    root.querySelectorAll('.activityiconcontainer[data-cmid]').forEach((container) => {
        if (container.dataset.iconInjected) {
            return;
        }
        container.dataset.iconInjected = '1';

        const link = container.closest('li.courseindex-item')?.querySelector('a.courseindex-link');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href') || '';
        const match = href.match(/\/mod\/([^/]+)\//);
        if (!match) {
            return;
        }
        const modname = match[1];
        const iconurl = getIconUrl(modname);

        const img = document.createElement('img');
        img.src = iconurl;
        img.alt = `${modname} icon`;
        img.className = `${modname} icon activityicon`;
        container.appendChild(img);
    });
};

/**
 * Ensures that all course index links have a title attribute for accessibility.
 *
 * @param {HTMLElement|Document} root - The root node to search for course index links.
 */
const injectTitles = (root) => {
    root.querySelectorAll('a.courseindex-link').forEach((link) => {
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
    injectIcons(node);
    injectTitles(node);
};

/**
 * Initializes the course index adjustments.
 *
 * - Injects icons into existing activities.
 * - Adds missing title attributes to links.
 * - Observes changes in the course index and applies the same adjustments to new nodes.
 */
export const init = () => {
    injectIcons(document);
    injectTitles(document);

    const target = document.querySelector('#courseindex');
    if (target) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                m.addedNodes.forEach(processNode);
            });
        });
        observer.observe(target, {childList: true, subtree: true});
    }
};
