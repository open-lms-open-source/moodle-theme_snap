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
        activityCards,
    ) {

        var self = this;


        /**
         * Sections that are being retrieved by the API.
         * @type {Array}
         */
        var sectionsProcess = [];

        /**
         * Sets observers for the TOC drawer elements.
         */
        var setTocObservers = function() {
            if (self.courseConfig.format == 'weeks' || self.courseConfig.format == 'topics') {
                $('#toc-searchables li a').click(function(e) {
                    var link = $(e.target);
                    var urlParams = link.attr('href').split("&"),
                        section = urlParams[0],
                        mod = urlParams[1] || null;
                    section = section.split('#section-')[1];
                    getSection(section, mod);
                });
            }
        };

        /**
         * Sets observers for the TOC and Footer navigation trough sections.
         */
        var setNavigationObservers = function() {
            if (self.courseConfig.format == 'weeks' || self.courseConfig.format == 'topics') {
                var selectors = [
                    '.section_footer a.next_section',
                    '.section_footer a.previous_section',
                    '#courseindex-content .courseindex-section-title a.courseindex-link' // For course index links.
                ];
                $(selectors.join(', ')).click(function(e) {
                    var link = $(this);
                    var section = link.attr('section-number');
                    // For TOC, section number resides on parent div.
                    if (!section) {
                        // Avoid running Behat, as it behaves randomly in Gitlab.
                        if (M.cfg.behatsiterunning) {
                            return;
                        }
                        section = link.closest('.courseindex-section').attr('data-number');
                    }
                    if (typeof section !== 'undefined' && section.length > 0) {
                        e.preventDefault();
                        self.courseConfig.sectionnum = parseInt(section);
                        getSection(section, 0);
                    }
                });
            }
        };
        const setCmActionsObservers = function() {
            const selector = '.action-menu a[data-action],' +
                '.section-actions a[data-action],' +
                '[data-action="newModule"].section-modchooser-link';

            const sections = document.querySelector('ul.sections');
            if (sections) {
                sections.addEventListener('click', function(e) {
                    const actionLink = e.target.closest(selector);
                    if (!actionLink) {
                        return; // Do nothing.
                    }
                    let actionName = actionLink.dataset.action;

                    // Check if we are clicking on action button, permalink & update had another observers.
                    if (actionName === 'permalink' || actionName === 'update') {
                        return; // Do nothing.
                    }
                    e.preventDefault();
                    e.stopPropagation();

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
                    // Init observers for other section and modules actions.
                    const actions = new Actions.prototype.constructor({
                        element: actionLink,
                        reactive: reactiveCourseEditor
                    });
                    // Handle the action.
                    if (typeof actions._dispatchClick === 'function') {
                        actions._dispatchClick(e);
                    }
                }, {capture: true});
            }
        };

        /**
         * Scroll to a mod via search.
         * @param {string} modid
         */
        var scrollToModule = function(modid) {
            // Sometimes we have a hash, sometimes we don't.
            // Strip hash then add just in case.
            $('#toc-search-results').html('');
            var targmod = $("#" + modid.replace('#', ''));
            // http://stackoverflow.com/questions/6677035/jquery-scroll-to-element
            util.scrollToElement(targmod);

            var searchpin = $("#searchpin");
            if (!searchpin.length) {
                searchpin = $('<i id="searchpin"></i>');
            }

            $(targmod).find('.instancename').prepend(searchpin);
            $(targmod).attr('tabindex', '-1').focus();
        };

        /**
         * Gets a specific section for the current course and if an activity module is passed sets focus on it.
         * @param {string} section
         * @param {string} mod
         */
        var getSection = function(section, mod) {
            var node = $('ul.sections > #section-' + section);
            if (node.length == 0 && sectionsProcess.indexOf(section) == -1) {
                sectionsProcess.push(section);
                var params = {courseid: self.courseConfig.id, section: section};
                $('.sk-fading-circle').show();
                // We need to prevent the DOM to show the default section.
                $('.course-content .' + self.courseConfig.format + 'ul.sections > li[id^="section-"]').hide();
                fragment.loadFragment('theme_snap', 'section', self.courseConfig.contextid, params).done(function(html, js) {
                    var node = $(html);
                    renderSection(section, node, mod, js);

                    var folders = node.find('li.snap-activity.modtype_folder');
                    $.each(folders, function (index, folder) {
                        var content = $(folder).find('div.contentwithoutlink div.snap-assettype');
                        if (content.length > 0) {
                            if ($(folder).find('div.activityinstance div.snap-header-card .asset-type').length == 0) {
                                var folderAssetTypeHeader = $(folder).find('div.activityinstance div.snap-header-card');
                                content.prependTo(folderAssetTypeHeader);
                            }
                        }
                    });
                });
            } else {
                $(window).trigger('hashchange');
            }
        };

        /**
         * This functions inserts a section node to the DOM.
         * @param {string} section
         * @param {node} html
         * @param {string} mod
         * @param {string} js
         */
        var renderSection = function(section, html, mod, js) {
            var anchor = $('.course-content');
            var existingSections = [];
            anchor.find('ul.sections>li[id^=section-]').each(function() {
                existingSections.push(parseInt($(this).attr('id').split('section-')[1]));
            });
            var tempnode = $('<div></div>');
            templates.replaceNodeContents(tempnode, html, '');

            if (existingSections.length > 0) {
                var closest = existingSections.reduce(function(prev, curr) {
                    return (Math.abs(curr - section) < Math.abs(prev - section) ? curr : prev);
                });

                if (closest > section) {
                    anchor.find('ul.sections > #section-' + closest).before(tempnode.contents());

                } else {
                    anchor.find('ul.sections > #section-' + closest).after(tempnode.contents());

                }
            } else {
                $('.sk-fading-circle').after(tempnode);
            }
            templates.runTemplateJS(js);

            // Hide loading animation.
            $('.sk-fading-circle').hide();
            // Notify filters about the new section.
            Event.notifyFilterContentUpdated($('.course-content .' + self.courseConfig.format));
            var sections = anchor.find('ul.sections>li[id^="section-"]');
            // When not present the section, the first one will be shown as default, remove all classes to prevent that.
            sections.removeClass('state-visible');
            var id = 'ul.sections>#section-' + section;
            $(id).addClass('state-visible');
            // Leave all course sections as they were.
            sections.show();
            setNavigationObservers();
            activityCards.init();

            // Set observer for mod chooser.
            $(id + ' .section-modchooser-link').click(function() {
                // Grab the section number from the button.
                var sectionNum = $(this).attr('data-sectionid');
                $('.snap-modchooser-addlink').each(function() {
                    // Update section in mod link to current section.
                    var newLink = this.href.replace(/(section=)[0-9]+/ig, '$1' + sectionNum);
                    $(this).attr('href', newLink);
                });
            });

            // If a module id has been passed as parameter, set focus.
            if (mod != 0 && typeof mod !== 'undefined') {
                scrollToModule(mod);
            }
        };

    return {
        init: function(courseLib) {

            self.courseConfig = courseLib.courseConfig;

            /**
             * Set observers for TOC and navigation buttons in the footer.
             */
            var setCourseSectionObservers = function() {
                setTocObservers();
                setNavigationObservers();
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
         * Exposed function that renders a specific course section and sets focus on an activity module.
         * @param {string} section
         * @param {string} mod
         */
        renderAndFocusSection: function(section, mod) {
            getSection(section, mod);
        },

        /**
         * Exposed function so observers can be set on TOC.
         */
        setNavigationObservers: function() {
            setNavigationObservers();
        }
    };

});
