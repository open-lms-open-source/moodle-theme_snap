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
namespace theme_snap;
defined('MOODLE_INTERNAL') || die();
global $CFG;

use theme_snap\local;
use theme_snap\activity;
require_once($CFG->dirroot. '/mod/assign/tests/fixtures/testable_assign.php');

/**
 * Unit tests for theme snap that rely on mod/assign present in course.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_test extends \advanced_testcase {


    /** @var Default number of students to create */
    const DEFAULT_STUDENT_COUNT = 3;
    /** @var Default number of teachers to create */
    const DEFAULT_TEACHER_COUNT = 2;
    /** @var Default number of editing teachers to create */
    const DEFAULT_EDITING_TEACHER_COUNT = 2;
    /** @var Optional extra number of students to create */
    const EXTRA_STUDENT_COUNT = 40;
    /** @var Optional number of suspended students */
    const EXTRA_SUSPENDED_COUNT = 10;
    /** @var Optional extra number of teachers to create */
    const EXTRA_TEACHER_COUNT = 5;
    /** @var Optional extra number of editing teachers to create */
    const EXTRA_EDITING_TEACHER_COUNT = 5;
    /** @var Number of groups to create */
    const GROUP_COUNT = 6;

    /** @var \stdClass $course New course created to hold the assignments */
    protected $course = null;

    /** @var array $teachers List of DEFAULT_TEACHER_COUNT teachers in the course*/
    protected $teachers = null;

    /** @var array $editingteachers List of DEFAULT_EDITING_TEACHER_COUNT editing teachers in the course */
    protected $editingteachers = null;

    /** @var array $students List of DEFAULT_STUDENT_COUNT students in the course*/
    protected $students = null;

    /** @var array $extrateachers List of EXTRA_TEACHER_COUNT teachers in the course*/
    protected $extrateachers = null;

    /** @var array $extraeditingteachers List of EXTRA_EDITING_TEACHER_COUNT editing teachers in the course*/
    protected $extraeditingteachers = null;

    /** @var array $extrastudents List of EXTRA_STUDENT_COUNT students in the course*/
    protected $extrastudents = null;

    /** @var array $extrasuspendedstudents List of EXTRA_SUSPENDED_COUNT students in the course*/
    protected $extrasuspendedstudents = null;

    /** @var array $groups List of 10 groups in the course */
    protected $groups = null;

    /**
     * Setup function - we will create a course and add an assign instance to it.
     */
    protected function setUp(): void {
        global $DB;

        $this->resetAfterTest(true);

        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $this->teachers = array();
        for ($i = 0; $i < self::DEFAULT_TEACHER_COUNT; $i++) {
            array_push($this->teachers, $this->getDataGenerator()->create_user());
        }

        $this->editingteachers = array();
        for ($i = 0; $i < self::DEFAULT_EDITING_TEACHER_COUNT; $i++) {
            array_push($this->editingteachers, $this->getDataGenerator()->create_user());
        }

        $this->students = array();
        for ($i = 0; $i < self::DEFAULT_STUDENT_COUNT; $i++) {
            array_push($this->students, $this->getDataGenerator()->create_user());
        }

        $this->groups = array();
        for ($i = 0; $i < self::GROUP_COUNT; $i++) {
            array_push($this->groups, $this->getDataGenerator()->create_group(array('courseid'=>$this->course->id)));
        }

        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        foreach ($this->teachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                $this->course->id,
                $teacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', array('shortname'=>'editingteacher'));
        foreach ($this->editingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user($editingteacher->id,
                $this->course->id,
                $editingteacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $editingteacher);
        }

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        foreach ($this->students as $i => $student) {
            $this->getDataGenerator()->enrol_user($student->id,
                $this->course->id,
                $studentrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $student);
        }
    }

    /*
     * For tests that make sense to use alot of data, create extra students/teachers.
     */
    protected function create_extra_users() {
        global $DB;
        $this->extrateachers = array();
        for ($i = 0; $i < self::EXTRA_TEACHER_COUNT; $i++) {
            array_push($this->extrateachers, $this->getDataGenerator()->create_user());
        }

        $this->extraeditingteachers = array();
        for ($i = 0; $i < self::EXTRA_EDITING_TEACHER_COUNT; $i++) {
            array_push($this->extraeditingteachers, $this->getDataGenerator()->create_user());
        }

        $this->extrastudents = array();
        for ($i = 0; $i < self::EXTRA_STUDENT_COUNT; $i++) {
            array_push($this->extrastudents, $this->getDataGenerator()->create_user());
        }

        $this->extrasuspendedstudents = array();
        for ($i = 0; $i < self::EXTRA_SUSPENDED_COUNT; $i++) {
            array_push($this->extrasuspendedstudents, $this->getDataGenerator()->create_user());
        }

        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        foreach ($this->extrateachers as $i => $teacher) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                $this->course->id,
                $teacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $teacher);
        }

        $editingteacherrole = $DB->get_record('role', array('shortname'=>'editingteacher'));
        foreach ($this->extraeditingteachers as $i => $editingteacher) {
            $this->getDataGenerator()->enrol_user($editingteacher->id,
                $this->course->id,
                $editingteacherrole->id);
            groups_add_member($this->groups[$i % self::GROUP_COUNT], $editingteacher);
        }

        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        foreach ($this->extrastudents as $i => $student) {
            $this->getDataGenerator()->enrol_user($student->id,
                $this->course->id,
                $studentrole->id);
            if ($i < (self::EXTRA_STUDENT_COUNT / 2)) {
                groups_add_member($this->groups[$i % self::GROUP_COUNT], $student);
            }
        }

        foreach ($this->extrasuspendedstudents as $i => $suspendedstudent) {
            $this->getDataGenerator()->enrol_user($suspendedstudent->id,
                $this->course->id,
                $studentrole->id, 'manual', 0, 0, ENROL_USER_SUSPENDED);
            if ($i < (self::EXTRA_SUSPENDED_COUNT / 2)) {
                groups_add_member($this->groups[$i % self::GROUP_COUNT], $suspendedstudent);
            }
        }
    }

    /**
     * Convenience function to create a testable instance of an assignment.
     *
     * @param array $params Array of parameters to pass to the generator
     * @return mod_assign_testable_assign Testable wrapper around the assign class.
     */
    protected function create_instance($params=array()) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        if (!isset($params['course'])) {
            $params['course'] = $this->course->id;
        }
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('assign', $instance->id);
        $context = \context_module::instance($cm->id);
        return new \mod_assign_testable_assign($context, $cm, $this->course);
    }

    public function test_create_instance(): void {
        $this->assertNotEmpty($this->create_instance());
    }

    public function test_assign_reopened_and_resubmitted() {
        $this->setUser($this->editingteachers[0]);
        $this->create_instance();
        $assign = $this->create_instance(array('duedate' => time(),
                                               'attemptreopenmethod' => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
                                               'maxattempts' => 3,
                                               'submissiondrafts' => 1,
                                               'assignsubmission_onlinetext_enabled' => 1, ));

        // Add a submission.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new \stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML, );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Mark the submission.
        $this->setUser($this->teachers[0]);
        $data = new \stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);
        // TODO remove this next line when the above is fixed  to stop triggering debug messages.
        $this->resetDebugging();

        // This is required so that the submissions timemodified > the grade timemodified.
        $this->waitForSecond();

        // Edit the submission again.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // This is required so that the submissions timemodified > the grade timemodified.
        $this->waitForSecond();

        // Allow the student another attempt.
        $this->teachers[0]->ignoresesskey = true;
        $this->setUser($this->teachers[0]);
        $assign->testable_process_add_attempt($this->students[0]->id);

        // Add another submission.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new \stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text 2',
                                         'format' => FORMAT_HTML, );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking (again).
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        $modinfo = get_fast_modinfo($this->course);
        $assigncm = $modinfo->instances['assign'][$assign->get_instance()->id];
        $expnumsubmissions = 1;
        $expnumrequiregrading = 1;

        $this->setUser($this->editingteachers[0]);
        $actual = activity::assign_meta($assigncm);
        $this->assertSame($actual->numsubmissions, $expnumsubmissions);
        $this->assertSame($actual->numrequiregrading, $expnumrequiregrading);
        $this->assertTrue($actual->isteacher);

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

    public function test_assign_overdue() {
        global $PAGE, $CFG;

        $this->resetAfterTest();

        // Create one month overdue assignment.
        $this->setUser($this->teachers[0]);
        $assign = $this->create_instance([
            'duedate' => time() - (4 * DAYSECS),
            'assignsubmission_onlinetext_enabled' => 1,
            'name' => 'Overdue Assignment Test',
        ]);

        $this->setUser($this->students[0]);
        $modinfo = get_fast_modinfo($this->course);
        $assigncm = $modinfo->instances['assign'][$assign->get_instance()->id];
        $meta = activity::assign_meta($assigncm);
        $this->assertTrue($meta->overdue);

        // Make sure a submission record does not exist.
        $submission = activity::get_submission_row($this->course->id, $assigncm, 'submission', 'assignment');
        $this->assertEmpty($submission);

        // At one time there was an issue where the overdue status would flip after viewing the module.
        // Make sure this isn't happening by viewing the assignment.
        // Code taken from mod/assign/tests/events_test.php test_submission_status_viewed.
        $PAGE->set_url('/a_url');
        // View the assignment.
        $assign->view();

        // Viewing an assignment creates a submission record with a status of new.
        // Make sure a submission record now exists with a status of new.
        $submission = activity::get_submission_row($this->course->id, $assigncm, 'submission', 'assignment');
        $this->assertNotEmpty($submission);
        $this->assertEquals(ASSIGN_SUBMISSION_STATUS_NEW, $submission->status);
        $meta = activity::assign_meta($assigncm);
        // Ensure that assignment is still classed as overdue.
        $this->assertTrue($meta->overdue);
    }

    private function create_one_ungraded_submission() {
        $this->setUser($this->editingteachers[0]);
        $assign = $this->create_instance([
            'assignsubmission_onlinetext_enabled' => 1,
            'duedate' => time() - WEEKSECS,
        ]);

        // Add a submission.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new \stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML, );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);
        return $assign;
    }

    public function test_assign_ungraded() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $sixmonthsago = time() - YEARSECS / 2;

        $actual = activity::assign_ungraded([], $sixmonthsago);
        $this->assertCount(0, $actual);

        $actual = activity::assign_ungraded([$this->course->id], $sixmonthsago);
        $this->assertCount(0, $actual);

        $this->create_one_ungraded_submission();

        $actual = activity::assign_ungraded([$this->course->id], $sixmonthsago);
        $this->assertCount(1, $actual);

        // Enable cache.
        $CFG->theme_snap_grading_cache = true;

        $actual = activity::assign_ungraded([], $sixmonthsago);
        // Function should work the same way.
        $this->assertCount(0, $actual);

        $actual = activity::assign_ungraded([$this->course->id], $sixmonthsago);
        $this->assertCount(1, $actual);
        // Now lets check the cache content.
        $cache = \cache::make('theme_snap', 'course_users_assign_ungraded');
        $this->assertNotEmpty($cache->get($this->course->id));
        $users = $cache->get($this->course->id);
        $this->assertTrue(in_array($this->students[0]->id, $users));

        $manualplugin = enrol_get_plugin('manual');

        $enrol = $DB->get_record('enrol', array('courseid' => $this->course->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        // Unenrol user and capture event.
        $manualplugin->unenrol_user($enrol, $this->students[0]->id);
        $cache = \cache::make('theme_snap', 'course_users_assign_ungraded');
        $this->assertEmpty($cache->get($this->course->id));
    }

    public function test_events_graded() {
        $this->setUser($this->editingteachers[0]);
        $this->create_instance();
        $assign = $this->create_instance(array('duedate' => time(),
                                               'submissiondrafts' => 1,
                                               'assignsubmission_onlinetext_enabled' => 1, ));

        // Add a submission.
        $this->setUser($this->students[0]);
        $submission = $assign->get_user_submission($this->students[0]->id, true);
        $data = new \stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
                                         'text' => 'Submission text',
                                         'format' => FORMAT_HTML, );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // And now submit it for marking.
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $this->students[0]->id, true, false);

        // Mark the submission.
        $this->setUser($this->teachers[0]);
        $data = new \stdClass();
        $data->grade = '50.0';
        $assign->testable_apply_grade_to_user($data, $this->students[0]->id, 0);

        // TODO remove this next line when the above is fixed  to stop triggering debug messages.
        $this->resetDebugging();

        $this->setUser($this->students[0]);
        $actual = activity::events_graded();
        $this->assertCount(1, $actual);

        $this->setUser($this->students[1]);
        $actual = activity::events_graded();
        $this->assertCount(0, $actual);
    }

    public function test_all_ungraded() {

        $sixmonthsago = time() - YEARSECS / 2;
        $expected = 0;

        $this->setUser($this->teachers[0]);
        $actual = local::all_ungraded($this->teachers[0]->id, $sixmonthsago);
        $this->assertcount($expected, $actual);

        $this->setUser($this->teachers[1]);
        $actual = local::all_ungraded($this->teachers[1]->id, $sixmonthsago);
        $this->assertcount($expected, $actual);

        $this->setUser($this->editingteachers[0]);
        $actual = local::all_ungraded($this->editingteachers[0]->id, $sixmonthsago);
        $this->assertcount($expected, $actual);

        $this->create_one_ungraded_submission();
        $expected = 1;

        $this->setUser($this->teachers[0]);
        $actual = local::all_ungraded($this->teachers[0]->id, $sixmonthsago);
        $this->assertcount($expected, $actual);

        $this->setUser($this->teachers[1]);
        $actual = local::all_ungraded($this->teachers[1]->id, $sixmonthsago);
        $this->assertcount($expected, $actual);

        $this->setUser($this->editingteachers[0]);
        $actual = local::all_ungraded($this->editingteachers[0]->id, $sixmonthsago);
        $this->assertcount($expected, $actual);

        // Limit time to after the assignment is due.
        $afterduedate = time() - WEEKSECS;

        $this->setUser($this->teachers[0]);
        $actual = local::all_ungraded($this->teachers[0]->id, $afterduedate);
        $this->assertcount(0, $actual);

        $this->setUser($this->teachers[1]);
        $actual = local::all_ungraded($this->teachers[1]->id, $afterduedate);
        $this->assertcount(0, $actual);

        $this->setUser($this->editingteachers[0]);
        $actual = local::all_ungraded($this->editingteachers[0]->id, $afterduedate);
        $this->assertcount(0, $actual);
    }

    public function test_courseinfo_empty_no_courses() {
        $actual = local::courseinfo([]);
        $this->assertCount(0, $actual);
    }

    public function test_courseinfo_error_not_enrolled() {
        $actual = local::courseinfo([$this->course->id]);
        $this->assertCount(0, $actual);
    }

    /**
     * Test current user enrolled but suspended in this course.
     */
    public function test_courseinfo_suspended_user() {
        $this->create_extra_users();
        $this->setUser($this->extrasuspendedstudents[0]);
        $actual = local::courseinfo([$this->course->id]);
        $this->assertCount(0, $actual);
    }

    public function test_courseinfo_student() {
        $this->setUser($this->students[0]);
        $actual = local::courseinfo([$this->course->id]);
        $this->assertCount(1, $actual);
    }

    public function test_courseinfo_teacher() {
        $this->setUser($this->teachers[0]);
        $actual = local::courseinfo([$this->course->id]);
        $this->assertCount(1, $actual);
    }

    /**
     * Checks the user metadata for ungraded submission, numbers will change based on user role permissions.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_assing_data_group_mode() {
        global $DB;
        $this->resetAfterTest(true);

        $this->course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $groupdata = new \stdClass();
        $groupdata->courseid = $this->course->id;
        $groupdata->name = 'group1';
        $group1id = groups_create_group($groupdata);
        $groupdata->name = 'group2';
        $group2id = groups_create_group($groupdata);

        for ($i = 0; $i < 7; $i++) {
            $student = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($student->id, $this->course->id, 'student');
            groups_add_member($i < 5 ? $group1id : $group2id, $student);
            $students[] = $student;
        }
        for ($i = 0; $i < 2; $i++) {
            $teacher = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($teacher->id, $this->course->id, 'editingteacher');
            groups_add_member($group1id, $teacher);
            $teachers[] = $teacher;
        }

        $this->setUser($teachers[0]);
        $assign = $this->create_instance(['assignsubmission_onlinetext_enabled' => 1, 'groupmode' => SEPARATEGROUPS]);

        $data = new \stdClass();
        $data->onlinetext_editor = array('itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_HTML, );
        $this->create_user_submission($students[0], $assign, $data);

        $this->setUser($teachers[0]);
        $modinfo = get_fast_modinfo($this->course);
        $assigncm = $modinfo->instances['assign'][$assign->get_instance()->id];
        $meta = activity::assign_meta($assigncm);
        $this->assertEquals(1, $meta->numrequiregrading);

        $this->create_user_submission($students[5], $assign, $data);
        $this->create_user_submission($students[6], $assign, $data);

        $this->setUser($teachers[0]);
        $meta = activity::assign_meta($assigncm);
        $this->assertEquals(3, $meta->numrequiregrading);

        $this->setAdminUser();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $coursecontext = \context_course::instance($this->course->id);
        role_change_permission($teacherrole->id, $coursecontext, 'moodle/site:accessallgroups', CAP_PROHIBIT);

        // Changing permissions to prohibit will result on the editing teacher only seeing the submission of its group.
        $this->setUser($teachers[0]);
        $meta = activity::assign_meta($assigncm);
        $this->assertEquals(1, $meta->numrequiregrading);
    }

    /**
     * Creates a submission for the given user and sets its status to submitted
     * @param $user Object User
     * @param $assign Object Assign object
     * @param $data Object User submission data
     */

    public function create_user_submission($user, $assign, $data) {
        $this->setUser($user);
        $submission = $assign->get_user_submission($user->id, true);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $assign->testable_update_submission($submission, $user->id, true, false);
    }
}
