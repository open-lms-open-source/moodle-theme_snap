<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course section actions.
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\webservice;

use theme_snap\services\course;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

/**
 * Course section action web service.
 *
 * Note: This web service is used in place of the core moodle /course/rest.php endpoint because that endpoint does not
 * return any json for toggling course section highlighting.
 * Also, this web service returns additional json for models affected by course actions - e.g. the updated course
 * section action and the course TOC.
 *
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_course_sections extends \external_api {
    /**
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        $parameters = [
            'courseshortname' => new \external_value(PARAM_TEXT, 'Course shortname', VALUE_REQUIRED),
            'action' => new \external_value(PARAM_ALPHA, 'Action to perform: visibility|highlight|delete', VALUE_REQUIRED),
            'sectionnumber' => new \external_value(PARAM_INT, 'Section number', VALUE_REQUIRED),
            'value' => new \external_value(PARAM_INT,
                    'Value corresponding to action - e.g. visibility 0 is hide, highlight 1 would highlight the section',
                    VALUE_REQUIRED)
        ];
        return new \external_function_parameters($parameters);
    }

    /**
     * @return \external_single_structure
     */
    public static function service_returns() {
        $keys = [
            'actionmodel' => new \external_single_structure(
                definition_helper::define_class_for_webservice('theme_snap\renderables\course_action_section_base'),
                'Action model',
                VALUE_OPTIONAL
            ),
            'toc' => new \external_single_Structure(
                definition_helper::define_class_for_webservice('theme_snap\renderables\course_toc'),
                'Table of contents',
                VALUE_REQUIRED
            )
        ];

        return new \external_single_structure($keys, 'course_completion');
    }

    /**
     * @param string $courseshortname
     * @param string $unavailablesections
     * @param string $unavailablemods
     * @return array
     */
    public static function service($courseshortname, $action, $sectionnumber, $value) {
        $service = course::service();
        switch ($action) {
            case 'highlight' :
                return $service->highlight_section($courseshortname, $sectionnumber, $value);
            case 'visibility' :
                return $service->set_section_visibility($courseshortname, $sectionnumber, $value);
            case 'delete' :
                return $service->delete_section($courseshortname, $sectionnumber);
        }
        throw new \coding_exception('Invalid action selected :' . $action);
    }
}
