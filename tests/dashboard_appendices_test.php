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
 * Testing for dashboard appendices.
 *
 * @package   theme_snap
 * @copyright 2020 Open LMS. (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap;
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../lib/badgeslib.php');
/**
 * Testing for dashboard appendices.
 *
 * @package   theme_snap
 * @copyright 2020 Open LMS. (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_appendices_test extends \advanced_testcase {

    public function test_dashboard_shows_open_reports_experimental() {
        global $CFG, $DB, $COURSE;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $coursecontext = \context_course::instance($course->id);
        // Assign capability for viewing reports for students.
        assign_capability('block/reports:viewown', CAP_ALLOW, $studentrole->id, $coursecontext->id, true);
        // Assign capability for viewing reports for teachers.
        assign_capability('block/reports:view', CAP_ALLOW, $teacherrole->id, $coursecontext->id, true);
        // Enrol student to course.
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        // Enrol teacher to course.
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        // By default, is not listed for admins nor teachers, nor students.
        $this->setAdminUser();
        $appendicesoutput = \theme_snap\output\shared::appendices();
        $this->assertStringNotContainsString('blocks/reports/view.php?action=dashboardce', $appendicesoutput);
        $this->setUser($student->id);
        $appendicesoutput = \theme_snap\output\shared::appendices();
        $this->assertStringNotContainsString('blocks/reports/view.php?action=dashboardce', $appendicesoutput);
        $this->setUser($teacher->id);
        $appendicesoutput = \theme_snap\output\shared::appendices();
        $this->assertStringNotContainsString('blocks/reports/view.php?action=dashboardce', $appendicesoutput);

        // When the flag is present, the option get listed.
        $CFG->block_reports_enable_dashboardce = true;
        // For admin.
        $this->setAdminUser();
        $appendicesoutput = \theme_snap\output\shared::appendices();
        $this->assertStringContainsString('blocks/reports/view.php?action=dashboardce', $appendicesoutput);
        // For student.
        $this->setUser($student->id);
        // Simulate being inside the course.
        $COURSE = $course;
        $appendicesoutput = \theme_snap\output\shared::appendices();
        $this->assertStringContainsString('blocks/reports/view.php?action=dashboardce', $appendicesoutput);
        // For teacher.
        $this->setUser($teacher->id);
        // Simulate being inside the course.
        $COURSE = $course;
        $appendicesoutput = \theme_snap\output\shared::appendices();
        $this->assertStringContainsString('blocks/reports/view.php?action=dashboardce', $appendicesoutput);

    }
}
