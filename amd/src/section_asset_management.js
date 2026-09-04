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
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
        'core/log',
        'core/ajax',
        'core/str',
        'core/templates',
        'core/notification',
        'theme_snap/util',
        'theme_snap/ajax_notification',
        'core_filters/events',
        'core/fragment',
        'core_courseformat/local/content/actions',
        'core_courseformat/courseeditor',
        'theme_snap/activity_cards',
    ],
    function(
        $,
        log,
        ajax,
        str,
        templates,
        notification,
        util, ajaxNotify,
        Event,
        fragment,
        Actions,
        CourseEditor,
        activityCards
    ) {

        var self = this;

        const setCmActionsObservers = function() {
            const selector = '.action-menu a[data-action],' +
                '.section-actions a[data-action],' +
                '[data-action="newModule"].section-modchooser-link';

            const sections = document.querySelector('ul.sections');
            if (sections) {
                sections.addEventListener('click', function(e) {
                    const actionLink = e.target.closest(selector);
                    if (!actionLink || actionLink.dataset.initialized === "true") {
                        // Not an action link or already initialized.
                        return; // Do nothing.
                    }
                    let actionName = actionLink.dataset.action;

                    // Check if we are clicking on action button, permalink & update had another observers.
                    if (actionName === 'permalink' || actionName === 'update') {
                        return; // Do nothing.
                    }
                    // Initialize the reactive component to make available the action and avoid duplicate of actions.
                    actionLink.dataset.initialized = "true";

                    const reactiveCourseEditor = CourseEditor.getCurrentCourseEditor();
                    // In topics format we need to initialize the reactive component for highlight sections.
                    if (self.courseConfig.format == 'topics') {
                        require(
                            ['format_topics/section',], function(SectionModule) {
                                let editMode = reactiveCourseEditor.isEditing;
                                // We need editing mode On, so topics module is initialized properly.
                                reactiveCourseEditor._editing = true;
                                SectionModule.init();
                                reactiveCourseEditor._editing = editMode;
                            }
                        );
                    }
                    // Init observers for section and activities actions.
                    const actions = new Actions.prototype.constructor({
                        element: actionLink,
                        reactive: reactiveCourseEditor
                    });
                    // Handle the action.
                    if (typeof actions._dispatchClick === 'function') {
                        if (actionName === "cmDelete") {
                            // If deleting an activity, update toc Searchable for Snap.
                            // We should use reactive components instead.
                            let cmid = actionLink.dataset.id;
                            $('#toc-searchables li[data-id="' + cmid + '"]').remove();
                        } else if (actionName === "newModule") {
                            e.preventDefault();
                            e.stopPropagation();
                            // For subsections creation we need to call the _dispatchClick manually always.
                            actionLink.dataset.initialized = "false";
                            actions._dispatchClick(e);
                        }
                    }
                }, {capture: true});
            }
        };

        /**
         * Mark the section shown to user with a class in the TOC.
         *
         * format_tiles never renders the "state-visible" class server-side (see
         * multi_section_page.mustache / single_section.mustache / section_zero.mustache) — it is
         * only ever added client-side by format_tiles/course.js as it opens/closes tiles via AJAX,
         * a module that is not loaded while editing. So on a genuine fresh navigation to
         * /course/section.php?id=X (editing on, or a full reload landing back there — e.g. toggling
         * editing off redirects to the same URL, see course/section.php), that DOM class does not
         * exist yet and the URL itself is the only reliable source. Once the page has settled,
         * further AJAX tile switches leave the URL unchanged, so from then on only the DOM class
         * (kept accurate by format_tiles/course.js on every click) can be trusted.
         *
         * @param {boolean} [trustUrl] Pass true only from a call that is guaranteed to run on a
         * fresh navigation (i.e. the course index's initial stateReady()); omit/false otherwise.
         */
        var setTOCVisibleSection = function(trustUrl) {
            var currentSectionId = null;
            if (trustUrl && window.location.pathname.endsWith('/course/section.php')) {
                currentSectionId = new URLSearchParams(window.location.search).get('id');
            }
            if (!currentSectionId) {
                var sectionIdSel = '.section.main.state-visible, #coursetools.state-visible, #snap-add-new-section.state-visible';
                var shownSection = $(sectionIdSel);
                // Formats rendered through Snap's own section_header() carry the section id in
                // data-id, but format_tiles renders its own markup and names it data-sectionid
                // (see multi_section_page.mustache / single_section.mustache / section_zero.mustache).
                // Both hold $section->id, which is what the course index keys its data-id on.
                currentSectionId = shownSection.attr('data-id') || shownSection.attr('data-sectionid');
            }
            if (!currentSectionId) {
                // Switching tiles drops state-visible from the outgoing tile synchronously but only
                // adds it to the incoming one once its content arrives from the server, so nothing
                // is open mid-switch. format_tiles keeps its own record of that in the body class,
                // which it clears only when a tile is really closed: while it is still set, treat
                // the gap as transient and leave the current highlight alone instead of flashing
                // General on every tile switch.
                if (document.body.classList.contains('format-tiles-tile-open')) {
                    return;
                }
                // Section zero never gets the state-visible class: format_tiles renders it outside
                // the tile grid (above_tiles.mustache) and only slides it up/down, and its
                // tile-closing loops skip it on purpose (see course.js populateAndExpandSection).
                // So once nothing else is open — which is exactly what closing a tile or picking
                // General in the course index leaves behind — the shown section is section zero.
                var sectionZero = $('#section-0:visible');
                currentSectionId = sectionZero.attr('data-id') || sectionZero.attr('data-sectionid');
            }

            // Remove snap-visible-section class and reset aria-current to false for all sections.
            $('#courseindex .courseindex-section').removeClass('snap-visible-section');
            $('#courseindex .courseindex-section a.courseindex-link').attr('aria-current', 'false');

            // Find the correct Section link and update class and aria-current.
            var visibleSection = $(`#courseindex .courseindex-section[data-id="${currentSectionId}"]`);
            visibleSection.addClass('snap-visible-section');
            visibleSection.find('a.courseindex-link').attr('aria-current', 'true');
        };

        /**
         * Whether a section is a delegated section (subsection), e.g. a mod_subsection instance.
         *
         * Delegated sections can become hidden/shown by cascade when their parent section's
         * visibility changes, without their own state ever being updated (the exported state only
         * reflects a section's own "visible" DB flag, not its parent's). So a cached copy of a
         * subsection previously fetched by getSection() cannot be trusted to still be accurate.
         *
         * @param {string} sectionID The section ID to check.
         * @returns {boolean} true if the section is a delegated section (subsection).
         */
        var isDelegatedSection = function(sectionID) {
            const reactiveCourseEditor = CourseEditor.getCurrentCourseEditor();
            const state = reactiveCourseEditor?.state;
            if (!state || !state.section) {
                return false;
            }
            const section = state.section.get(String(sectionID));
            return !!section?.component;
        };

        /**
         * block_sharing_cart's own JS (blocks/sharing_cart/amd/src/local/block.js) only injects its
         * "add to sharing cart" icon once, in a one-time DOM scan done at page load, and afterwards only reacts to
         * core course-editor reactive state mutations (cm:created/cm:updated/section:created/section:updated).
         * Snap's lazy section loading above injects a whole new section's markup directly via appendNodeContents,
         * without ever going through the reactive state layer, so the icon never gets attached to activities/
         * sections loaded this way until a full page reload re-runs that one-time scan. This re-runs the same
         * icon injection for whatever section markup was just appended.
         *
         * @param {Element} container the newly appended section container
         */
        var refreshSharingCartButtons = function(container) {
            const blockElement = document.querySelector('.block.block_sharing_cart');
            // The data-can-backup attribute lives on the inner #block_sharing_cart div
            // (see blocks/sharing_cart/templates/block/content.mustache), not on the outer
            // Snap block wrapper matched above.
            const canBackupElement = document.getElementById('block_sharing_cart');
            if (!blockElement || !canBackupElement || canBackupElement.dataset.canBackup !== '1') {
                return;
            }

            // Only proceed if the feature is actually active on this page (an icon is already showing elsewhere).
            const template = document.querySelector('.course-content .add_to_sharing_cart');
            if (!template) {
                return;
            }

            require(['block_sharing_cart/app/factory'], function(BaseFactory) {
                const block = BaseFactory.make().block().element(blockElement, false, false, true, true);
                block.setupCourse();

                container.querySelectorAll('.cm_action_menu[data-cmid]').forEach(function(menu) {
                    if (menu.querySelector('.add_to_sharing_cart')) {
                        return;
                    }

                    const cmid = menu.dataset.cmid;
                    const button = template.cloneNode(true);
                    button.classList.remove('disabled');
                    button.removeAttribute('title');
                    menu.append(button);
                    button.addEventListener('click', function() {
                        block.addCourseModuleBackupToSharingCart(cmid);
                    });
                });

                container.querySelectorAll('[data-for="section_title"] .inplaceeditable[data-itemid]').forEach(
                    function(sectionTitle) {
                        if (sectionTitle.parentElement.querySelector('.add_to_sharing_cart')) {
                            return;
                        }

                        const sectionId = sectionTitle.dataset.itemid;
                        const button = template.cloneNode(true);
                        sectionTitle.after(button);
                        button.addEventListener('click', function(e) {
                            if (e.currentTarget.classList.contains('disabled')) {
                                return;
                            }
                            block.addSectionBackupToSharingCart(sectionId);
                        });

                        const section = CourseEditor.getCurrentCourseEditor().state.section.get(sectionId);
                        const disabled = !section || section.cmlist.length === 0;
                        button.classList.toggle('disabled', disabled);
                        if (disabled) {
                            str.get_string('no_course_modules_in_section_description', 'block_sharing_cart')
                                .then(function(text) {
                                    button.title = text;
                                    return;
                                })
                                .catch(notification.exception);
                        }
                    }
                );
            });
        };

        /**
         * Gets a specific section for the current course.
         * @param {string} sectionID The section ID to be shown.
         * @param {string} modid The module ID to set focus.
         * @param {function} sectionVisibilityCallback Function to make visible the section on Course.
         */
        var getSection = function(sectionID, modid, sectionVisibilityCallback) {
            var params = {courseid: self.courseConfig.id, sectionid: sectionID};
            $('.sk-fading-circle').show();
            fragment.loadFragment('theme_snap', 'section', self.courseConfig.contextid, params)
                .done(function(html, js) {
                    var $container = $('ul.sections');
                    templates.appendNodeContents($container, html, js);

                    // Then, show the section.
                    sectionVisibilityCallback(sectionID, modid);
                    // Notify filters about the new section.
                    Event.notifyFilterContentUpdated($('.course-content .' + self.courseConfig.format));
                    activityCards.init();
                    // OpenLMS Patch: re-attach sharing cart icons for the section just lazily loaded above.
                    refreshSharingCartButtons($container.get(0));

                    $('.sk-fading-circle').hide();
                })
                .fail(function(ex) {
                    $('.sk-fading-circle').hide();
                    notification.exception(ex);
                });
        };

        /**
         * Update site breadcrumb when navigating through sections.
         * @param {string} sectionId the ID of the current section to show.
         */
        var updateBreadcrumb = function(sectionId = '') {
            // Use reactive Instances for getting course State.
            const reactiveCourseEditor = CourseEditor.getCurrentCourseEditor();
            const state = reactiveCourseEditor.state;

            if (!state || !state.section) {
                return;
            }

            // Get Visible section from State.
            const targetSection = Array.from(state.section.values()).find(sec => sec.id == sectionId);
            if (!targetSection) {
                return;
            }

            let breadcrumbSections = [];

            // Manage subsections.
            if (targetSection.component === 'mod_subsection' && targetSection.parentsectionid) {
                // Let's search for parent of subsection.
                const parentSection = state.section.find(sec => sec.id == targetSection.parentsectionid);
                if (parentSection) {
                    // Add parent section to Breadcrumb.
                    breadcrumbSections.push({
                        id: parentSection.id,
                        name: parentSection.title,
                        url: parentSection.sectionurl,
                        isCurrent: false
                    });
                }
            }

            // Add current section to Breadcrumb.
            breadcrumbSections.push({
                id: targetSection.id,
                name: targetSection.title,
                url: targetSection.sectionurl,
                isCurrent: true
            });

            // Generate new HTML for section nodes.
            let html = '';
            breadcrumbSections.forEach(step => {
                const ariaCurrent = step.isCurrent ? 'aria-current="page"' : '';
                html += `<li class="breadcrumb-item">
                            <a href="${step.url}" ${ariaCurrent} data-section-name-for="${step.id}" >${step.name}</a>
                         </li>`;
            });

            // Inject the Breadcrumb Nodes.
            const breadcrumbList = document.querySelector('ol.breadcrumb');

            // Remove old section/s from Breadcrumb.
            const oldDynamicItems = breadcrumbList.querySelectorAll('a[data-section-name-for]');
            oldDynamicItems.forEach(link => {
                const listItem = link.closest('li.breadcrumb-item');
                if (listItem) {
                    listItem.remove();
                }
            });
            // Update or insert new sections.
            breadcrumbList.insertAdjacentHTML('beforeend', html);
        };

    return {
        init: function(courseLib) {

            self.courseConfig = courseLib.courseConfig;

            /**
             * Set observers for TOC and navigation buttons in the footer.
             */
            var setCourseSectionObservers = function() {
                // Check user is logged in, in order to use CourseEditor.
                const isLoggedIn = document.querySelector('#snap-header  .usermenu');
                // Only on course page.
                const onCoursePage = document.body.classList.contains('path-course-view');
                if (!isLoggedIn || !onCoursePage) {
                    return; // If not, do nothing.
                }
                // Event listeners for Course modules actions, when editing is off.
                const reactiveCourseEditor = CourseEditor.getCurrentCourseEditor();
                if (!reactiveCourseEditor.isEditing) {
                    setCmActionsObservers();
                }
            };

            /**
             * Add listeners.
             */
            var addListeners = function() {
                setCourseSectionObservers();
                $('body').addClass('snap-course-listening');
            };

            /**
             * Override core functions.
             */
            var overrideCore = function() {
                // Check M.course exists (doesn't exist in social format).
                if (M.course && M.course.resource_toolbox) {
                    /* eslint-disable camelcase */
                    M.course.resource_toolbox.handle_resource_dim = function(button, activity, action) {
                        return (action === 'hide') ? 0 : 1;
                    };
                    /* eslint-enable camelcase */
                }
            };

            /**
             * Initialise script.
             */
            var initialise = function() {
                // Add listeners.
                addListeners();

                // Override core functions
                util.whenTrue(function() {
                    return M.course && M.course.init_section_toolbox;
                }, function() {
                    overrideCore();
                    }, true);

            };
            initialise();
        },

        /**
         * Exposed function so Section HTML can be obtained.
         * @param {string} sectionID
         * @param {string} modid
         * @param {function} sectionVisibilityCallback
         */
        getSection: function(sectionID, modid, sectionVisibilityCallback) {
            getSection(sectionID, modid, sectionVisibilityCallback);
        },

        /**
         * Exposed function so Breadcrumb is updated.
         * @param {string} sectionId the ID of the current section to show.
         */
        updateBreadcrumb: function(sectionId) {
            updateBreadcrumb(sectionId);
        },

        /**
         * Exposed function so Section in TOC is highlighted.
         * @param {boolean} [trustUrl] see the internal setTOCVisibleSection() above.
         */
        setTOCVisibleSection: function(trustUrl) {
            setTOCVisibleSection(trustUrl);
        },

        /**
         * Exposed function so callers can check if a section is a delegated section (subsection).
         * @param {string} sectionID
         * @returns {boolean}
         */
        isDelegatedSection: function(sectionID) {
            return isDelegatedSection(sectionID);
        }
    };

});
