/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    var initialized = false;

    /**
     * Determines if an item is a Snap resource card.
     * @param {HTMLElement} item
     * @returns {boolean} True if the item should be treated as a resource card.
     */
    var isSnapResourceCard = function(item) {
        if (item.classList.contains('modtype_resource')) {
            return !item.querySelector('.snap-resource-figure');
        } else if (item.classList.contains('modtype_folder')) {
            return !!item.querySelector('.activity-name-area');
        } else if (
            item.classList.contains('modtype_url') ||
            item.classList.contains('modtype_imscp') ||
            item.classList.contains('modtype_lightboxgallery') ||
            item.classList.contains('modtype_scorm')
        ) {
            return true;
        }
        return false;
    };

    /**
     * Initializes the single-click behavior of Snap dropdown menus.
     */
    var initResourceCardDropdownBehavior = function() {
        const body = document.body;

        if (!body.classList.contains('snap-resource-card') ||
            !(body.classList.contains('format-weeks') || body.classList.contains('format-topics')) ||
            document.querySelector('.moodle-dialogue-base') ||
            document.querySelector('.modal-dialog')) {
            return;
        }

        const courseContent = document.querySelector('.course-content');
        if (!courseContent) {
            return;
        }

        const BREAKPOINT_MIN = 600;
        const containerWidth = courseContent.offsetWidth;
        if (containerWidth < BREAKPOINT_MIN) {
            return;
        }

        // Only initialize listeners once to avoid duplicates.
        if (initialized) {
            return;
        }
        initialized = true;

        const setMenuState = function(menu, open) {
            menu.classList.toggle('show', open);
            menu.setAttribute('aria-hidden', String(!open));
            menu.dataset.open = open ? 'true' : 'false';
            const parent = menu.closest('.dropdown-subpanel');
            if (parent) {
                parent.classList.toggle('content-displayed', open);
            }
        };

        const closeMenu = function(menu) {
            setMenuState(menu, false);
        };

        const openMenu = function(menu) {
            setMenuState(menu, true);
        };

        const closeAllMenusInWrapper = function(wrapper, exceptMenu) {
            wrapper.querySelectorAll('.dropdown-subpanel-content').forEach(function(menu) {
                if (menu !== exceptMenu) {
                    closeMenu(menu);
                }
            });
        };

        const closeAllMenus = function() {
            document.querySelectorAll('.dropdown-subpanel-content[data-open="true"]').forEach(closeMenu);
        };

        // Disable the hover
        courseContent.addEventListener('mouseover', function(e) {
            const trigger = e.target.closest('.dropdown-subpanel > .dropdown-item');
            if (!trigger) {
                return;
            }
            const wrapper = trigger.closest('li.activity-wrapper');
            if (wrapper && isSnapResourceCard(wrapper)) {
                e.stopImmediatePropagation();
            }
        }, true);

        // Handle click toggle
        courseContent.addEventListener('click', function(e) {
            const toggle = e.target.closest('[data-toggle="dropdown-subpanel"]');
            if (!toggle) {
                return;
            }

            const wrapper = toggle.closest('li.activity-wrapper');
            if (!wrapper || !isSnapResourceCard(wrapper)) {
                return;
            }

            const subpanel = toggle.closest('.dropdown-subpanel');
            const menu = subpanel && subpanel.querySelector('.dropdown-subpanel-content');
            if (!menu) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const isOpen = menu.dataset.open === 'true';
            closeAllMenusInWrapper(wrapper, menu);
            if (isOpen) {
                closeMenu(menu);
            } else {
                openMenu(menu);
            }
        }, true);

        // Close menus when clicking outside
        courseContent.addEventListener('click', function(e) {
            if (e.target.closest('.dropdown-subpanel')) {
                return;
            }
            const openMenus = document.querySelectorAll('.dropdown-subpanel-content[data-open="true"]');
            if (openMenus.length === 0) {
                return;
            }
            closeAllMenus();
        }, true);
    };

    return {
        /**
         * Initialize activity cards behavior.
         */
        init: function() {
            initResourceCardDropdownBehavior();
        }
    };
});