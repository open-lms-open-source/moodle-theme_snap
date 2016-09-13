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
define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], function($, ajax, notification, templates) {
    
    // Main function.
    var init = function(courseConfig) {
        var currentlyUnavailableSections = courseConfig.unavailablesections.map(Number),
        currentlyUnavailableMods =  courseConfig.unavailablemods.map(Number);

        $(document).on( "modulecompleted",
            function() {
                ajax.call([
                    {
                        methodname: 'theme_snap_course_completion',
                        args: {
                            courseshortname: courseConfig.shortname,
                            unavailablesections: currentlyUnavailableSections.join(','),
                            unavailablemods: currentlyUnavailableMods.join(',')
                        },
                        done: function(response) {

                            // Remove availability warnings for sections.
                            if (Object.keys(response.newlyavailablesectionhtml).length) {
                                for (var s in response.newlyavailablesectionhtml) {
                                     $('#section-' + s + ' .content > .snap-restrictions-meta').remove();
                                }
                            }

                            /**
                             * Update elements with newly available html.
                             * Elements can either be sections or modules.
                             *
                             * @param {object} availableHTML - response json
                             * @param {string} typeKey - string (either 'section' or 'module')
                             */
                            var updateNewlyAvailableHTML = function(availableHTML, typeKey) {
                                if (!Object.keys(availableHTML).length) {
                                    // There are no newly available elements which require updating.
                                    return;
                                }
                                for (var id in availableHTML) {
                                    var html = availableHTML[id];
                                    var baseSelector = '#' + typeKey + '-' + id;
                                    if (typeKey === 'module') {
                                        $(baseSelector).replaceWith(html);
                                    } else {
                                        if ($(baseSelector + ' ul.section').length) {
                                            $(baseSelector + ' ul.section').replaceWith(html);
                                        } else {
                                            $(baseSelector + ' nav.section_footer').before(html);
                                        }
                                        $(baseSelector + ' > .snap-restrictions-meta .text-danger').replaceWith('');
                                    }
                                }
                            };

                            // Update newly available sections with released html.
                            updateNewlyAvailableHTML(response.newlyavailablesectionhtml, 'section');

                            // Update newly available modules with released html.
                            updateNewlyAvailableHTML(response.newlyavailablemodhtml, 'module');

                            // Update TOC.
                            templates.render('theme_snap/course_toc', response.toc)
                                .done(function(result) {
                                    $('#course-toc').replaceWith(result);
                                });
               
                            // Update current state.
                            currentlyUnavailableSections = response.unavailablesections.split(',').map(Number);
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
