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
 * Course conditionals function.
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {
    
    // Main function.
    var init = function(courseshortname) {
        var currentlyUnavailableSections = M.theme_snap.courseconfig.unavailablesections.map(Number),
        currentlyUnavailableMods =  M.theme_snap.courseconfig.unavailablemods.map(Number);

        $(document).on( "modulecompleted",
            function() {
                ajax.call([
                    {
                        methodname: 'theme_snap_course_completion',
                        args: {
                            courseshortname: courseshortname,
                            unavailablemods: currentlyUnavailableMods.join(',')
                        },
                        done: function(response) {
                            var arrayDiff = function(arr1, arr2) {
                                var ret = arr1.filter(function(val) {
                                    return arr2.indexOf(val)==-1;
                                });
                                return ret;
                            };

                            // Remove availability warnings for sections.
                            var unavailableSections = [],
                                newlyAvailableSections = [];
                            if (response.unavailablesections !== '') {
                                unavailableSections = response.unavailablesections.split(',').map(Number);
                            }
                            newlyAvailableSections = arrayDiff(currentlyUnavailableSections, unavailableSections);
                            for (var s in newlyAvailableSections) {
                                var sectionId = newlyAvailableSections[s];
                                $('#section-' + sectionId + ' .content > .snap-restrictions-meta').remove();
                            }

                            // We can't use the same technique as sections for mods as some mods will need content adding.
                            if (Object.keys(response.newlyavailablemodhtml).length) {
                                var newlyAvailableHTML = response.newlyavailablemodhtml;
                                for (var modId in newlyAvailableHTML) {
                                    var modhtml = newlyAvailableHTML[modId];
                                    $('#module-' + modId).replaceWith(modhtml);
                                }
                            }

                            // Update current state.
                            currentlyUnavailableSections = unavailableSections.map(Number);
                            currentlyUnavailableMods = response.unavailablemods.split(',').map(Number);

                        },
                        fail: function(response) {
                            notification.exception(response);
                        }
                    }
                ], true, true);
            }
        );
    };
    return init;
});
