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

namespace theme_snap\webservice;

use core_external\external_api;
use core_external\external_value;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

/**
 * Course section progress service
 * @author    Dayana Pardo
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_course_section_progress extends external_api {

    /**
     * Defines the input parameters of the web service.
     * @return external_function_parameters
     */
    public static function service_parameters() {
        return new external_function_parameters([
            'sectionid' => new external_value(PARAM_INT, 'Section ID (0 or null to get all sections)', VALUE_DEFAULT, 0),
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Defines the response of the web service.
     * @return external_single_structure|external_multiple_structure
     */
    public static function service_returns() {
        $sectionstructure = new external_single_structure([
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'completed' => new external_value(PARAM_INT, 'Number of completed activities'),
            'total' => new external_value(PARAM_INT, 'Total number of activities with completion'),
            'hasprogress' => new external_value(PARAM_BOOL, 'Whether section has activities with completion'),
            'iscomplete' => new external_value(PARAM_BOOL, 'Whether section is completely finished'),
        ]);

        // Return can be either a single section or multiple sections.
        return new external_single_structure([
            'sections' => new external_multiple_structure(
                $sectionstructure,
                'Array of section progress data'
            ),
        ]);
    }

    /**
     * Get the progress data for a section or all sections.
     * If sectionid is 0 or null, returns all sections. Otherwise returns only the specified section.
     * @param int $sectionid Section ID (0 for all sections)
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return array Section progress data(s)
     */
    public static function service($sectionid, $userid, $courseid) {
        $params = self::validate_parameters(self::service_parameters(),
            [
                'sectionid' => $sectionid,
                'userid' => $userid,
                'courseid' => $courseid,
            ]);

        $course = get_course($courseid);
        $user = \core_user::get_user($userid);

        // If sectionid is 0 or null, get all sections.
        // Note: empty(0) returns true, so 0 is treated as "all sections".
        if (empty($params['sectionid']) || $params['sectionid'] == 0) {
            $alldata = \theme_snap\local::get_section_completion_data($course, $user, null);
            // Convert associative array to indexed array with sectionid included.
            $sections = [];
            foreach ($alldata as $sectionid => $data) {
                $sections[] = array_merge(['sectionid' => $sectionid], $data);
            }
            return ['sections' => $sections];
        }

        // Otherwise, get single section.
        $data = \theme_snap\local::get_section_completion_data(
            $course,
            $user,
            $params['sectionid']
        );

        return ['sections' => [array_merge(['sectionid' => $params['sectionid']], $data)]];
    }
}