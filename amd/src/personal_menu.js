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
 * @package   theme_n2018
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * N2018 Personal menu.
 */
define(['jquery', 'core/log', 'core/yui', 'theme_n2018/pm_course_cards', 'theme_n2018/util', 'theme_n2018/ajax_notification'],
    function($, log, Y, courseCards, util, ajaxNotify) {

        /**
         * Personal Menu (courses menu).
         * @constructor
         */
        var PersonalMenu = function() {

            var self = this;

            var redirectToSitePolicy = false;

            /**
             * Add deadlines, messages, grades & grading,  async'ly to the personal menu
             *
             */
            this.update = function() {

                // If site policy needs acceptance, then don't update, just redirect to site policy!
                if (redirectToSitePolicy) {
                    var redirect = M.cfg.wwwroot + '/user/policy.php';
                    window.location = redirect;
                    return;
                }

                // Update course cards with info.
                courseCards.reqCourseInfo(courseCards.getCourseIds());


                $('#n2018-pm').focus();

                /**
                 * Load ajax info into personal menu.
                 *
                 */
                var loadAjaxInfo = function(type) {
                    // Target for data to be displayed on screen.
                    var container = $('#n2018-personal-menu-' + type);
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
                                url: M.cfg.wwwroot + '/theme/n2018/rest.php?action=get_' + type + '&contextid=' + M.cfg.context,
                                success: function(data) {
                                    ajaxNotify.ifErrorShowBestMsg(data).done(function(errorShown) {
                                        if (errorShown) {
                                            return;
                                        } else {
                                            // No errors, update sesion storage.
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

                $(document).trigger('n2018UpdatePersonalMenu');
            };

            /**
             * Apply listeners for personal menu in mobile mode.
             */
            var mobilePersonalMenuListeners = function() {
                /**
                 * Get section left position and height.
                 */
                var getSectionCoords = function(href) {
                    var sections = $("#n2018-pm-content section");
                    var sectionWidth = $(sections).outerWidth();
                    var section = $(href);
                    var targetSection = $("#n2018-pm-updates section > div").index(section) + 1;
                    var position = sectionWidth * targetSection;
                    var sectionHeight = $(href).outerHeight() + 200;

                    // Course lists is at position 0.
                    if (href == '#n2018-pm-courses') {
                        position = 0;
                    }

                    // Set the window height.
                    var winHeight = $(window).height();
                    if (sectionHeight < winHeight) {
                        sectionHeight = winHeight;
                    }
                    return {left: position, height: sectionHeight};
                };
                // Personal menu small screen behaviour corrections on resize.
                $(window).on('resize', function() {
                    if (window.innerWidth >= 992) {
                        // If equal or larger than Bootstrap 992 large breakpoint, clear left positions of sections.
                        $('#n2018-pm-content').removeAttr('style');
                        return;
                    }
                    var activeLink = $('#n2018-pm-mobilemenu a.state-active');
                    if (!activeLink || !activeLink.length) {
                        return;
                    }
                    var href = activeLink.attr('href');
                    var posHeight = getSectionCoords(href);

                    $('#n2018-pm-content').css('left', '-' + posHeight.left + 'px');
                    $('#n2018-pm-content').css('height', posHeight.height + 'px');
                });
                // Personal menu small screen behaviour.
                $(document).on("click", '#n2018-pm-mobilemenu a', function(e) {
                    var href = this.getAttribute('href');
                    var posHeight = getSectionCoords(href);

                    $("html, body").animate({scrollTop: 0}, 0);
                    $('#n2018-pm-content').animate({
                            left: '-' + posHeight.left + 'px',
                            height: posHeight.height + 'px'
                        }, "700", "swing",
                        function() {
                            // Animation complete.
                        });
                    $('#n2018-pm-mobilemenu a').removeClass('state-active');
                    $(this).addClass('state-active');
                    e.preventDefault();
                });
            };

            /**
             * Apply personal menu listeners.
             */
            var applyListeners = function() {
                // On clicking personal menu trigger.
                $(document).on("click", ".js-n2018-pm-trigger", function(event) {
                    $('body').toggleClass('n2018-pm-open');
                    if ($('.n2018-pm-open #n2018-pm').is(':visible')) {
                        self.update();
                    }
                    event.preventDefault();
                });

                mobilePersonalMenuListeners();
            };

            /**
             * Initialising function.
             */
            this.init = function(sitePolicyAcceptReqd) {
                redirectToSitePolicy = sitePolicyAcceptReqd;
                applyListeners();
                if (!redirectToSitePolicy) {
                    courseCards.init();
                }
            };
        };

        return new PersonalMenu();
    }
);
