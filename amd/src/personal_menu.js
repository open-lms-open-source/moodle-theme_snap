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
define(['jquery', 'core/log', 'theme_snap/pm_course_cards'], function($, log, courseCards) {

    return new (function() {

        var self = this;

        /**
         * Add deadlines, messages, grades & grading,  async'ly to the personal menu
         *
         * @author Stuart Lamour
         */
        this.update = function() {

            /**
             * Check if the browser supports localstorage.
             * Safari on private mode does not support write on this object
             */
            var supportlocalstorage = true;
            if (typeof localStorage === 'object') {
                try {
                    localStorage.setItem('localStorage', 1);
                    localStorage.removeItem('localStorage');
                } catch (e) {
                    supportlocalstorage = false;
                }
            }

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
                        if (window.sessionStorage[cache_key]) {
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
                                log.info('fetched ' + type);
                                if (supportlocalstorage) {
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

            // Load course information via ajax.
            var courseids = [];
            var courseinfo_key = M.cfg.sesskey + 'courseinfo';
            $('.courseinfo .dynamicinfo').each(function() {
                courseids.push($(this).attr('data-courseid'));
            });
            if (courseids.length > 0) {

                /**
                 * Apply course information to courses in personal menu.
                 *
                 * @param crsinfo
                 */
                var applyCourseInfo = function(crsinfo) {
                    for (var i in crsinfo) {
                        var info = crsinfo[i];
                        log.info('applying course data for courseid ' + info.courseid);
                        var courseinfohtml = info.progress.progresshtml;
                        if (info.feedback && info.feedback.feedbackhtml) {
                            courseinfohtml += info.feedback.feedbackhtml;
                        }
                        $('.courseinfo [data-courseid="' + info.courseid + '"]').html(courseinfohtml);
                    }
                };

                // OK - lets see if we have grades/progress in session storage we can use before ajax call.
                if (window.sessionStorage[courseinfo_key]) {
                    var courseinfo = JSON.parse(window.sessionStorage[courseinfo_key]);
                    applyCourseInfo(courseinfo);
                }

                // Get course info via ajax.
                var courseiddata = 'courseids=' + courseids.join(',');
                log.info("fetching course data");
                $.ajax({
                    type: "GET",
                    async: true,
                    url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_courseinfo&contextid=' + M.cfg.context,
                    data: courseiddata,
                    success: function(data) {
                        if (data.info) {
                            log.info('fetched coursedata', data.info);
                            if (supportlocalstorage) {
                                window.sessionStorage[courseinfo_key] = JSON.stringify(data.info);
                            }
                            applyCourseInfo(data.info);
                        } else {
                            log.warn('fetched coursedata with error: JSON data object is missing info property', data);
                        }
                    }
                });
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
                var targetSection = $(".callstoaction section > div").index(section)+1;
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
        applyListeners();
    })();
});
