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
 * Base class for unit tests for mod_assign.
 *
 * @package    mod_assign
 * @category   phpunit
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

use \theme_snap\local;
use \theme_snap\activity;

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

/**
 * Unit tests for theme snap that rely on mod/assign present in course.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class theme_snap_assign_test extends mod_assign_base_testcase {

    private $assignments = array();

    protected function setUp() {
        parent::setUp();

        $this->setUser($this->editingteachers[0]);
        $this->create_instance();
        $assign = $this->create_instance(array('duedate' => time(),
                                               'attemptreopenmethod' => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                                               'maxattempts' => 3,
                                               'submissiondrafts' => 1,
                                               'assignsubmission_onlinetext_enabled' => 1));

        $this->assignments[] = $assign;

        // Add a submission.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Mark the submission.
        $this->setUser($this->teachers[0]);
        $data = new stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        // This is required so that the submissions timemodified > the grade timemodified.
        sleep(2);

        // Edit the submission again.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // This is required so that the submissions timemodified > the grade timemodified.
        sleep(2);

        // Allow the student another attempt.
        $this->teachers[0]->ignoresesskey = true;
        $this->setUser($this->teachers[0]);
        $assign->testable_process_add_attempt($this->students[0]->id);

        // Add another submission.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text 2',
                                         'format' => FORMAT_HTML);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking (again).
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);
    }

    public function test_assign_reopened_and_resubmitted() {
        $modinfo = get_fast_modinfo($this->course);
        $assign = $this->assignments[0];
        $assigncm = $modinfo->instances['assign'][$assign->get_instance()->id];
        $expnumsubmissions = 1;
        $expnumrequiregrading = 1;

        $this->setUser($this->editingteachers[0]);
        $actual = activity::assign_meta($assigncm);
        $this->assertSame($actual->numsubmissions, $expnumsubmissions);
        $this->assertSame($actual->numrequiregrading, $expnumrequiregrading);
        $this->assertTrue($actual->isteacher);

        $this->setUser($this->teachers[0]);
        $actual = activity::assign_meta($assigncm);
        $this->assertSame($actual->numsubmissions, $expnumsubmissions);
        $this->assertSame($actual->numrequiregrading, $expnumrequiregrading);
        $this->assertTrue($actual->isteacher);

        $this->setUser($this->students[0]);
        $actual = activity::assign_meta($assigncm);
        $this->assertFalse($actual->isteacher);
    }

    public function test_assign_upcoming_deadlines() {
        global $USER;
        // Check the overview as the different users.
        $this->setUser($this->students[0]);
        $deadlines = local::upcoming_deadlines($USER->id);
        $this->assertCount(1, $deadlines);

        $this->setUser($this->teachers[0]);
        $deadlines = local::upcoming_deadlines($USER->id);
        $this->assertCount(1, $deadlines);

        $this->setUser($this->editingteachers[0]);
        $deadlines = local::upcoming_deadlines($USER->id);
        $this->assertCount(1, $deadlines);
    }

    public function test_participant_count() {
        $courseid = $this->course->id;
        $actual = local::course_participant_count($courseid);
        $expected = count($this->students);
        $this->assertSame($expected, $actual);

        $this->create_extra_users();
        $actual = local::course_participant_count($courseid);
        $expected = count($this->students);
        $this->assertSame($expected, $actual);
    }

    public function test_no_course_completion_progress() {
        $actual = local::course_completion_progress($this->course);
        // TODO this is a rubbish test.
        $this->assertInstanceOf('stdClass', $actual);
    }

    public function test_no_course_feedback() {
        $actual = local::course_feedback($this->course);
        // TODO this is a rubbish test.
        $this->assertInstanceOf('stdClass', $actual);
    }

    public function test_no_course_image() {
        $actual = local::get_course_image($this->course->id);
        $this->assertFalse($actual);
    }

    public function test_assign_ungraded() {
        $actual = activity::assign_ungraded([]);
        $this->assertFalse($actual);

        $actual = activity::assign_ungraded([$this->course->id]);
        $this->assertCount(1, $actual);
    }

    public function test_quiz_ungraded() {
        $actual = activity::quiz_ungraded([]);
        $this->assertFalse($actual);

        $actual = activity::quiz_ungraded([$this->course->id]);
        $this->assertCount(0, $actual);

        // TODO need a test with actually generated quizzes.
    }

    public function test_events_graded() {
        $this->setUser($this->students[0]);
        $actual = activity::events_graded();
        $this->assertCount(1, $actual);

        $this->setUser($this->students[1]);
        $actual = activity::events_graded();
        $this->assertCount(0, $actual);
    }

    public function test_all_ungraded() {
        $this->setUser($this->teachers[0]);
        $actual = local::all_ungraded($this->teachers[0]->id);
        $this->assertcount(1, $actual);

        $this->setUser($this->teachers[1]);
        $actual = local::all_ungraded($this->teachers[1]->id);
        $this->assertcount(1, $actual);

        $this->setUser($this->editingteachers[0]);
        $actual = local::all_ungraded($this->editingteachers[0]->id);
        $this->assertcount(1, $actual);
    }
}
