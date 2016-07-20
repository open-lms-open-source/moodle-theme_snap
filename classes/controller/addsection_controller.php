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

namespace theme_snap\controller;

defined('MOODLE_INTERNAL') || die();

/**
 * Add section Controller.
 * Handles requests to add a new section
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addsection_controller extends controller_abstract {
    /**
     * Do any security checks needed for the passed action
     *
     * @param string $action
     */
    public function require_capability($action) {
        global $PAGE;

        if ($action !== 'addsection') {
            throw new \coding_exception("Missing capability check for $action action");
        }
        require_capability('moodle/course:update', $PAGE->context);
    }

    /**
     * Add a new section with the provided title and (optional) summary
     *
     * @return string
     */
    public function addsection_action() {
        global $CFG, $PAGE, $DB;

        require_once($CFG->dirroot.'/course/lib.php');

        $sectioname = optional_param('newsection', '', PARAM_TEXT);
        $summary = optional_param('summary', '', PARAM_RAW);
        $sectioname = $sectioname === '' ? null : $sectioname;

        require_sesskey();

        $courseid = $PAGE->context->get_course_context()->instanceid;

        $course = course_get_format($courseid)->get_course();
        $course->numsections++;

        course_get_format($course)->update_course_format_options(
            array('numsections' => $course->numsections)
        );
        course_create_sections_if_missing($course, range(0, $course->numsections));

        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info($course->numsections, MUST_EXIST);
        $DB->set_field('course_sections', 'name', $sectioname, array('id' => $section->id));
        $DB->set_field('course_sections', 'summary', $summary, array('id' => $section->id));
        $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, array('id' => $section->id));
        rebuild_course_cache($course->id);

        redirect(course_get_url($course, $section->section));
    }
}
