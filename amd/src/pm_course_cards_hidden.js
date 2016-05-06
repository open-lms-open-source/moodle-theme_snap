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
 * Hidden course cards.
 */
define(['jquery'], function($) {
    return new function() {

        var self = this;

        var init = function() {
            // Hidden course toggle function.
            $(document).on("click", '#js-toggle-hidden-courses', function(e) {
                self.toggleHidden();
                e.preventDefault();
            });
        };

        this.toggleHidden = function() {
            $('#fixy-hidden-courses').slideToggle("fast", function() {
                // Animation complete.
                $('#fixy-hidden-courses').focus();
            });
        };

        this.updateToggleCount = function() {
            var count = $('#fixy-hidden-courses .courseinfo').length;
            var hiddenCourseStr = M.util.get_string('hiddencoursestoggle', 'theme_snap', count);
            $('#js-toggle-hidden-courses').html(hiddenCourseStr);
            if (count === 0) {
                $('.header-hidden-courses').removeClass('state-visible');
                $('#fixy-hidden-courses').css('display', 'none');
            } else {
                $('.header-hidden-courses').addClass('state-visible');
                if (!$('#fixy-hidden-courses').is(':visible')) {
                    self.toggleHidden();
                }
            }
        };

        init();

    };
});
