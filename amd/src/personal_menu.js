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
 * @package   theme_snap
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course card favoriting.
 */
define(['jquery', 'core/log', 'core/yui', 'theme_snap/pm_course_cards', 'theme_snap/util', 'theme_snap/ajax_notification'],
    function($, log, Y, courseCards, util, ajaxNotify) {

        /**
         * Personal Menu (courses menu).
         * @constructor
         */
        var PersonalMenu = function() {

            var self = this;

            /**
             * Add deadlines, messages, grades & grading,  async'ly to the personal menu
             *
             * @author Stuart Lamour
             */
            this.update = function() {

                // Update course cards with info.
                courseCards.reqCourseInfo(courseCards.getCourseIds());

                $('#primary-nav').focus();
                // primary nav showing so hide the other dom parts
                $('#page, #moodle-footer, #js-personal-menu-trigger, #logo, .skiplinks').hide(0);

                /**
                 * Load ajax info into personal menu.
                 *
                 */
                var loadAjaxInfo = function(type) {
                    var container = $('#snap-personal-menu-' + type);
                    if ($(container).length) {
                        var cache_key = M.cfg.sesskey + 'personal-menu-' + type;
                        try {
                            // Display old content while waiting
                            if (util.supportsSessionStorage() && window.sessionStorage[cache_key]) {
                                log.info('using locally stored ' + type);
                                var html = window.sessionStorage[cache_key];
                                $(container).html(html);
                            }
                            log.info('fetching ' + type);
                            $.ajax({
                                type: "GET",
                                async: true,
                                url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_' + type + '&contextid=' + M.cfg.context,
                                success: function(data) {
                                    if (ajaxNotify.ifErrorShowBestMsg(data)) {
                                        return;
                                    }
                                    log.info('fetched ' + type);
                                    if (util.supportsSessionStorage() && typeof(data.html) != 'undefined') {
                                        window.sessionStorage[cache_key] = data.html;
                                    }
                                    // Note: we can't use .data because that does not manipulate the dom, we need the data
                                    // attribute populated immediately so things like behat can utilise it.
                                    // .data just sets the value in memory, not the dom.
                                    $(container).attr('data-content-loaded', '1');
                                    $(container).html(data.html);
                                }
                            });
                        } catch (err) {
                            sessionStorage.clear();
                            log.error(err);
                        }
                    }
                };

                loadAjaxInfo('deadlines');
                loadAjaxInfo('graded');
                loadAjaxInfo('grading');
                loadAjaxInfo('messages');
                loadAjaxInfo('forumposts');

                if ($('#snap-personal-menu-badges').length) {
                    if (typeof(M.snap_message_badge) === 'undefined') {
                        // When M.snap_message_badge is available then trigger personal menu update.
                        util.whenTrue(
                            function() {
                                return typeof(M.snap_message_badge) != 'undefined';
                            },
                            function() {
                                // We can't rely on snapUpdatePersonalMenu here because it might have been triggered prior to
                                // the badge code being loaded.
                                // So let's just call init_overlay instead.
                                M.snap_message_badge.init_overlay(Y);
                            }, true);
                    } else {
                        M.snap_message_badge.init_overlay(Y);
                    }
                }

                $(document).trigger('snapUpdatePersonalMenu');
            };

            /**
             * Apply personal menu listeners.
             */
            var applyListeners = function() {
                // On clicking personal menu trigger.
                $(document).on("click", ".js-personal-menu-trigger", function(event) {
                    $('body').toggleClass('snap-fixy-open');
                    if ($('.snap-fixy-open #primary-nav').is(':visible')) {
                        self.update();
                    }
                    event.preventDefault();
                });

                // Personal menu small screen behaviour.
                $(document).on("click", '#fixy-mobile-menu a', function(e) {
                    var href = this.getAttribute('href');
                    var sections = $("#fixy-content section");
                    var sectionWidth = $(sections).outerWidth();
                    var section = $(href);
                    var targetSection = $(".callstoaction section > div").index(section) + 1;
                    var position = sectionWidth * targetSection;
                    var sectionHeight = $(href).outerHeight() + 100;

                    // Course lists is at position 0.
                    if (href == '#fixy-my-courses') {
                        position = 0;
                    }

                    // Set the window height.
                    var winHeight = $(window).height();
                    if (sectionHeight < winHeight) {
                        sectionHeight = winHeight;
                    }

                    $('#fixy-content').animate({
                            left: '-' + position + 'px',
                            height: sectionHeight + 'px'
                        }, "fast", "swing",
                        function() {
                            // Animation complete.
                            // TODO - add tab index & focus INT-8988

                        });
                    e.preventDefault();
                });

                // Listen for close button to show page content.
                $(document).on("click", "#fixy-close", function() {
                    $('#page, #moodle-footer, #js-personal-menu-trigger, #logo, .skiplinks').css('display', '');

                });
            };

            /**
             * Initialising function.
             */
            this.init = function() {
                applyListeners();
                courseCards.init();
            };
        };

        return new PersonalMenu();
    }
);
