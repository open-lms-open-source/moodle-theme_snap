/* eslint-disable no-trailing-spaces */
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
 * JavaScript for the Snap theme sidebar menu functionality
 *
 * @module     theme_snap/sidebar_menu
 * @copyright  2024 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    SIDEBAR: '#snap-sidebar-menu',
    TRIGGER: '.snap-sidebar-menu-trigger',
    TRIGGER_ICON: '.snap-sidebar-menu-trigger i',
    HEADER: 'header',
    DRAWER_BUTTON: '.snap-sidebar-menu-item[data-activeselector]',
    MESSAGES_POPOVER: '[data-region="popover-region-messages"]',
    CLOSE_DRAWER_BUTTON: '[data-action="closedrawer"]',
    SIDEBAR_MENU_ITEM: '.snap-sidebar-menu-item',
};

const CLASSES = {
    CUSTOM_MENU_ITEM: 'custom-menu-item',
    SHOW: 'show',
    ACTIVE: 'active',
    COLLAPSED: 'collapsed',
    ROTATE: 'rotate-180'
};

/**
 * Toggle sidebar menu visibility and update its position
 */
const toggleSidebar = () => {
    const sidebar = document.querySelector(SELECTORS.SIDEBAR);
    const icon = document.querySelector(SELECTORS.TRIGGER_ICON);
    const isClosing = sidebar.classList.contains(CLASSES.SHOW);

    sidebar.classList.toggle(CLASSES.SHOW);
    icon.classList.toggle(CLASSES.ROTATE);
    updateSidebarPosition();
    
    // If we're closing the sidebar, close any open drawers
    if (isClosing) {
        closeAllDrawers();
    }
};

/**
 * Update sidebar position based on header height
 */
const updateSidebarPosition = () => {
    const sidebar = document.querySelector(SELECTORS.SIDEBAR);
    const header = document.querySelector(SELECTORS.HEADER);

    if (!sidebar || !header) {
        return;
    }

    const headerRect = header.getBoundingClientRect();
    sidebar.style.top = `${headerRect.bottom}px`;
    sidebar.style.height = `calc(100vh - ${headerRect.bottom}px)`;
};

/**
 * Handle drawer button clicks
 * @param {Event} e - The event object
 */
const handleDrawerButtonClick = (e) => {
    const button = e.target.closest(SELECTORS.DRAWER_BUTTON);
    if (!button) {
        return;
    }

    const activeSelector = button.dataset.activeselector;
    if (!activeSelector) {
        return;
    }

    // Check if the drawer is being opened
    setTimeout(() => {
        const activeElements = document.querySelectorAll(activeSelector);
        const isActive = Array.from(activeElements).some(el =>
            el.classList.contains(CLASSES.SHOW) ||
            el.classList.contains(CLASSES.ACTIVE) ||
            !el.classList.contains(CLASSES.COLLAPSED) // Consider not collapsed as active
        );

        if (isActive) {
            // If this drawer is being opened, close others
            closeOtherDrawers(activeSelector, button);
            button.classList.add(CLASSES.ACTIVE);
        } else {
            button.classList.remove(CLASSES.ACTIVE);
        }
    }, 50); // Small delay to allow the drawer state to update
};

/**
 * Close all active drawers except the one matching the given selector
 * @param {string} currentSelector - The selector for the drawer to keep open
 * @param {Element} currentButton - The button that was clicked
 */
const closeOtherDrawers = (currentSelector, currentButton) => {
    const drawerButtons = document.querySelectorAll(SELECTORS.DRAWER_BUTTON);

    drawerButtons.forEach(button => {
        if (button === currentButton) {
            return;
        }

        const activeSelector = button.dataset.activeselector;
        if (!activeSelector || activeSelector === currentSelector) {
            return;
        }

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
};

/**
 * Close all active drawers
 */
const closeAllDrawers = () => {
    const drawerButtons = document.querySelectorAll(SELECTORS.DRAWER_BUTTON);
    
    drawerButtons.forEach(button => {
        const activeSelector = button.dataset.activeselector;
        if (!activeSelector) {
            return;
        }
        
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
};

/**
 * Handle messages popover click
 * @param {Event} e - The event object
 */
const handleMessagesPopoverClick = (e) => {
    const sidebarItem = e.currentTarget.closest(SELECTORS.SIDEBAR_MENU_ITEM);
    if (sidebarItem) {
        const isCollapsed = e.currentTarget.classList.contains(CLASSES.COLLAPSED);
        if (isCollapsed) {
            e.currentTarget.classList.remove(CLASSES.COLLAPSED);
        } else {
            e.currentTarget.classList.add(CLASSES.COLLAPSED);
        }
    }
};

/**
 * Handle close drawer button clicks
 */
const handleCloseDrawerClick = () => {
    // Remove active classes from all drawer buttons
    document.querySelectorAll(SELECTORS.DRAWER_BUTTON).forEach(button => {
        button.classList.remove(CLASSES.ACTIVE);
    });
    
    // Add collapsed class to messages popover if it's open
    const messagesPopover = document.querySelector(SELECTORS.MESSAGES_POPOVER);
    if (messagesPopover && !messagesPopover.classList.contains(CLASSES.COLLAPSED)) {
        messagesPopover.classList.add(CLASSES.COLLAPSED);
    }
};

/**
 * Setup all event listeners
 */
const setupEventListeners = () => {
    const trigger = document.querySelector(SELECTORS.TRIGGER);
    if (trigger) {
        trigger.addEventListener('click', toggleSidebar);
    }

    window.addEventListener('resize', updateSidebarPosition);
    window.addEventListener('scroll', updateSidebarPosition);

    // Add click event listeners to drawer buttons
    document.querySelectorAll(SELECTORS.DRAWER_BUTTON).forEach(button => {
        button.addEventListener('click', handleDrawerButtonClick);
    });
    
    // Add click event listener to messages popover
    const messagesPopover = document.querySelector(SELECTORS.MESSAGES_POPOVER);
    if (messagesPopover) {
        messagesPopover.addEventListener('click', handleMessagesPopoverClick);
    }
    
    // Add click event listeners to elements with data-action="closedrawer"
    document.querySelectorAll(SELECTORS.CLOSE_DRAWER_BUTTON).forEach(element => {
        element.addEventListener('click', handleCloseDrawerClick);
    });
};

/**
 * Initialize the sidebar menu functionality
 */
export const init = () => {
    setupEventListeners();
    updateSidebarPosition();
    
    // Open the sidebar by default
    toggleSidebar();
};
