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
 * Personal menu course cards.
 */
define(['jquery', 'core/log', 'core/templates',
    'theme_snap/pm_course_cards_hidden', 'theme_snap/pm_course_favorites',
    'theme_snap/model_view', 'theme_snap/ajax_notification', 'theme_snap/util'],
    function($, log, templates, cardsHidden, courseFavorites, mview, ajaxNotify, util) {

        var CourseCards = function() {

            var self = this;

            /**
             * Apply course information to courses in personal menu.
             *
             * @param crsinfo
             */
            this.applyCourseInfo = function(crsinfo) {
                // Pre-load template or it will get loaded multiple times with a detriment on performance.
                templates.render('theme_snap/course_cards', [])
                    .done(function() {
                        for (var i in crsinfo) {
                            var info = crsinfo[i];
                            log.debug('applying course data for courseid ' + info.course);
                            var cardEl = $('.courseinfo[data-courseid="' + info.course + '"]');
                            mview(cardEl, 'theme_snap/course_cards');
                            $(cardEl).trigger('modelUpdate', info);
                        }
                    });
            };

            /**
             * Request courseids.
             * @param courseids[]
             */
            this.reqCourseInfo = function(courseids) {
                if (courseids.length === 0) {
                    return;
                }
                // Get course info via ajax.
                var courseiddata = 'courseids=' + courseids.join(',');
                var courseinfo_key = M.cfg.sesskey + 'courseinfo';
                log.debug("fetching course data");
                $.ajax({
                    type: "GET",
                    async: true,
                    url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_courseinfo&contextid=' + M.cfg.context,
                    data: courseiddata,
                    success: function(data) {
                        if (ajaxNotify.ifErrorShowBestMsg(data)) {
                            return;
                        }
                        if (data.info) {
                            log.debug('fetched coursedata', data.info);
                            if (util.supportsSessionStorage()) {
                                window.sessionStorage[courseinfo_key] = JSON.stringify(data.info);
                            }
                            self.applyCourseInfo(data.info);
                        } else {
                            log.warn('fetched coursedata with error: JSON data object is missing info property', data);
                        }
                    }
                });
            };

            /**
             * Get course ids from cards.
             * @returns {Array}
             */
            this.getCourseIds = function() {
                var courseIds = [];
                $('.courseinfo').each(function() {
                    courseIds.push($(this).attr('data-courseid'));
                });
                return courseIds;
            };

            /**
             * Initialising function.
             */
            this.init = function() {
                $(document).ready(function() {
                    courseFavorites(cardsHidden);

                    // Load course information via ajax.
                    var courseIds = self.getCourseIds();
                    var courseinfo_key = M.cfg.sesskey + 'courseinfo';
                    if (courseIds.length > 0) {
                        // OK - lets see if we have grades/progress in session storage.
                        if (util.supportsSessionStorage() && window.sessionStorage[courseinfo_key]) {
                            var courseinfo = JSON.parse(window.sessionStorage[courseinfo_key]);
                            self.applyCourseInfo(courseinfo);
                        } else {
                            // Only make AJAX request on document ready if the session storage isn't populated.
                            self.reqCourseInfo(courseIds);
                        }
                    }
                });

                // Reveal more teachers on click or hover teachers more icon.
                $('#fixy-my-courses').on('click hover', '.courseinfo-teachers-more', null, function(e) {
                    e.preventDefault();
                    var nowhtml = $(this).html();
                    if (nowhtml.indexOf('+') > -1) {
                        $(this).html(nowhtml.replace('+', '-'));
                    } else {
                        $(this).html(nowhtml.replace('-', '+'));
                    }
                    $(this).parents('.courseinfo').toggleClass('show-all');
                });

                // Personal menu course card clickable.
                $(document).on('click', '.courseinfo[data-href]', function(e) {
                    var trigger = $(e.target),
                        hreftarget = '_self';
                    // Excludes any clicks in the card deeplinks.
                    if (!$(trigger).closest('a').length) {
                        window.open($(this).data('href'), hreftarget);
                        e.preventDefault();
                    }
                });
            };

        };

        return new CourseCards();
    }
);
