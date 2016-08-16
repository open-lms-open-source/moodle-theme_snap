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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

/**
 * Cover image web service
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_cover_image extends \external_api {
    /**
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        $parameters = [
            'courseshortname' => new \external_value(PARAM_TEXT, 'Course shortname', VALUE_REQUIRED),
            'imagedata' => new \external_value(PARAM_TEXT, 'Image data', VALUE_REQUIRED),
            'imagefilename' => new \external_value(PARAM_TEXT, 'Image filename', VALUE_REQUIRED)
        ];
        return new \external_function_parameters($parameters);
    }

    /**
     * @return \external_single_structure
     */
    public static function service_returns() {
        $keys = [
            'success' => new \external_value(PARAM_BOOL, 'Was the cover image successfully changed', VALUE_REQUIRED)
        ];

        return new \external_single_structure($keys, 'coverimage');
    }

    /**
     * @param string $courseshortname
     * @param string $imagedata
     * @param string $imagefilename
     * @return array
     */
    public static function service($courseshortname, $imagedata, $imagefilename) {
        $service = course::service();

        $course = $service->coursebyshortname($courseshortname, 'id');
        $context = \context_course::instance($course->id);
        self::validate_context($context);

        $coverimage = $service->setcoverimage($courseshortname, $imagedata, $imagefilename);
        return $coverimage;
    }
}
