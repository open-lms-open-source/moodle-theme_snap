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
define(['jquery', 'core/log', 'theme_snap/pm_course_cards_hidden', 'theme_snap/pm_course_favorites', 'theme_snap/model_view'],
    function($, log, cardsHidden, courseFavorites, mview) {
        var CourseCards = function() {

            $(document).ready(function() {
                courseFavorites(cardsHidden);

                // Load course information via ajax.
                var supportlocalstorage = true;
                if (typeof localStorage === 'object') {
                    try {
                        localStorage.setItem('localStorage', 1);
                        localStorage.removeItem('localStorage');
                    } catch (e) {
                        supportlocalstorage = false;
                    }
                }

                var courseids = [];
                var courseinfo_key = M.cfg.sesskey + 'courseinfo';
                $('.courseinfo').each(function() {
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
                            log.debug('applying course data for courseid ' + info.course);
                            var cardEl = $('.courseinfo[data-courseid="' + info.course + '"]');
                            mview(cardEl, 'theme_snap/course_cards');
                            $(cardEl).trigger('modelUpdate', info);
                        }
                    };

                    // OK - lets see if we have grades/progress in session storage we can use before ajax call.
                    if (window.sessionStorage[courseinfo_key]) {
                        var courseinfo = JSON.parse(window.sessionStorage[courseinfo_key]);
                        applyCourseInfo(courseinfo);
                    }

                    // Get course info via ajax.
                    var courseiddata = 'courseids=' + courseids.join(',');
                    log.debug("fetching course data");
                    $.ajax({
                        type: "GET",
                        async: true,
                        url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_courseinfo&contextid=' + M.cfg.context,
                        data: courseiddata,
                        success: function(data) {
                            if (data.info) {
                                log.debug('fetched coursedata', data.info);
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

        return new CourseCards();
    }
);
