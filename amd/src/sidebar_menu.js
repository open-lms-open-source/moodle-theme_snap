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
    NAV_UNPINNED: '#mr-nav.headroom--unpinned',
    GOTO_TOP_LINK: '#goto-top-link',
    COURSE_TOC: '#course-toc',
};

const CLASSES = {
    CUSTOM_MENU_ITEM: 'custom-menu-item',
    SHOW: 'show',
    ACTIVE: 'active',
    COLLAPSED: 'collapsed',
    ROTATE: 'rotate-180',
    STATE_VISIBLE: 'state-visible',
    POSITIONING_OFFSCREEN: 'positioning-offscreen',
};

const DRAWERS = {
    SELECTORS: [
        '.drawer',
        '.block_settings.block',
        '#snap_feeds_side_menu',
        '.drawer:has(.message-app)'
    ],
    ACTIVE_SELECTORS: [
        '.drawer.show',
        '.block_settings.block.state-visible',
        '#snap_feeds_side_menu.state-visible',
        '.drawer:not(.hidden):has(.message-app)'
    ]
};

let lastScrollX = 0;

/**
 * Toggle sidebar menu visibility and update its position
 */
const toggleSidebar = () => {
    const sidebar = document.querySelector(SELECTORS.SIDEBAR);
    const icon = document.querySelector(SELECTORS.TRIGGER_ICON);
    const isClosing = sidebar.classList.contains(CLASSES.SHOW);

    sidebar.classList.toggle(CLASSES.SHOW);
    icon.classList.toggle(CLASSES.ROTATE);
    updateElementPositions();
    
    // If we're closing the sidebar, close any open drawers
    if (isClosing) {
        closeAllDrawers();
    }
};

/**
 * Update the position of UI elements relative to the header
 * @param {Array|string|null} selectors - CSS selector(s) for elements to update, or null for sidebar only
 */
const updateElementPositions = (selectors = null) => {
    const header = document.querySelector(SELECTORS.HEADER);
    if (!header) {
        return;
    }

    const headerRect = header.getBoundingClientRect();
    const visibleHeight = window.innerHeight;
    const topPosition = Math.max(0, headerRect.bottom);
    const isNavUnpinned = document.querySelector(SELECTORS.NAV_UNPINNED);
    
    const sidebar = document.querySelector(SELECTORS.SIDEBAR);
    if (sidebar) {
        if (isNavUnpinned) {
            sidebar.style.top = '0px';
            sidebar.style.height = '100vh';
        } else {
            sidebar.style.top = `${topPosition}px`;
            sidebar.style.height = `${visibleHeight - topPosition}px`;
        }
        
        // Remove positioning-offscreen class after positioning is complete
        // Add a small delay before removing the positioning-offscreen class
        setTimeout(() => {
            sidebar.classList.remove(CLASSES.POSITIONING_OFFSCREEN);
        }, 100);
    }

    if (selectors) {
        const selectorsArray = Array.isArray(selectors) ? selectors : [selectors];
        
        // Update each element's position
        selectorsArray.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            
            elements.forEach(element => {    
                if (isNavUnpinned) {
                    element.style.top = '0px';
                    element.style.height = '100vh';
                } else {
                    element.style.top = `${topPosition}px`;
                    element.style.height = `${visibleHeight - topPosition}px`;
                }
                
                // Ensure the element is visible within the viewport if it's active
                if (element.classList.contains(CLASSES.SHOW) || 
                    element.classList.contains(CLASSES.ACTIVE) || 
                    !element.classList.contains(CLASSES.COLLAPSED)) {
                    element.style.maxHeight = isNavUnpinned ? '100vh' : `${visibleHeight - topPosition}px`;
                }
            });
        });
    }
};

/**
 * Handle drawer button clicks
 * @param {Event} e - The event object
 */
const handleDrawerButtonClick = (e) => {
    setTimeout(() => {
        const button = e.target.closest(SELECTORS.DRAWER_BUTTON);
        repositionGotoTopLink();
        if (!button) {
            return;
        }

        const activeSelector = button.dataset.activeselector;
        if (!activeSelector) {
            return;
        }

        const activeElements = document.querySelectorAll(activeSelector);
        const isActive = Array.from(activeElements).some(
            (el) =>
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
    repositionGotoTopLink();
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
    repositionGotoTopLink();
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
    repositionGotoTopLink();
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
    repositionGotoTopLink();
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

    // Update both sidebar and drawer positions on resize and scroll
    window.addEventListener('resize', () => {
        updateElementPositions(DRAWERS.SELECTORS);
    });
    
    window.addEventListener('scroll', () => {
        // Add a small delay to avoid performance issues with rapid scroll events
        setTimeout(() => {
            updateElementPositions(DRAWERS.SELECTORS);
            
            // Check if Go to Top link is visible and reposition it if needed
            const gotoTopLink = document.querySelector(SELECTORS.GOTO_TOP_LINK);
            if (gotoTopLink) {
                const computedStyle = window.getComputedStyle(gotoTopLink);
                if (computedStyle.visibility === 'visible') {
                    repositionGotoTopLink();
                }
            }
            
            // Handle horizontal scrolling to control sticky elements (e.g. grader)
            toggleSidebarOnHorizontalScroll(window.scrollX);
        }, 50);
    });

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
    
    // Set up course TOC observer
    setupCourseTocObserver();
};

/**
 * Set up a MutationObserver to watch for changes to #course-toc
 */
const setupCourseTocObserver = () => {
    const courseToc = document.querySelector(SELECTORS.COURSE_TOC);
    if (courseToc) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (courseToc.classList.contains(CLASSES.STATE_VISIBLE)) {
                        // Close the sidebar when course TOC becomes visible
                        const sidebar = document.querySelector(SELECTORS.SIDEBAR);
                        const icon = document.querySelector(SELECTORS.TRIGGER_ICON);
                        
                        if (sidebar && sidebar.classList.contains(CLASSES.SHOW)) {
                            sidebar.classList.remove(CLASSES.SHOW);
                            if (icon) {
                                icon.classList.remove(CLASSES.ROTATE);
                            }
                            closeAllDrawers();
                            updateElementPositions();
                        }
                    }
                }
            });
        });
        observer.observe(courseToc, { attributes: true });
    }
};

/**
 * Initialize the sidebar menu functionality
 */
export const init = () => {
    setupEventListeners();
    updateElementPositions();
    
    // Update positions of all drawers
    updateElementPositions(DRAWERS.SELECTORS);
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
        // Check each drawer selector
        for (const selector of DRAWERS.ACTIVE_SELECTORS) {
            const drawer = document.querySelector(selector);
            
            if (drawer && drawer.offsetWidth > 0) {
                // Get the width of the drawer
                const drawerWidth = drawer.offsetWidth;
                // Add margin to position the link to the left of the drawer
                gotoTopLink.style.marginRight = `${drawerWidth}px`;
                return; // Exit after finding the first open drawer
            }
        }
    }
};

/**
 * Hide or show the sidebar based on horizontal scroll position
 * @param {number} scrollX - The horizontal scroll position
 */
const toggleSidebarOnHorizontalScroll = (scrollX) => {
    const sidebar = document.querySelector(SELECTORS.SIDEBAR);
    if (!sidebar) {
        return;
    }
    if (scrollX !== 0) {
        if (lastScrollX === 0) {
            // Hide sidebar
            sidebar.style.right = '-100%';
            
            // Hide active drawers
            DRAWERS.ACTIVE_SELECTORS.forEach(selector => {
                const activeDrawers = document.querySelectorAll(selector);
                activeDrawers.forEach(drawer => {
                    drawer.style.right = '-100%';
                });
            });
        }
    } else if (lastScrollX !== 0) {
        // When returning to scroll position 0
        sidebar.style.right = '';
        
        // Restore active drawers visibility
        DRAWERS.ACTIVE_SELECTORS.forEach(selector => {
            const activeDrawers = document.querySelectorAll(selector);
            activeDrawers.forEach(drawer => {
                drawer.style.right = '';
            });
        });
    }
    lastScrollX = scrollX;
};
