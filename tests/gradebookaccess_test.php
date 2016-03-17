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
 * Test gradebook_accessible functionality.
 *
 * @package   theme_snap
 * @category  phpunit
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG, $DB, $COURSE;

require_once(__DIR__.'/../../../local/mr/bootstrap.php');
require_once($CFG->dirroot.'/theme/snap/renderers/snap_shared.php');

class theme_snap_gradebookaccess_testcase extends advanced_testcase {

    protected function setUp() {
        global $USER;
        // The user we are going to test this on.
        $this->setAdminUser();
        $this->user = $USER;
    }

    public function test_gradebookaccess_gradesavailableforstuds() {
        global $DB, $COURSE, $PAGE;

        $this->resetAfterTest(true);

        // Get the id for the necessary roles.
        $studentrole = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $editteacherrole = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $this->assertEquals(5, $studentrole);
        $this->assertEquals(3, $editteacherrole);

        // Create a course with grades enabled to students.
        $course1 = $this->getDataGenerator()->create_course(array('showgrades'=>1));
        $this->course = $course1;
        $PAGE->set_course($this->course);
        $courseid = $course1->id;
        $this->assertEquals(1, $COURSE->showgrades);

        // Create two users.
        $studentuser = $this->getDataGenerator()->create_user(array('username'=>'stud1', 'firstname'=>'Wayne'));
        $studentuserid = $studentuser->id;
        $teacheruser = $this->getDataGenerator()->create_user(array('username'=>'teach1', 'firstname'=>'Jamie'));
        $teacheruserid = $teacheruser->id;

        // Enrol users to created course.
        $this->getDataGenerator()->enrol_user($studentuserid, $courseid, $studentrole);
        $this->getDataGenerator()->enrol_user($teacheruserid, $courseid, $editteacherrole);

        $this->setUser($teacheruser); // Set the teacher as active user.

        // Check functionality of gradebook_accessible.
        $coursecontext = context_course::instance($COURSE->id);
        $isavailable = snap_shared::gradebook_accessible($coursecontext);
        $this->assertTrue($isavailable); // Always accessible for teachers.

        $this->setUser($studentuser); // Set the student as active user.
        $isavailable = snap_shared::gradebook_accessible($coursecontext);
        $this->assertTrue($isavailable); // As long as showgrades is active, must be available for studs.
    }

	public function test_gradebookaccess_gradesnotavailableforstuds() {
        global $DB, $COURSE, $PAGE;

        $this->resetAfterTest(true);

        // Get the id for the necessary roles.
        $studentrole = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $editteacherrole = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $this->assertEquals(5, $studentrole);
        $this->assertEquals(3, $editteacherrole);

        // Create a course with grades disabled to students.
        $course2 = $this->getDataGenerator()->create_course(array('showgrades'=>0));
        $this->course = $course2;
        $PAGE->set_course($this->course);
        $courseid = $course2->id;
        $this->assertEquals(0, $COURSE->showgrades);

        // Create two users.
        $studentuser = $this->getDataGenerator()->create_user(array('username'=>'stud2', 'firstname'=>'Mike'));
        $studentuserid = $studentuser->id;
        $teacheruser = $this->getDataGenerator()->create_user(array('username'=>'teach2', 'firstname'=>'John'));
        $teacheruserid = $teacheruser->id;

        // Enrol users to created course.
        $this->getDataGenerator()->enrol_user($studentuserid, $courseid, $studentrole);
        $this->getDataGenerator()->enrol_user($teacheruserid, $courseid, $editteacherrole);

        $this->setUser($teacheruser); // Set the teacher as active user.

        // Check functionality of gradebook_accessible.
        $coursecontext = context_course::instance($COURSE->id);
        $isavailable = snap_shared::gradebook_accessible($coursecontext);
        $this->assertTrue($isavailable); // Always accessible for teachers.

        $this->setUser($studentuser); // Set the student as active user.
        $isavailable = snap_shared::gradebook_accessible($coursecontext);
        $this->assertFalse($isavailable); // As long as showgrades is not active, mustn't be available for studs.
    }
}