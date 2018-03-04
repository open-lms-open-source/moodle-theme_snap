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
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module theme_snap/alternative_role_handler-lazy
 */
define(['jquery', 'core/ajax', 'theme_snap/ajax_notification', 'core/templates'],
    function($, Ajax, AjaxNotify, templates) {
        var self = this;

        /**
         * Initialising function.
         */
        self.init = function(courseId) {
            // Encode return url.
            var returnURL = encodeURIComponent(window.location.href.replace(M.cfg.wwwroot, ''));

            // Render "return to normal role" button and add to Snap admin navigation.
            templates.render('theme_snap/return_to_normal_role', {
                switchroleurl: M.cfg.wwwroot + '/course/switchrole.php?'
                        + 'id=' + courseId
                        + '&sesskey=' + M.cfg.sesskey
                        + '&switchrole=0'
                        + '&returnurl=' + returnURL
            }).then(function(html) {
                $('#admin-menu-trigger').parent().append(html);
            });
        };

        return {
            init: self.init
        };
    }
);
