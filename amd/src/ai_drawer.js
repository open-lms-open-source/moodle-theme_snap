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
 * @author    Julian Tovar <julian.tovar@openlms.net>
 * @copyright Copyright (c) 2025 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    AIDRAWER_CLOSE_BUTTON: '#ai-drawer button#ai-drawer-close',
    GOTO_TOP_LINK: '#goto-top-link',
    SIDEBAR: '#snap-sidebar-menu',
    HEADER: 'header',
    DRAWER_BUTTONS: '.snap-sidebar-menu-item[data-activeselector], ' +
        '.drawer-toggler.drawer-left-toggle [data-activeselector]',
    AIDRAWER_BUTTON: '.ai-course-summarise-controls button[aria-controls="ai-drawer"]',
    AIDRAWER: 'div#ai-drawer',
    NAV_UNPINNED: '#mr-nav.headroom--unpinned',
};

const CLASSES = {
    CUSTOM_MENU_ITEM: 'custom-menu-item',
    SHOW: 'show',
    ACTIVE: 'active',
    COLLAPSED: 'collapsed',
};

const POPOVERS_AND_DRAWERS = {
    CLICKABLE_SELECTORS: [
        '#user-menu-toggle', // User menu
        '#nav-intellicart-popover-container', // Intellicart
        '#nav-notification-popover-container', // Notifications
        '#local-accessibility-buttoncontainer', // Accessibility
        '.snap-sidebar-menu-item[data-activeselector="#admin-menu-trigger.active"]', // Admin gear
        '.snap-sidebar-menu-item[data-activeselector="#theme_snap-drawers-blocks.show"]', // Blocks drawer
        '.snap-sidebar-menu-item[data-activeselector="#snap_feeds_side_menu_trigger.active"]', // Snap feeds
    ]
};

export const init = () => {
    setupClickHandlers();
    setupAIDrawerDynamicLayout();
    setupEventListeners();
};

/**
 * Setup the AI drawer position dynamically if needed, and be aware of other drawer, popover and dropdown usage by the user.
 */
const setupAIDrawerDynamicLayout = () => {
    // Don't spend time if we don't need to set up anything at all.
    const AIDrawerButton = document.querySelector(SELECTORS.AIDRAWER_BUTTON);
    const header = document.querySelector(SELECTORS.HEADER);
    if (AIDrawerButton && header) {
        // Set the position of the AISummary on page load.
        const AIDrawer = document.querySelector(SELECTORS.AIDRAWER);
        if (AIDrawer) {
            repositionHeightAndTopPosition(header, AIDrawer);
        }
        // Set the position of the AISummary if the user scrolls.
        window.addEventListener('scroll', function() {
            const isNavUnpinned = document.querySelector(SELECTORS.NAV_UNPINNED);
            const AIDrawer = document.querySelector(SELECTORS.AIDRAWER);
            if (AIDrawer) {
                if (isNavUnpinned) {
                    AIDrawer.style.top = '0px';
                    AIDrawer.style.height = '100vh';
                } else {
                    repositionHeightAndTopPosition(header, AIDrawer);
                }
            }
        });
    }
};

/**
 * Gets the visible height and the top position of a given element.
 * @param {mixed} reference The element we take as reference for the visible height and top position.
 * @param {mixed} positionedElement The element we are repositioning.
 */
function repositionHeightAndTopPosition(reference, positionedElement) {
    const elementRect = reference.getBoundingClientRect();
    const visibleHeight = window.innerHeight;
    const topPosition = Math.max(0, elementRect.bottom);
    positionedElement.style.top = `${topPosition}px`;
    positionedElement.style.height = `${visibleHeight - topPosition}px`;
}

/**
 * Close the AI drawer if active
 */
const closeAIDrawer = () => {
    repositionGotoTopLink();
    const AIDrawerButton = document.querySelector(SELECTORS.AIDRAWER_CLOSE_BUTTON);
    if (AIDrawerButton) {
        AIDrawerButton.click();
        AIDrawerButton.classList.remove(CLASSES.ACTIVE);
    }
};

/**
 * Setup the click event handlers for all elements involved (namely, the AI drawer, the other drawers, popovers, dropdowns).
 */
const setupClickHandlers = () => {
    let isClosingDrawers = false;

    const checkAndCloseAIDrawer = () => {
        if (isClosingDrawers) {
            return false;
        }

        let isOpenAIDrawer = false;
        const AIDrawer = document.querySelector(SELECTORS.AIDRAWER);
        if (AIDrawer.classList.contains(CLASSES.SHOW)) {
            isOpenAIDrawer = true;
        }

        if (isOpenAIDrawer) {
            // Set flag to prevent recursive calls
            isClosingDrawers = true;
            // Close all drawers first
            closeAIDrawer();
            isClosingDrawers = false;
            return true;
        }

        return false;
    };

    POPOVERS_AND_DRAWERS.CLICKABLE_SELECTORS.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            // Handle mouse clicks
            element.addEventListener('click', () => {
                checkAndCloseAIDrawer();
            }, true);

            // Handle keyboard events (Enter key)
            element.addEventListener('keydown', (e) => {
                // Check if the Enter key was pressed
                if (e.key === 'Enter' || e.keyCode === 13) {
                    checkAndCloseAIDrawer();
                }
            }, true);
        }
    });

    /**
     * Close all active drawers except the one matching the given selector
     */
    const checkAndCloseOtherDrawers = () => {
        const drawerButtons = document.querySelectorAll(SELECTORS.DRAWER_BUTTONS);
        drawerButtons.forEach(button => {
            const activeSelector = button.dataset.activeselector;
            const activeElements = document.querySelectorAll(activeSelector);
            const isActive = Array.from(activeElements).some(el =>
                el.classList.contains(CLASSES.SHOW) ||
                el.classList.contains(CLASSES.ACTIVE) ||
                !el.classList.contains(CLASSES.COLLAPSED) // Consider not collapsed as active
            );

            if (isActive) {
                const isCustomContent = button.classList.contains(CLASSES.CUSTOM_MENU_ITEM);
                if (isCustomContent) {
                    const clickableElement = button.querySelector('a, button') || button;
                    clickableElement.click();
                } else {
                    button.click();
                }
                button.classList.remove(CLASSES.ACTIVE);
            }
        });
        setTimeout(() => {
            repositionGotoTopLink();
        }, 50); // Small delay to allow the drawer state to update
    };

    const AIDrawerButton = document.querySelector(SELECTORS.AIDRAWER_BUTTON);
    AIDrawerButton.addEventListener('click', () => {
        checkAndCloseOtherDrawers();
    }, true);

    // Handle keyboard events (Enter key)
    AIDrawerButton.addEventListener('keydown', (e) => {
        // Check if the Enter key was pressed
        if (e.key === 'Enter' || e.keyCode === 13) {
            checkAndCloseOtherDrawers();
        }
    }, true);
};

/**
 * Setup needed event listeners
 */
const setupEventListeners = () => {
    window.addEventListener('scroll', () => {
        // Add a small delay to avoid performance issues with rapid scroll events
        setTimeout(() => {
            const header = document.querySelector(SELECTORS.HEADER);
            const AIDrawer = document.querySelector(SELECTORS.AIDRAWER);

            repositionHeightAndTopPosition(header, AIDrawer);

            // Check if Go to Top link is visible and reposition it if needed
            const gotoTopLink = document.querySelector(SELECTORS.GOTO_TOP_LINK);
            if (gotoTopLink) {
                const computedStyle = window.getComputedStyle(gotoTopLink);
                if (computedStyle.visibility === 'visible') {
                    repositionGotoTopLink();
                }
            }
        }, 50);
    });
};

/**
 * Reposition the "Go to Top" button based on open drawers
 */
const repositionGotoTopLink = () => {
    const gotoTopLink = document.querySelector(SELECTORS.GOTO_TOP_LINK);
    if (!gotoTopLink) {
        return;
    }

    gotoTopLink.style.marginRight = '';

    // Check if sidebar is showing
    const sidebar = document.querySelector(SELECTORS.SIDEBAR);
    const isSidebarShowing = sidebar && sidebar.classList.contains(CLASSES.SHOW);

    // Only proceed if sidebar is showing
    if (isSidebarShowing) {
        const AIDrawer = document.querySelector(SELECTORS.AIDRAWER);

        if (AIDrawer) {
            // Get the first active drawer found for this selector type
            if (AIDrawer.offsetWidth > 0 && !AIDrawer.classList.contains('drawer-left')) {
                // Get the width of the drawer
                const drawerWidth = AIDrawer.offsetWidth;
                // Add margin to position the link to the left of the drawer
                gotoTopLink.style.marginRight = `${drawerWidth}px`;
                return; // Exit after finding the first open drawer
            }
        }
    }
};
