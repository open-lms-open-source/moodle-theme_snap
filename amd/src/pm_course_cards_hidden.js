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
    var HiddenCards = function() {

        var self = this;

        /**
         * Initialise.
         */
        var init = function() {
            // Hidden course toggle function.
            $(document).on("click", '#js-toggle-hidden-courses', function(e) {
                self.toggleHidden();
                e.preventDefault();
            });
        };

        /**
         * Toggle hidden section.
         * @param {undefined|bool} focusOnComplete
         */
        this.toggleHidden = function(focusOnComplete) {
            // Default behaviour is to focus on the expanded area when the animation is complete.
            focusOnComplete = typeof(focusOnComplete) === 'undefined' ? true : focusOnComplete;
            $('#fixy-hidden-courses').slideToggle("fast", function() {
                if (focusOnComplete) {
                    // Small screen height
                    var winHeight = $(window).height();
                    var sectionHeight = $('#fixy-my-courses').outerHeight() + 100;
                    if (sectionHeight < winHeight) {
                        sectionHeight = winHeight;
                    }
                    $('#fixy-content').css('height',sectionHeight);
                    // Animation complete.
                    $('#fixy-hidden-courses').toggleClass('state-visible');
                    $('#fixy-hidden-courses').focus();
                }
            });
        };

        /**
         * Update count of hidden courses within toggblable hidden section.
         */
        this.updateToggleCount = function() {
            var count = $('#fixy-hidden-courses .courseinfo').length;
            var hiddenCourseStr = M.util.get_string('hiddencoursestoggle', 'theme_snap', count);
            $('#js-toggle-hidden-courses').html(hiddenCourseStr);
            if (count === 0) {
                $('.header-hidden-courses').removeClass('state-visible');
                $('#fixy-hidden-courses').css('display', 'none');
            }
        };

        init();

    };
    return new HiddenCards();
});
