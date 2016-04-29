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
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course card favoriting.
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], function($, ajax, notification, templates) {
    return function() {

        /**
         * The ajax call has returned a new course_card renderable.
         *
         * @method reloadCourseCardTemplate
         * @param renderable - coursecard renderable
         * @param cardel - coursecard element
         */
        var reloadCourseCardTemplate = function(renderable, cardel) {
            templates.render('theme_snap/course_cards', renderable)
                .done(function(result) {
                    $(cardel).replaceWith(result);
                }).fail(notification.exception);
        };

        /**
         * On clicking favourite toggle. (Delegated).
         */
        $("#fixy-my-courses").on("click", ".favoritetoggle", function(e) {
            e.preventDefault();
            e.stopPropagation();

            if ($(this).hasClass('ajaxing')) {
                return;
            }

            $(this).addClass('ajaxing');

            var favorited = $(this).attr('aria-pressed') === 'true' ? 0 : 1;
            var cardel = $(this).parents('.courseinfo')[0];
            var shortname = $(cardel).data('shortname');

            ajax.call([
                {
                    methodname: 'theme_snap_course_card',
                    args: {courseshortname: shortname, favorited: favorited},
                    done: function(response) {
                        reloadCourseCardTemplate(response, cardel);
                        $(cardel).removeClass('ajaxing');
                    },
                    fail: function() {
                        $(cardel).removeClass('ajaxing');
                        notification.exception(response);
                    }
                }
            ], true, true);

        });
    }
});
