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
 * Returns the current list of course-module IDs hidden from the Snap TOC.
 *
 * Called by the course index JS after any section cm-list update (e.g. after
 * a module is duplicated) so the browser can refresh its cached list and
 * immediately hide newly toc_hidden activities without a page reload.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_get_hidden_toc_activities extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function service_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * @return external_single_structure
     */
    public static function service_returns() {
        return new external_single_structure([
            'cmids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course module ID'),
                'Course module IDs currently hidden from the TOC'
            ),
        ]);
    }

    /**
     * Return the cmids hidden from the TOC for the given course.
     *
     * @param int $courseid
     * @return array{cmids: int[]}
     */
    public static function service(int $courseid): array {
        $params = self::validate_parameters(
            self::service_parameters(),
            ['courseid' => $courseid]
        );

        $course  = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context($context);

        return ['cmids' => \theme_snap\toc_hidden::get_cmids_for_course($course->id)];
    }
}
