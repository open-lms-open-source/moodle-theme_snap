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
 * @copyright Copyright (c) 2023 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Snap My Courses.
 */
define(['jquery', 'core/log','theme_snap/util', 'theme_snap/ajax_notification'],
    function($, log, util, ajaxNotify) {
        /**
         * Load My courses feeds content when advanced feeds are disabled.
         *
         * @param {Boolean} redirectToSitePolicyMyCourses Boolean to redirect to site policy.
         */
        var init = function(redirectToSitePolicyMyCourses) {
            if (redirectToSitePolicyMyCourses) {
                var redirect = M.cfg.wwwroot + '/user/policy.php';
                window.location = redirect;
                return;
            }
            $(document).ready(function() {
                var loadAjaxInfo = function(type) {
                    // Target for data to be displayed on screen.
                    var container = $('#snap-my-courses-' + type);
                    if ($(container).length) {
                        var cacheKey = M.cfg.sesskey + 'snap-my-courses-' + type;
                        try {
                            // Display old content while waiting
                            if (util.supportsSessionStorage() && window.sessionStorage[cacheKey]) {
                                log.info('using locally stored ' + type);
                                var html = window.sessionStorage[cacheKey];
                                $(container).html(html);
                            }
                            log.info('fetching ' + type);
                            $.ajax({
                                type: "GET",
                                async: true,
                                url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_' + type + '&contextid=' + M.cfg.context,
                                success: function(data) {
                                    ajaxNotify.ifErrorShowBestMsg(data).done(function(errorShown) {
                                        if (errorShown) {
                                            return;
                                        } else {
                                            // No errors, update sesion storage.
                                            log.info('fetched ' + type);
                                            if (util.supportsSessionStorage() && typeof (data.html) != 'undefined') {
                                                window.sessionStorage[cacheKey] = data.html;
                                            }
                                            // Note: we can't use .data because that does not manipulate the dom, we need the data
                                            // attribute populated immediately so things like behat can utilise it.
                                            // .data just sets the value in memory, not the dom.
                                            $(container).attr('data-content-loaded', '1');
                                            $(container).html(data.html);
                                        }
                                    });
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
            });
        };
        return {
            init: init,
        };
    }
);