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
define(['jquery', 'core/ajax', 'core/notification', 'core/log', 'theme_snap/model_view', 'theme_snap/ajax_notification'],
    function($, ajax, notification, log, mview, ajaxNotify) {
        return function(cardsHidden) {
            log.enableAll(true);

            /**
             * The ajax call has returned a new course_card renderable.
             *
             * @method reloadCourseCardTemplate
             * @param {object} renderable - coursecard renderable
             * @param {jQuery} cardEl - coursecard element
             */
            var reloadCourseCardTemplate = function(renderable, cardEl) {
                mview(cardEl, 'theme_snap/course_cards');
                var callback = function() {
                    var button = $(cardEl).find('.favoritetoggle');
                    $(button).removeClass('ajaxing');
                    $(button).focus();
                };
                $(cardEl).trigger('modelUpdate', [renderable, callback]);
            };

            /**
             * Get course card course id.
             * @param {jQuery} cardEl
             * @returns {int}
             */
            var getCardId = function(cardEl) {
                return parseInt($(cardEl).find('.courseinfo-body').data('courseid'));
            };

            /**
             * Get course card full name.
             * @param {jQuery} cardEl
             * @param {null|bool} lowerCase
             * @returns {*|jQuery}
             */
            var getCardTitle = function(cardEl, lowerCase) {
                // The title comes back in lower case by default as it's used for case insensitive sorting.
                lowerCase = null ? null : true;
                var title = $(cardEl).find('.coursefullname').html();
                if (lowerCase) {
                    title = title.toLowerCase();
                }
                return title;
            };

            /**
             * Get index of card within list.
             *
             * @param {jQuery} cardEl
             * @param {jQuery} cards
             */
            var getCardIndex = function(cardEl, cards) {
                if (cards.length === 0) {
                    return -1;
                }
                // The sort variable is purely for sorting the cards by name.
                var sort = [],
                    sortItem = {};

                cards.each(function() {
                    sortItem = {
                        title: getCardTitle(this),
                        card: this
                    };
                    sort.push(sortItem);
                });
                // Add the item we are inserting to the list.
                sortItem = {
                    title: getCardTitle(cardEl),
                    card: cardEl
                };
                sort.push(sortItem);
                sort.sort(function(a, b) {
                    var aId = getCardId(a.card);
                    var bId = getCardId(b.card);
                    if (a.title === b.title) {
                        if (aId === bId) {
                            return 0;
                        }
                        return aId > bId ? 1 : -1;
                    }
                    return a.title > b.title ? 1 : -1;
                });
                return sort.indexOf(sortItem);
            };

            /**
             * Move card into alphabetical place in list.
             * @param {jQuery} cardEl
             * @param {string} listSelector
             * @param {string} listSelectorWhenEmpty
             * @param {bool} prependWhenEmpty
             * @param {function} onMoveComplete
             */
            var moveCard = function(cardEl, listSelector, listSelectorWhenEmpty, prependWhenEmpty, onMoveComplete) {

                var cardEls = $(listSelector);
                var idx = getCardIndex(cardEl, cardEls);
                var insIdx = idx + 1;

                log.debug('Moving card element into position ' + insIdx +
                    ' of list (size = ' + cardEls.length + ') : ' + listSelector);

                if (insIdx > 0) {
                    if (insIdx <= cardEls.length) {
                        log.debug('Moving card before position ' + insIdx + '  using selector ' + listSelector);
                        $(listSelector).eq(idx).before(cardEl);
                    } else {
                        log.debug('Moving card after position ' + cardEls.length + ' using selector ' + listSelector);
                        $(listSelector).eq(cardEls.length - 1).after(cardEl);
                    }
                } else {
                    log.debug('Destination ' + listSelector + ' empty');
                    if (prependWhenEmpty) {
                        log.debug('prepending to ' + listSelectorWhenEmpty);
                        $(listSelectorWhenEmpty).prepend(cardEl);
                    } else {
                        log.debug('appending to ' + listSelectorWhenEmpty);
                        $(listSelectorWhenEmpty).append(cardEl);
                    }
                }

                cardsHidden.updateToggleCount();
                if (typeof(onMoveComplete) === 'function') {
                    onMoveComplete();
                }
            };

            /**
             * Move card element out of favorites.
             * @param {jQuery} cardEl
             * @param {function} onMoveComplete
             * @returns {void}
             */
            var moveOutOfFavorites = function(cardEl, onMoveComplete) {
                var container;
                if ($(cardEl).data('hidden') === true) {
                    container = '#fixy-hidden-courses';
                    // Show toggle for hidden courses.
                    $('.header-hidden-courses').addClass('state-visible');
                    // Auto toggle visibility of hidden courses if currently hidden.
                    if (!$('#fixy-hidden-courses').is(':visible')) {
                        cardsHidden.toggleHidden(false);
                    }
                } else {
                    container = '#fixy-visible-courses';
                }
                moveCard(cardEl, container + ' .courseinfo:not(.favorited)', container, false, onMoveComplete);
            };

            /**
             * Favorite a course.
             * @param {jQuery} button - button clicked on to favorite the course.
             */
            var favoriteCourse = function(button) {
                if ($(button).hasClass('ajaxing')) {
                    return;
                }

                $(button).addClass('ajaxing');

                var favorited = $(button).attr('aria-pressed') === 'true' ? 0 : 1;
                var cardEl = $($(button).parents('.courseinfo')[0]);
                var shortname = $(cardEl).data('shortname');

                var doAjax = function() {
                    ajax.call([
                        {
                            methodname: 'theme_snap_course_card',
                            args: {courseshortname: shortname, favorited: favorited},
                            done: function(response) {
                                reloadCourseCardTemplate(response, cardEl);
                            },
                            fail: function(response) {
                                $(button).removeClass('ajaxing');
                                ajaxNotify.ifErrorShowBestMsg(response);
                            }
                        }
                    ], true, true);
                };

                if (favorited === 1) {
                    // Move to favorites.
                    moveCard(cardEl, '#fixy-visible-courses .courseinfo.favorited', '#fixy-visible-courses', true, doAjax);
                } else {
                    moveOutOfFavorites(cardEl, doAjax);
                }
            };

            /**
             * On clicking favourite toggle. (Delegated).
             */
            $("#fixy-my-courses").on("click", ".favoritetoggle", function(e) {
                e.preventDefault();
                e.stopPropagation();
                favoriteCourse(this);
            });
        };
    }
);
