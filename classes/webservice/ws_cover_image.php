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
 * @copyright Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_cover_image extends \external_api {
    /**
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        $parameters = [
            'params' => new \external_single_structure([
                'imagedata' => new \external_value(PARAM_TEXT, 'Image data', VALUE_REQUIRED),
                'imagefilename' => new \external_value(PARAM_TEXT, 'Image filename', VALUE_REQUIRED),
                'categoryid' => new \external_value(PARAM_INT, 'Category Id', VALUE_OPTIONAL),
                'courseshortname' => new \external_value(PARAM_TEXT, 'Course shortname', VALUE_OPTIONAL)
            ], 'Params wrapper - just here to accommodate optional values', VALUE_REQUIRED)
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
     * @param string $imagedata
     * @param string $imagefilename
     * @param int $categoryid
     * @param string $courseshortname
     * @return array
     */
    public static function service($params) {
        $service = course::service();

        $params = self::validate_parameters(self::service_parameters(), ['params' => $params])['params'];

        if (!empty($params['courseshortname'])) {
            $course = $service->coursebyshortname($params['courseshortname'], 'id');
            if ($course->id === SITEID) {
                $context = \context_system::instance();
            } else {
                $context = \context_course::instance($course->id);
            }
        } else if (!empty($params['categoryid'])) {
            $context = get_category_or_system_context($params['categoryid']);
        } else {
            throw new \coding_exception('Error - courseshortname OR categoryid must be provided');
        }
        self::validate_context($context);

        $coverimage = $service->setcoverimage($context, $params['imagedata'], $params['imagefilename']);
        return $coverimage;
    }
}
