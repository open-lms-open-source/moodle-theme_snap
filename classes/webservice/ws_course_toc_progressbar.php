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

use theme_snap\services\course;
use core_external\external_api;
use core_external\external_value;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

defined('MOODLE_INTERNAL') || die();

/**
 * Course TOC progress bar service
 * @author    Daniel Cifuentes
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_course_toc_progressbar extends external_api {

    /**
     * Defines the input parameters of the web service.
     * @return external_function_parameters
     */
    public static function service_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT),
            'courseid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Defines the response of the web service.
     * @return external_single_structure
     */
    public static function service_returns() {
        return new external_single_structure(
            [
                'courseprogress' => new external_value(PARAM_TEXT, 'Course progress'),
                'progresspercentage' => new external_value(PARAM_TEXT, 'Course progress percentage (%)'),
            ]
        );
    }

    /**
     * Update the progress bar value from the Course TOC in Snap.
     * @param $userid
     * @param $courseid
     */
    public static function service($userid, $courseid) {
        global $DB;

        $params = self::validate_parameters(self::service_parameters(),
            [
                'userid' => $userid,
                'courseid' => $courseid,
            ]);
        $course = get_course($courseid);
        $user = \core_user::get_user($userid);

        $data = \theme_snap\output\core_renderer::get_course_completion_data($course, $user);

        return [
            'courseprogress' => get_string('progress', 'theme_snap').': '. $data['courseprogress'],
            'progresspercentage' => $data['progresspercentage'],
        ];
    }
}
