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
 * Testing for Render Edit Mode in theme Snap.
 *
 * @package   theme_snap
 * @author    Diego Monroy
 * @copyright 2022 Open LMS. (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../lib/badgeslib.php');
/**
 * Testing for Render Edit Mode in theme Snap.
 *
 * @package   theme_snap
 * @copyright 2022 Open LMS. (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class render_edit_mode_test extends \advanced_testcase {

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_render_edit_mode() {
        global $DB, $PAGE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $coursecontext = \context_course::instance($course->id);
        $PAGE->set_pagetype('course-view-' . $course->format);
        // Assign capability for viewing course for students.
        assign_capability('moodle/course:view', CAP_ALLOW, $studentrole->id, $coursecontext->id, true);
        // Assign capability for update course for teachers.
        assign_capability('moodle/course:update', CAP_ALLOW, $teacherrole->id, $coursecontext->id, true);
        // Enrol student to course.
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        // Enrol teacher to course.
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // Setting general variables for testing.
        $courseid = $course->id;
        $pagetype = $PAGE->pagetype;
        $courseformat = false;
        $needle = '<div id="snap-editmode" class="snap-editmode">';

        // Testing with not valid values.
        $this->setAdminUser();
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertFalse($render);

        // Testing as Student.
        $this->setUser($student->id);

        // Format Weeks Testing as teacher.
        $courseformat = 'weeks';
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertIsString($render);
        $this->assertStringNotContainsString($needle, $render);

        // Format Topics Testing.
        $courseformat = 'topics';
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertIsString($render);
        $this->assertStringNotContainsString($needle, $render);

        // Format Tiles Testing.
        $courseformat = 'tiles';
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertIsString($render);
        $this->assertStringNotContainsString($needle, $render);

        // Testing as Teacher.
        $this->setUser($teacher->id);

        // Format Weeks Testing as teacher.
        $courseformat = 'weeks';
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertIsString($render);
        $this->assertStringNotContainsString($needle, $render);

        // Format Topics Testing.
        $courseformat = 'topics';
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertIsString($render);
        $this->assertStringNotContainsString($needle, $render);

        // Format Tiles Testing.
        $courseformat = 'tiles';
        $render = \theme_snap\output\shared::render_edit_mode($courseid, $courseformat, $pagetype);
        $this->assertIsString($render);
        $this->assertStringContainsString($needle, $render);
    }
}
