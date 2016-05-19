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
define(['jquery', 'theme_snap/pm_course_cards_hidden', 'theme_snap/pm_course_favorites'], function($, cardsHidden, courseFavorites) {
    return (function() {

        $(document).ready(function() {
            courseFavorites(cardsHidden);
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
        
    })();
});
