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

defined('MOODLE_INTERNAL') || die();

use \theme_snap\activity;

global $CFG;
require_once($CFG->dirroot.'/mod/assign/tests/base_test.php');

/**
 * Testing for theme/snap/classes/activity.php
 *
 * @package  theme_snap
 * @copyright  2017 Blackboard inc.
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_acitvity_test extends advanced_testcase {

    public function test_assignment_user_extension_date() {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $student = $dg->create_user();
        $teacher = $dg->create_user();
        $course = $dg->create_course();
        $dg->enrol_user($student->id, $course->id, 'student');
        $dg->enrol_user($teacher->id, $course->id, 'teacher');
        $assigndg = $dg->get_plugin_generator('mod_assign');
        $assign = $assigndg->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $context = context_module::instance($cm->id);
        $assign = new testable_assign($context, $cm, $course);

        // Test extension not set.
        $this->setUser($student);
        $extdate = activity::assignment_user_extension_date($assign->get_instance()->id);
        $this->assertFalse($extdate);

        // Test extension set.
        $this->setUser($teacher);
        $now = time();
        $assign->save_user_extension($student->id, $now);
        $this->setUser($student);
        $extdate = activity::assignment_user_extension_date($assign->get_instance()->id);
        $this->assertEquals($now, $extdate);
    }
}
