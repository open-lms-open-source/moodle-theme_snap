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

    // Core renders the subpanel menu item as a direct dropdown item child, see core/local/action_menu/subpanel.
    const SUBPANEL_MENU_ITEM = '.dropdown-subpanel > .dropdown-item';

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
            item.classList.contains('modtype_lightboxgallery')
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
            const parent = menu.closest('.dropdown-subpanel');
            if (parent) {
                parent.classList.toggle('content-displayed', open);
                const menuItem = parent.querySelector(SUBPANEL_MENU_ITEM);
                if (menuItem) {
                    menuItem.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
            }
        };

        const isMenuOpen = function(menu) {
            return menu.classList.contains('show');
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
            document.querySelectorAll('.dropdown-subpanel-content.show').forEach(closeMenu);
        };

        /**
         * Returns the resource card subpanel menu item the event was raised on, if any.
         *
         * @param {Event} e
         * @returns {HTMLElement|null} The subpanel menu item, or null when the event is not on a resource card.
         */
        const resourceCardMenuItem = function(e) {
            const menuItem = e.target.closest(SUBPANEL_MENU_ITEM);
            if (!menuItem) {
                return null;
            }
            const wrapper = menuItem.closest('li.activity-wrapper');
            if (!wrapper || !isSnapResourceCard(wrapper)) {
                return null;
            }
            return menuItem;
        };

        // Cards open their subpanels on click, so core's hover and focus previews are suppressed. Leaving mouseout
        // and focusin active would close the subpanel again as soon as the cursor travels towards it.
        ['mouseover', 'mouseout', 'focusin'].forEach(function(eventName) {
            courseContent.addEventListener(eventName, function(e) {
                if (resourceCardMenuItem(e)) {
                    e.stopImmediatePropagation();
                }
            }, true);
        });

        // Handle click toggle
        courseContent.addEventListener('click', function(e) {
            const toggle = resourceCardMenuItem(e);
            if (!toggle) {
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

            const isOpen = isMenuOpen(menu);
            closeAllMenusInWrapper(toggle.closest('li.activity-wrapper'), menu);
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
            const openMenus = document.querySelectorAll('.dropdown-subpanel-content.show');
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