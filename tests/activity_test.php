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

use \theme_snap\activity,
    \theme_snap\snap_base_test;

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

/**
 * Testing for theme/snap/classes/activity.php
 *
 * @package  theme_snap
 * @copyright  2017 Blackboard Inc.
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_acitvity_test extends snap_base_test {

    /**
     * Crete an assign module instance.
     *
     * @param int $courseid
     * @param int $duedate
     * @param array $opts - an array of field values to go into the assign record.
     * @return \testable_assign
     * @throws \coding_exception
     */
    protected function create_assignment($courseid, $duedate, $opts = []) {
        global $USER, $CFG;

        // This is crucial - without this you can't make a conditionally accessed forum.
        $CFG->enableavailability = true;

        // Hack - without this the calendar library trips up when trying to give an assignment a duedate.
        // lib.php line 2234 - nopermissiontoupdatecalendar.
        $origuser = $USER;
        $USER = get_admin();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');

        $params = [
            'course' => $courseid,
            'duedate' => $duedate,
            'grade' => 100
        ];
        foreach ($opts as $key => $val) {
            // Overwrite or add opt vals to params.
            $params[$key] = $val;
        }
        $instance = $generator->create_instance($params);

        // Restore user.
        $USER = $origuser;

        $cm = get_coursemodule_from_instance('assign', $instance->id);
        $context = \context_module::instance($cm->id);
        return new \testable_assign($context, $cm, get_course($courseid));
    }

    /**
     * Create a quiz instance.
     * @param int $courseid
     * @param int $duedate
     * @param array $opts
     * @return quiz
     */
    protected function create_quiz($courseid, $duedate = null, $opts = []) {
        global $USER, $CFG;

        if (empty($duedate)) {
            $duedate = time() + DAYSECS;
        }

        // This is crucial - without this you can't make a conditionally accessed forum.
        $CFG->enableavailability = true;

        // Hack - without this the calendar library trips up when trying to give a quiz a duedate.
        // lib.php line 2234 - nopermissiontoupdatecalendar.
        $origuser = $USER;
        $USER = get_admin();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $params = [
            'course' => $courseid,
            'timeclose' => $duedate,
            'grade' => 100
        ];
        foreach ($opts as $key => $val) {
            // Overwrite or add opt vals to params.
            $params[$key] = $val;
        }

        $instance = $generator->create_instance($params);

        // Restore user.
        $USER = $origuser;

        list ($course, $cm) = get_course_and_cm_from_instance($instance->id, 'quiz');
        return new \quiz($instance, $cm, $course);
    }

    /**
     * Assert that deadlines array contains specific assignment id.
     * @param array $deadlines
     * @param int $assignid
     */
    private function assert_deadlines_includes_assignment(array $deadlines, $assignid) {
        $hasassign = false;
        foreach ($deadlines as $event) {
            if ($event->modulename === 'assign' && $event->instance == $assignid) {
                $hasassign = true;
                break;
            }
        }
        $this->assertTrue($hasassign, 'Error, deadlines does not contain assignment with id ' . $assignid);
    }

    /**
     * Extend assignment deadline for specific assignment and student.
     * @param int $assignid
     * @param int $studentid
     * @param int | null $extendeddue
     */
    private function extend_assign_deadline($assignid, $studentid, $extendeddue = null) {
        global $USER;
        $origuser = $USER;

        $this->setAdminUser();

        usleep(1); // We have to sleep for 1 microsecond so that the cache can be invalidated.
        if (empty($extendeddue)) {
            $extendeddue = time() + (DAYSECS * 2);
        }
        list ($course, $cm) = get_course_and_cm_from_instance($assignid, 'assign');
        $cm = \cm_info::create($cm);
        $assignobj = new \assign($cm->context, $cm, $course);

        $assignobj->save_user_extension($studentid, $extendeddue);

        $this->setUser($origuser);
    }

    /**
     * @param string $mod - e.g. quiz
     * @param string $fkeyfield - foreign key field in override table - e.g. quiz
     * @param string $ovdfield - e.g. timeopen
     * @param string $ovdval - e.g. 12345
     * @param int $elementid - i.e. The id of the quiz
     * @param string $typefield - e.g. 'groupid'
     * @param int $typeid - i.e. The id of the group
     * @param string $ovdfunction - function to call, lamda or function name - e.g. quiz_update_events
     */
    private function general_override($mod, $fkeyfield, $ovdfield, $ovdval, $elementid, $typefield, $typeid,
                                      $sortorder = null, $ovdfunction = '') {
        global $DB, $USER;

        $origuser = $USER;

        $this->setAdminUser();

        $element = $DB->get_record($mod, ['id' => $elementid]);

        usleep(1); // We have to sleep for 1 microsecond so that the cache can be invalidated.

        if (empty($sortorder)) {
            $existingovd = $DB->get_record($mod . '_overrides',
                    [$fkeyfield => $elementid, $typefield => $typeid]);
        } else {
            $existingovd = $DB->get_record($mod . '_overrides',
                    [$fkeyfield => $elementid, $typefield => $typeid, 'sortorder' => $sortorder]);
        }
        if (!$existingovd) {
            $overridedata = (object)[$fkeyfield => $elementid, $typefield => $typeid,
                $ovdfield => $ovdval];
            if (!empty($sortorder)) {
                $overridedata->sortorder = $sortorder;
            }
            $overridedata->id = $DB->insert_record($mod.'_overrides', $overridedata);
        } else {
            $overridedata = $existingovd;
            $overridedata->$ovdfield = $ovdval;
            if (!empty($sortorder)) {
                $overridedata->sortorder = $sortorder;
            }
        }

        if (!empty($ovdfunction)) {
            $ovdfunction($element, $overridedata);
        }

        $this->setUser($origuser);
    }

    private function override_assign_general($fkeyfield, $ovdfield, $ovdval, $elementid, $typefield, $typeid,
                                             $sortorder = null) {
        $ovdfunc = function($assign, $overridedata) {
            list ($course, $cm) = get_course_and_cm_from_instance($assign->id, 'assign');
            $cm = \cm_info::create($cm);
            $assignobj = new \assign($cm->context, $cm, $course);

            assign_update_events($assignobj, $overridedata);
        };

        $this->general_override('assign', $fkeyfield, $ovdfield, $ovdval, $elementid, $typefield,
            $typeid, $sortorder, $ovdfunc);
    }

    /**
     * Override an assignment user duedate.
     * @param int $assignid
     * @param int $userid
     * @param int $ovduserdue
     */
    private function override_assign_user_duedate($assignid, $userid, $ovduserdue) {
        $this->override_assign_general ('assignid', 'duedate', $ovduserdue, $assignid, 'userid',
                                        $userid);
    }

    /**
     * Override an assignment user open date.
     * @param int $assignid
     * @param int $userid
     * @param int $ovddate
     */
    private function override_assign_user_opendate($assignid, $userid, $ovddate) {
        $this->override_assign_general ('assignid', 'allowsubmissionsfromdate', $ovddate, $assignid, 'userid',
            $userid);
        global $DB;
    }

    /**
     * Override an assignment group duedate.
     * @param int $assignid
     * @param int $groupid
     * @param int $ovdgroupdue
     */
    private function override_assign_group_duedate($assignid, $groupid, $ovdgroupdue, $sortorder) {
        $this->override_assign_general ('assignid', 'duedate', $ovdgroupdue, $assignid, 'groupid',
                                        $groupid, $sortorder);
    }

    /**
     * Override an assignment user open date.
     * @param int $assignid
     * @param int $groupid
     * @param int $ovddate
     */
    private function override_assign_group_opendate($assignid, $groupid, $ovddate, $sortorder) {
        $this->override_assign_general ('assignid', 'allowsubmissionsfromdate', $ovddate, $assignid, 'groupid',
            $groupid, $sortorder);
    }

    /**
     * Delete an assignment override for a specific user.
     * @param int $assignid
     * @param int $userid
     */
    private function delete_assign_user_overrides($assignid, $userid) {
        global $DB, $USER;

        $origuser = $USER;

        $this->setAdminUser();

        $assign = $DB->get_record('assign', ['id' => $assignid]);
        list ($course, $cm) = get_course_and_cm_from_instance($assign->id, 'assign');
        $assignobj = new \assign($cm->context, $cm, $course);

        usleep(1); // We have to sleep for 1 microsecond so that the cache can be invalidated.

        $DB->delete_records('assign_overrides', array ('userid' => $userid, 'assignid' => $assignid));

        assign_update_events($assignobj);

        $this->setUser($origuser);
    }

    /**
     * Delete a quiz override for a specific user.
     * @param int $quizid
     * @param int $userid
     */
    private function delete_quiz_user_overrides($quizid, $userid) {
        global $DB, $USER;

        $origuser = $USER;

        $this->setAdminUser();

        $quiz = $DB->get_record('quiz', ['id' => $quizid]);

        usleep(1); // We have to sleep for 1 microsecond so that the cache can be invalidated.

        $DB->delete_records('quiz_overrides', array ('userid' => $userid, 'quiz' => $quizid));

        quiz_update_events($quiz);

        $this->setUser($origuser);
    }

    /**
     * Override a quiz user duedate.
     * @param int $quizid
     * @param int $userid
     * @param int $ovduserdue
     */
    private function override_quiz_user_duedate($quizid, $userid, $ovduserdue) {
        $this->general_override('quiz', 'quiz', 'timeclose', $ovduserdue, $quizid, 'userid',
            $userid, null, 'quiz_update_events');
    }

    /**
     * Override a groups quiz duedate
     * @param int $quizid
     * @param int $groupid
     * @param int $ovdgroupdue
     */
    private function override_quiz_group_duedate($quizid, $groupid, $ovdgroupdue) {
        $this->general_override('quiz', 'quiz', 'timeclose', $ovdgroupdue, $quizid, 'groupid',
                $groupid, null, 'quiz_update_events');
    }

    /**
     * Override quiz group open date.
     * @param int $quizid
     * @param int $groupid
     * @param int $ovdgroupopen
     */
    private function override_quiz_group_opendate($quizid, $groupid, $ovdgroupopen) {
        $this->general_override('quiz', 'quiz', 'timeopen', $ovdgroupopen, $quizid, 'groupid',
            $groupid, null, 'quiz_update_events');
    }

    /**
     * Set up an assignment for activity testing.
     * @return array
     */
    protected function assign_activity_test_setup() {
        $due = time() + WEEKSECS;
        $ovdgroupdue = $due + (DAYSECS);
        $ovduserdue = $due + (DAYSECS * 2);
        $extension = $due + (WEEKSECS * 2);

        list ($student, $teacher, $course, $group) = $this->course_group_user_setup();

        $this->setUser($teacher);

        $assignobj = $this->create_assignment($course->id, $due);

        list ($course, $cm) = get_course_and_cm_from_instance($assignobj->get_instance()->id, 'assign');

        return [
            'due' => $due,
            'ovdgroupdue' => $ovdgroupdue,
            'ovduserdue' => $ovduserdue,
            'extension' => $extension,
            'student' => $student,
            'teacher' => $teacher,
            'course' => $course,
            'cm' => $cm,
            'group' => $group,
            'assign' => $assignobj->get_instance(),
            'assignobj' => $assignobj
        ];
    }

    public function test_get_calendar_activity_events() {
        $this->resetAfterTest();

        $vars = $this->assign_activity_test_setup();
        $due = $vars['due'];
        $extension = $vars['extension'];
        $student = $vars['student'];
        $assign = $vars['assign'];

        $tstart = time() - YEARSECS / 2;
        $tend = time() + YEARSECS / 2;

        // Test extension not set.
        $this->setUser($student);
        $calendar = new \calendar_information(0, 0, 0, $tstart);
        $course = get_course(SITEID);
        $courses = enrol_get_my_courses();
        $calendar->set_sources($course, $courses);
        $events = activity::get_calendar_activity_events($tstart, $tend, $courses);
        $snapevent = reset($events);
        $this->assertEquals($due, $snapevent->timestart);
        $this->assertEquals($due, $snapevent->timesort);

        // Test extension set.
        $this->extend_assign_deadline($assign->id, $student->id, $extension);
        // Test that the Snap calendar includes extensions to assignments.
        $events = activity::get_calendar_activity_events($tstart, $tend, $courses);
        $snapevent = reset($events);
        $this->assertEquals($extension, $snapevent->timestart);
        $this->assertEquals($extension, $snapevent->timesort);

        // Reset the caches so we can get the core calendar:.
        \core_calendar\local\event\container::reset_caches();

        // Test that core calendar does not include extensions to assignments.
        // This is important because if core introduce assignment extensions to the calendar then we will
        // want get rid of our custom code and the failure of this test will make us aware of the new core
        // functionality.
        $events = calendar_get_legacy_events($tstart, $tend, $calendar->users, $calendar->groups, $calendar->courses);
        $coreevent = reset($events);
        $this->assertEquals($due, $coreevent->timestart);
        $this->assertEquals($due, $coreevent->timesort);

        // Test that the Snap calendar event is the same as the core event
        // (with the exception of the timestart + timesort fields).
        unset($snapevent->timestart);
        unset($snapevent->timesort);
        unset($coreevent->timestart);
        unset($coreevent->timesort);
        $this->assertEquals($snapevent, $coreevent);
    }

    public function test_user_activity_events() {

        $this->resetAfterTest();

        // This allows non-standard PHP unit behaviour - normally caching isn't enabled for PHPUNIT.
        activity::$phpunitallowcaching = true;

        $tstart = time() - YEARSECS / 2;
        $tend = time() + YEARSECS / 2;

        $vars = $this->assign_activity_test_setup();
        $due = $vars['due'];
        $extension = $vars['extension'];
        $student = $vars['student'];
        $assign = $vars['assign'];
        $group = $vars['group'];
        $ovdgroupdue = $vars['ovdgroupdue'];
        $ovduserdue = $vars['ovduserdue'];

        // Test no overrides or extensions set.
        $this->setUser($student);
        $calendar = new \calendar_information(0, 0, 0, $tstart);
        $course = get_course(SITEID);
        $courses = enrol_get_my_courses();
        $calendar->set_sources($course, $courses);
        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        $this->assertEquals($due, $snapevent->timestart);
        $this->assertEquals($due, $snapevent->timesort);
        // Assert not from cache.
        $this->assertFalse($eventsobj->fromcache);
        // Test that getting the events again recovers them from cache and that they are populated.
        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        // Assert from cache.
        $this->assertTrue($eventsobj->fromcache);
        $this->assertEquals($due, $snapevent->timestart);
        $this->assertEquals($due, $snapevent->timesort);

        // Test group override invalidates cache and overrides due date.
        $this->override_assign_group_duedate($assign->id, $group->id, $ovdgroupdue, 1);

        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        // Assert not from cache.
        $this->assertFalse($eventsobj->fromcache);
        $this->assertEquals($ovdgroupdue, $snapevent->timestart);
        $this->assertEquals($ovdgroupdue, $snapevent->timesort);

        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        // Assert from cache.
        $this->assertTrue($eventsobj->fromcache);
        $this->assertEquals($ovdgroupdue, $snapevent->timestart);
        $this->assertEquals($ovdgroupdue, $snapevent->timesort);

        // Test user override invalidates cache and trumps group override.
        $this->override_assign_user_duedate($assign->id, $student->id, $ovduserdue);

        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        // Assert not from cache.
        $this->assertFalse($eventsobj->fromcache);
        $this->assertEquals($ovduserdue, $snapevent->timestart);
        $this->assertEquals($ovduserdue, $snapevent->timesort);

        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        // Assert from cache.
        $this->assertTrue($eventsobj->fromcache);
        $this->assertEquals($ovduserdue, $snapevent->timestart);
        $this->assertEquals($ovduserdue, $snapevent->timesort);

        // Test extension set invalidates cache and trumps all overrides.
        $this->extend_assign_deadline($assign->id, $student->id, $extension);
        $eventsobj = activity::user_activity_events($student, $courses, $tstart, $tend, 'allcourses');
        $events = $eventsobj->events;
        $snapevent = reset($events);
        // Assert not from cache.
        $this->assertFalse($eventsobj->fromcache);
        $this->assertEquals($extension, $snapevent->timestart);
        $this->assertEquals($extension, $snapevent->timesort);

    }

    /**
     * Make sure multiple module types play nice together with activity dates.
     */
    public function test_instance_activity_dates_quiz_assign() {
        global $DB;

        activity::$phpunitallowcaching = false;

        $this->resetAfterTest();

        list ($student, $teacher, $course, $group) = $this->course_group_user_setup();

        $timeclose1 = time();
        $timeclose2 = time() + (2 * DAYSECS);
        $quiz1obj = $this->create_quiz($course->id, $timeclose1);
        $quiz1 = $DB->get_record('quiz', ['id' => $quiz1obj->get_cm()->instance]);
        $quiz2obj = $this->create_quiz($course->id, $timeclose2);
        $quiz2 = $DB->get_record('quiz', ['id' => $quiz2obj->get_cm()->instance]);

        $modinfo = get_fast_modinfo($course->id);
        $cm = $modinfo->instances['quiz'][$quiz1->id];
        $cm2 = $modinfo->instances['quiz'][$quiz2->id];
        $this->setUser($student);
        $quizdates = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');

        // Check no override.
        $this->assertEquals($timeclose1, $quizdates->timeclose);

        // User override.
        $ovduserdue = $timeclose1 + DAYSECS;
        $this->override_quiz_user_duedate($quiz1->id, $student->id, $ovduserdue);

        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $dates2 = \theme_snap\activity::instance_activity_dates($course->id, $cm2, '', '');

        $this->assertEquals($ovduserdue, $dates1->timeclose);
        $this->assertEquals($timeclose2, $dates2->timeclose);

        // Group override.
        $ovdgroupdue = $timeclose1 + (3 * DAYSECS);
        $this->override_quiz_group_duedate($quiz1->id, $group->id, $ovdgroupdue);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');

        // Returned override should be user instead of group - user overrides trump groups.
        $this->assertEquals($ovduserdue, $dates1->timeclose);

        // Deleting the user override should bring the group override as result.
        $this->delete_quiz_user_overrides($quiz1->id, $student->id);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $this->assertEquals($ovdgroupdue, $dates1->timeclose);

        // Second group override.
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $this->getDataGenerator()->create_group_member(array('userid' => $student, 'groupid' => $group2->id));

        $ovdgroup2open = $timeclose1 + (2 * DAYSECS);
        $ovdgroup2due = $timeclose1 + (7 * DAYSECS);
        $this->override_quiz_group_opendate($quiz1->id, $group2->id, $ovdgroup2open);
        $this->override_quiz_group_duedate($quiz1->id, $group2->id, $ovdgroup2due);

        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');

        $this->assertEquals($ovdgroup2open, $dates1->timeopen);
        $this->assertEquals($ovdgroup2due, $dates1->timeclose);

        // Switching to a user without group.
        $nogroupuser = $this->getDataGenerator()->create_user();
        $this->setUser($nogroupuser);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $this->assertEquals($quiz1->timeclose, $dates1->timeclose);

        $dates2 = \theme_snap\activity::instance_activity_dates($course->id, $cm2, '', '');
        $this->assertEquals($quiz2->timeclose, $dates2->timeclose);

        // Now test assignment works alongside quiz.
        $assign1obj = $this->create_assignment($course->id, $timeclose1);
        $assign1 = $assign1obj->get_instance();
        $assign2obj = $this->create_assignment($course->id, $timeclose2);
        $assign2 = $assign2obj->get_instance();

        $modinfo = get_fast_modinfo($course->id);
        $cm = $modinfo->instances['assign'][$assign1->id];
        $cm2 = $modinfo->instances['assign'][$assign2->id];
        $this->setUser($student);
        $assigndates = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');

        // Check no override.
        $this->assertEquals($timeclose1, $assigndates->timeclose);

        // User override.
        $ovduserdue = $timeclose1 + DAYSECS;
        $this->override_assign_user_duedate($assign1->id, $student->id, $ovduserdue);

        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $dates2 = \theme_snap\activity::instance_activity_dates($course->id, $cm2, '', '');

        $this->assertEquals($ovduserdue, $dates1->timeclose);
        $this->assertEquals($timeclose2, $dates2->timeclose);

        // Group override.
        $ovdgroupdue = $timeclose1 + (3 * DAYSECS);
        $this->override_assign_group_duedate($assign1->id, $group->id, $ovdgroupdue, 1);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');

        // Returned override should be user instead of group - user overrides trump groups.
        $this->assertEquals($ovduserdue, $dates1->timeclose);

        // Deleting the user override should bring the group override as result.
        $this->delete_assign_user_overrides($assign1->id, $student->id);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $this->assertEquals($ovdgroupdue, $dates1->timeclose);

        // Change the sort order of the original group override so we can have a new one override it.
        $ovdrow = $DB->get_record('assign_overrides', ['assignid' => $assign1->id, 'groupid' => $group->id]);
        $ovdrow->sortorder = 2;
        $DB->update_record('assign_overrides', $ovdrow);

        // Second group override.
        $ovdgroup2open = $timeclose1 + (2 * DAYSECS);
        $ovdgroup2due = $timeclose1 + (7 * DAYSECS);
        $this->override_assign_group_opendate($assign1->id, $group2->id, $ovdgroup2open, 1);
        $this->override_assign_group_duedate($assign1->id, $group2->id, $ovdgroup2due, 1);

        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');

        $this->assertEquals($ovdgroup2open, $dates1->timeopen);
        $this->assertEquals($ovdgroup2due, $dates1->timeclose);

        // Override open time for user and make sure it trumps the group override.
        $ovduseropen = time() + (11 * DAYSECS);
        $this->override_assign_user_opendate($assign1->id, $student->id, $ovduseropen);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $this->assertEquals($ovduseropen, $dates1->timeopen);

        // Switching to a user without group.
        $this->setUser($nogroupuser);
        $dates1 = \theme_snap\activity::instance_activity_dates($course->id, $cm, '', '');
        $this->assertEquals($assign1->duedate, $dates1->timeclose);

        $dates2 = \theme_snap\activity::instance_activity_dates($course->id, $cm2, '', '');
        $this->assertEquals($assign2->duedate, $dates2->timeclose);
    }

    public function test_instance_activity_dates_caching() {
        $this->resetAfterTest();

        // This allows non-standard PHP unit behaviour - normally caching isn't enabled for PHPUNIT.
        activity::$phpunitallowcaching = true;

        $vars = $this->assign_activity_test_setup();
        $due = $vars['due'];
        $extension = $vars['extension'];
        $student = $vars['student'];
        $assign = $vars['assign'];
        $cm = $vars['cm'];
        $course = $vars['course'];
        $group = $vars['group'];
        $ovdgroupdue = $vars['ovdgroupdue'];
        $ovduserdue = $vars['ovduserdue'];

        // Test no overrides or extensions set.
        $this->setUser($student);

        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert not from cache.
        $this->assertFalse($dates->fromcache);
        $this->assertEquals($due, $dates->timeclose);

        // Test that getting the events again recovers them from cache and that they are populated.
        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert from cache.
        $this->assertTrue($dates->fromcache);
        $this->assertEquals($due, $dates->timeclose);

        // Test group override invalidates cache and overrides due date.
        $this->override_assign_group_duedate($assign->id, $group->id, $ovdgroupdue, 1);

        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert not from cache.
        $this->assertFalse($dates->fromcache);
        $this->assertEquals($ovdgroupdue, $dates->timeclose);

        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert from cache.
        $this->assertTrue($dates->fromcache);
        $this->assertEquals($ovdgroupdue, $dates->timeclose);

        // Test user override invalidates cache and trumps group override.
        $this->override_assign_user_duedate($assign->id, $student->id, $ovduserdue);

        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert not from cache.
        $this->assertFalse($dates->fromcache);
        $this->assertEquals($ovduserdue, $dates->timeclose);

        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert from cache.
        $this->assertTrue($dates->fromcache);
        $this->assertEquals($ovduserdue, $dates->timeclose);

        // Test extension set invalidates cache and trumps all overrides.
        $this->extend_assign_deadline($assign->id, $student->id, $extension);
        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert not from cache.
        $this->assertFalse($dates->fromcache);
        $this->assertEquals($extension, $dates->timeclose);

        $dates = activity::instance_activity_dates($course->id, $cm, 'allowsubmissionsfromdate', 'duedate');
        // Assert from cache.
        $this->assertTrue($dates->fromcache);
        $this->assertEquals($extension, $dates->timeclose);

    }

    public function test_assignment_due_date_info() {
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $student = $dg->create_user();
        $teacher = $dg->create_user();
        $course = $dg->create_course();
        $dg->enrol_user($student->id, $course->id, 'student');
        $dg->enrol_user($teacher->id, $course->id, 'teacher');

        $duedate = time() - DAYSECS;

        $assign = $this->create_assignment($course->id, $duedate);

        // Test extension not set.
        $now = time();
        $duedateinfo = activity::assignment_due_date_info($assign->get_instance()->id, $student->id);
        $this->assertFalse($duedateinfo->extended);
        $this->assertNotEquals($now, $duedateinfo->duedate);

        // Test extension set.
        $extension = $now + DAYSECS;
        $this->extend_assign_deadline($assign->get_instance()->id, $student->id, $extension);

        $duedateinfo = activity::assignment_due_date_info($assign->get_instance()->id, $student->id);
        $this->assertTrue($duedateinfo->extended);
        $this->assertNotEquals($now, $duedateinfo->duedate);
        $this->assertEquals($extension, $duedateinfo->duedate);
    }

    public function test_hash_events_by_module_instance() {
        $events = [
            (object)[
                'modulename' => 'assign',
                'instance' => '1000',
                'id' => '1'
            ],
            (object)[
                'modulename' => 'quiz',
                'instance' => '99',
                'id' => '2'
            ],
            (object)[
                'modulename' => 'assign',
                'instance' => '1000',
                'id' => '3'
            ],
            (object)[
                'modulename' => 'lesson',
                'instance' => '1000', // Here to check it doesn't interfere with assign.
                'id' => '4'
            ],
            (object)[
                'modulename' => 'assign',
                'instance' => '1000',
                'id' => '5'
            ]
        ];

        $events = phpunit_util::call_internal_method(null, 'hash_events_by_module_instance', [$events],
            '\theme_snap\activity');

        $this->assertCount(3, $events['assign']['1000']);
        $this->assertCount(1, $events['lesson']['1000']);
        $this->assertCount(1, $events['quiz']['99']);

        $this->assertEquals(2, reset($events['quiz']['99'])->id);
        $this->assertEquals(4, reset($events['lesson']['1000'])->id);

        $assigneventids = [1, 3, 5];
        foreach ($events['assign']['1000'] as $event) {
            $failmsg = 'Event id ' . $event->id . ' should be one of ' . implode(',', $assigneventids);
            $this->assertTrue(in_array($event->id, $assigneventids), $failmsg);
        }
    }

    public function test_upcoming_deadlines() {
        $this->resetAfterTest();

        list ($student, $teacher, $course, $group) = $this->course_group_user_setup();

        $this->create_assignment($course->id, time());

        $deadlines = activity::upcoming_deadlines($teacher->id)->events;
        $this->assertCount(1, $deadlines);

        $this->setUser($student);
        $deadlines = activity::upcoming_deadlines($student->id)->events;
        $this->assertCount(1, $deadlines);

        $this->setUser($teacher);
        $deadlines = activity::upcoming_deadlines($teacher->id)->events;
        $this->assertCount(1, $deadlines);

        $this->create_assignment($course->id, time() + 3 * DAYSECS);

        $deadlines = activity::upcoming_deadlines($teacher->id)->events;
        $this->assertCount(2, $deadlines);

        $this->setUser($student);
        $deadlines = activity::upcoming_deadlines($student->id)->events;
        $this->assertCount(2, $deadlines);

        $newdue = time() + (4 * DAYSECS);
        $this->create_assignment($course->id, $newdue);
        $this->create_assignment($course->id, $newdue);
        $max = 2;
        $this->setUser($student);
        $deadlines = activity::upcoming_deadlines($student->id, $max)->events;
        $this->assertCount(2, $deadlines);
        $this->setUser($teacher);
    }

    public function test_upcoming_deadlines_cache_maxlistsize() {
        $this->resetAfterTest();

        activity::$phpunitallowcaching = true;

        $dg = $this->getDataGenerator();
        $student = $dg->create_user();
        $teacher = $dg->create_user();
        $course = $dg->create_course();
        $group = $dg->create_group((object)['courseid' => $course->id]);
        $dg->enrol_user($student->id, $course->id, 'student');
        $dg->create_group_member((object)['groupid' => $group->id, 'userid' => $student->id]);
        $dg->enrol_user($teacher->id, $course->id, 'teacher');

        $this->setUser($teacher);

        $tz = new \DateTimeZone(\core_date::get_user_timezone($student));
        $today = new \DateTime('today', $tz);
        $todayts = $today->getTimestamp();

        $assigninstances = [];

        for ($t = 0; $t < 2; $t++) {
            $assigninstances[] = $this->create_assignment($course->id, $todayts)->get_instance();
        }
        for ($t = 0; $t < 20; $t++) {
            $assigninstances[] = $this->create_assignment($course->id, ($todayts + WEEKSECS))->get_instance();
        }

        $deadlines = activity::upcoming_deadlines($student);
        $this->assertCount(5, $deadlines->events);
        $this->assertFalse($deadlines->fromcache);
        $deadlines = activity::upcoming_deadlines($student);
        $this->assertTrue($deadlines->fromcache);
    }

    public function test_upcoming_deadlines_todays_priority() {
        $this->resetAfterTest();

        activity::$phpunitallowcaching = true;

        $dg = $this->getDataGenerator();
        $student = $dg->create_user();
        $teacher = $dg->create_user();
        $course = $dg->create_course();
        $group = $dg->create_group((object)['courseid' => $course->id]);
        $dg->enrol_user($student->id, $course->id, 'student');
        $dg->create_group_member((object)['groupid' => $group->id, 'userid' => $student->id]);
        $dg->enrol_user($teacher->id, $course->id, 'teacher');

        $this->setUser($teacher);

        $tz = new \DateTimeZone(\core_date::get_user_timezone($student));
        $today = new \DateTime('today', $tz);
        $todayts = $today->getTimestamp();

        $assigninstances = [];

        // When we create 20 assignments due today they all display in upcoming deadlines.
        for ($t = 0; $t < 20; $t++) {
            $assigninstances[] = $this->create_assignment($course->id, $todayts)->get_instance();
        }
        for ($t = 0; $t < 5; $t++) {
            $assigninstances[] = $this->create_assignment($course->id, ($todayts + WEEKSECS))->get_instance();
        }

        $deadlines = activity::upcoming_deadlines($student);
        $this->assertCount(20, $deadlines->events);
        $this->assertFalse($deadlines->fromcache);
        $deadlines = activity::upcoming_deadlines($student);
        $this->assertTrue($deadlines->fromcache);
    }

    /**
     * Test upcoming deadline works with expired due date extended.
     */
    public function test_upcoming_deadlines_extension() {
        activity::$phpunitallowcaching = true;

        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $student = $dg->create_user();
        $teacher = $dg->create_user();
        $course = $dg->create_course();
        $dg->enrol_user($student->id, $course->id, 'student');
        $dg->enrol_user($teacher->id, $course->id, 'teacher');

        $deadlinepast = time() - WEEKSECS;

        $overdueassign = $this->create_assignment(
            $course->id, $deadlinepast, ['name' => 'Assign overdue']
        );
        $overdueassignid = $overdueassign->get_instance()->id;

        $eventobj = activity::upcoming_deadlines($student);
        $this->assertFalse($eventobj->fromcache);
        $this->assertEmpty($eventobj->events);
        $eventobj = activity::upcoming_deadlines($student);
        $this->assertTrue($eventobj->fromcache);
        $this->assertEmpty($eventobj->events);

        // Test creating assignment invalidates cache and that current assignments feature in list.
        $deadlinefuture = time() + DAYSECS;
        $assignobj = $this->create_assignment(
            $course->id, $deadlinefuture, ['name' => 'Assign future due']
        );
        $assign = $assignobj->get_instance();
        $eventobj = activity::upcoming_deadlines($student);
        $this->assertFalse($eventobj->fromcache);
        $this->assert_deadlines_includes_assignment($eventobj->events, $assign->id);
        $eventobj = activity::upcoming_deadlines($student);
        $this->assertTrue($eventobj->fromcache);
        $this->assert_deadlines_includes_assignment($eventobj->events, $assign->id);

        // Test extension set invalidates cache and trumps all overrides.
        $this->setUser($teacher);
        $extension = $deadlinefuture + DAYSECS; // 1 Day further in future than non extended assignment.
        $this->extend_assign_deadline($overdueassignid, $student->id, $extension);
        $this->setUser($student);

        $eventobj = activity::upcoming_deadlines($student);
        $this->assert_deadlines_includes_assignment($eventobj->events, $overdueassignid);

        // Assert count and order of assignments is correct.
        $this->assertCount(2, $eventobj->events);
        $this->assertEquals('Assign future due is due', $eventobj->events[0]->name);
        // The overdue assignment should be last in the list as it now has a deadline greater than the other assignment.
        $this->assertEquals('Assign overdue is due', $eventobj->events[1]->name);
    }

    /**
     * Test upcoming deadlines
     */
    public function test_upcoming_deadlines_timezones() {
        global $DB;

        $this->resetAfterTest();

        date_default_timezone_set('UTC');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $generator->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);

        $assigngen = $this->getDataGenerator()->get_plugin_generator('mod_assign');

        $this->setUser($teacher);

        $approachingdeadline = time() + HOURSECS;
        $deadlinepast = time() - WEEKSECS;

        $assigngen->create_instance([
            'name' => 'Assign 1',
            'course' => $course->id,
            'duedate' => $approachingdeadline
        ]);
        $assigngen->create_instance([
            'name' => 'Assign 2',
            'course' => $course->id,
            'duedate' => strtotime('tomorrow') + HOURSECS * 2 // Add two hours so that test works at 23:30.
        ]);
        $assigngen->create_instance([
            'name' => 'Assign 3',
            'course' => $course->id,
            'duedate' => strtotime('next week')
        ]);
        $assigngen->create_instance([
            'name' => 'Assign 4',
            'course' => $course->id,
            'duedate' => $deadlinepast
        ]);

        $quizgen = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quizgen->create_instance([
            'name' => 'Quiz 1',
            'course' => $course->id,
            'timeclose' => $approachingdeadline + 1 // Add 1 second so that Quiz deadlines sort predictably after Assign.
        ]);
        $quizgen->create_instance([
            'name' => 'Quiz 2',
            'course' => $course->id,
            'timeclose' => strtotime('tomorrow') + (HOURSECS * 2) + 1 // Add two hours so that test works at 23:30.
        ]);
        $quizgen->create_instance([
            'name' => 'Quiz 3',
            'course' => $course->id,
            'timeclose' => strtotime('next month') + 1
        ]);

        // 5 items should be shown as final deadline 3rd quiz gets cut off and assignment with past deadline should not
        // show.
        $actual = activity::upcoming_deadlines($student->id)->events;
        $expected = 5;

        // Check deadlines are listed in appropriate order.
        $this->assertCount($expected, $actual);
        $deadlinelist = [];
        foreach ($actual as $item) {
            $deadlinelist[] = $item;
        }
        $this->assertEquals('Assign 1 is due', $deadlinelist[0]->name);
        $this->assertEquals('Quiz 1', $deadlinelist[1]->name);
        $this->assertEquals('Assign 2 is due', $deadlinelist[2]->name);
        $this->assertEquals('Quiz 2', $deadlinelist[3]->name);
        $this->assertEquals('Assign 3 is due', $deadlinelist[4]->name);

        // Check 5 deadlines exist for users in all timeszones.
        $tzoneusers = [];
        $timezones = [
            'GMT-1' => 'Atlantic/Cape_Verde',
            'GMT-2' => 'America/Miquelon',
            'GMT-3' => 'America/Rio_Branco',
            'GMT-4' => 'America/Nassau',
            'GMT-5' => 'America/Bogota',
            'GMT-6' => 'America/Belize',
            'GMT-7' => 'Pacific/Honolulu',
            'GMT-8' => 'Pacific/Pitcairn',
            'GMT-9' => 'Pacific/Gambier',
            'GMT-10' => 'Pacific/Rarotonga',
            'GMT-11' => 'Pacific/Niue',
            'GMT' => 'Atlantic/Azores',
            'GMT+1' => 'Europe/London',
            'GMT+2' => 'Europe/Paris',
            'GMT+3' => 'Europe/Athens',
            'GMT+4' => 'Asia/Tbilisi',
            'GMT+5' => 'Asia/Baku',
            'GMT+6' => 'Asia/Dhaka',
            'GMT+7' => 'Asia/Phnom_Penh',
            'GMT+8' => 'Asia/Hong_Kong',
            'GMT+9' => 'Asia/Seoul',
            'GMT+10' => 'Pacific/Guam',
            'GMT+11' => 'Pacific/Efate',
            'GMT+12' => 'Asia/Anadyr',
            'GMT+13' => 'Pacific/Apia'
        ];

        foreach ($timezones as $offset => $tz) {
            $tzoneusers[$offset] = $generator->create_user(['timezone' => $tz]);
            $generator->enrol_user($tzoneusers[$offset]->id, $course->id, $studentrole->id);
            $this->setUser($tzoneusers[$offset]);
            $actual = activity::upcoming_deadlines($tzoneusers[$offset])->events;
            $expected = 5;
            $this->assertCount($expected, $actual);
        }

    }

    /**
     * Test no upcoming deadlines.
     */
    public function test_no_upcoming_deadlines() {
        global $USER;

        $actual = activity::upcoming_deadlines($USER->id)->events;
        $expected = array();
        $this->assertSame($actual, $expected);
    }

    /*
     * Test upcoming deadline times.
     */
    public function test_upcoming_deadlines_close_events() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $generator->enrol_user($teacher->id, $course->id, $teacherrole->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);

        $quizgen = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        // Seperate open and close events generated if open for more than 5 days.
        $timeopen = time() - (5 * DAYSECS);
        $timeclose = time() + (2 * DAYSECS);

        // Can't create activities with deadlines using generator without the
        // current user having the correct permissions for the calendar.
        $this->setUser($teacher);

        $quizgen->create_instance([
            'course' => $course->id,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
        ]);

        $actual = activity::upcoming_deadlines($student->id)->events;
        $expected = 1;
        $this->assertCount($expected, $actual);
        $event = reset($actual);
        $this->assertSame('close', $event->eventtype);
        $this->assertSame('Quiz 1', $event->name, 'Should not have "(Quiz closes)" at the end of the event name');
    }

    /**
     * Test upcoming deadlines
     *
     * @throws \coding_exception
     */
    public function test_upcoming_deadlines_hidden() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course((object)['visible' => 0, 'oldvisible' => 0]);
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        $courses = [$course1, $course2];

        // Enrol teacher on both courses.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        foreach ([$course1, $course2] as $course) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                $course->id,
                $teacherrole->id
            );
        }

        // Enrol student on both courses.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        foreach ($courses as $course) {
            $generator->enrol_user($student->id,
                $course->id,
                $studentrole->id
            );
        }

        // Create an assignment in each course.
        foreach ($courses as $course) {
            $this->create_assignment($course->id, time() + (DAYSECS * 2), ['name' => 'Assign for course ' . $course->id]);
        }

        // Student should see 1 deadline as course2 is hidden.
        $actual = activity::upcoming_deadlines($student->id)->events;
        $expected = 1;
        $this->assertCount($expected, $actual);

        // Teacher should see 2 deadlines as they can see hidden courses.
        $actual = activity::upcoming_deadlines($teacher->id)->events;
        $expected = 2;
        $this->assertCount($expected, $actual);

    }

    /**
     * Test upcoming deadlines where enrolment has expired.
     *
     * @throws \coding_exception
     */
    public function test_upcoming_deadlines_enrolment_expired() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();

        // Enrol student on with an expired enrolment.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($student->id,
            $course->id,
            $studentrole->id,
            'manual',
            time() - (DAYSECS * 2),
            time() - DAYSECS
        );

        // Create assign instance.
        $this->create_assignment($course->id, time() + (DAYSECS * 2));

        // Student should see 0 deadlines as their enrollments have expired.
        $actual = activity::upcoming_deadlines($student->id)->events;
        $expected = 0;
        $this->assertCount($expected, $actual);
    }

    /**
     * Get date condition for module availability.
     * @param $time
     * @param string $comparator
     * @return string
     * @throws \coding_exception
     */
    protected function get_date_condition_json($time, $comparator = '>=') {
        return json_encode(
            \core_availability\tree::get_root_json(
                [\availability_date\condition::get_json($comparator, $time)
                ]
            )
        );
    }

    /**
     * Test upcoming deadlines with assignment activity restricted to future date.
     *
     * @throws \coding_exception
     */
    public function test_upcoming_deadlines_restricted() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();

        // Enrol student.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($student->id,
            $course->id,
            $studentrole->id
        );

        // Create assign instance.
        $this->create_assignment($course->id, time() + (DAYSECS * 2));
        $actual = activity::upcoming_deadlines($student->id)->events;
        $expected = 1;
        $this->assertCount($expected, $actual);

        // Create restricted assign instance.
        $opts = ['availability' => $this->get_date_condition_json(time() + WEEKSECS)];
        $this->create_assignment($course->id, time() + (DAYSECS * 2), $opts);

        // Student should see 1 deadlines as the second assignment is restricted until next week.
        $actual = activity::upcoming_deadlines($student->id);
        $expected = 1;
        $this->assertCount($expected, $actual->events);
    }

    /**
     * Test upcoming deadlines restricted by group
     *
     * @throws \coding_exception
     */
    public function test_upcoming_deadlines_group() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $teacher = $generator->create_user();

        $group1 = $generator->create_group((object)['courseid' => $course->id, 'name' => 'group1']);
        $group2 = $generator->create_group((object)['courseid' => $course->id, 'name' => 'group2']);

        // Enrol students.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        foreach ([$student1, $student2] as $student) {
            $generator->enrol_user($student->id,
                $course->id,
                $studentrole->id
            );
        }

        // Enrol teacher.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $generator->enrol_user($teacher->id,
            $course->id,
            $teacherrole->id);

        // Add students to groups.
        groups_add_member($group1, $student1);
        groups_add_member($group2, $student2);

        // Create assignment restricted to group1.
        $opts = [
            'availability' => json_encode(
                \core_availability\tree::get_root_json(
                    [\availability_group\condition::get_json($group1->id)]
                )
            )
        ];
        $duedate1 = time() + (DAYSECS * 2);
        $this->create_assignment($course->id, $duedate1, $opts);

        // Create assignment restricted to group2.
        $opts = [
            'availability' => json_encode(
                \core_availability\tree::get_root_json(
                    [\availability_group\condition::get_json($group2->id)]
                )
            )
        ];
        $duedate2 = time() + (DAYSECS * 3);
        $this->create_assignment($course->id, $duedate2, $opts);

        // Ensure student1 only has 1 deadline and that it is for group1.
        $stu1deadlines = activity::upcoming_deadlines($student1->id)->events;
        $this->assertCount(1, $stu1deadlines);
        $this->assertEquals($duedate1, reset($stu1deadlines)->timestart);

        // Ensure student2 only has 1 deadline and that it is for group2.
        $stu2deadlines = activity::upcoming_deadlines($student2->id)->events;
        $this->assertCount(1, $stu2deadlines);
        $this->assertEquals($duedate2, reset($stu2deadlines)->timestart);

        // Ensure teacher can see both deadlines.
        $tchdeadlines = activity::upcoming_deadlines($teacher->id)->events;
        $this->assertCount(2, $tchdeadlines);
    }

    /**
     * General feedback test.
     *
     * @throws \coding_exception
     */
    public function test_feedback() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        // Enrol teacher.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
            $course->id,
            $teacherrole->id
        );

        // Enrol student.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($student->id,
            $course->id,
            $studentrole->id
        );

        // Create an assignment and mark with a regular grade.
        $assign = $this->create_assignment($course->id, time() + (DAYSECS * 2));
        $data = $assign->get_user_grade($student->id, true);
        $data->grade = '50.5';
        $assign->update_grade($data);

        // Create an assignment and mark a zero grade (should still count as feedback).
        $assign = $this->create_assignment($course->id, time() + (DAYSECS * 2));
        $data = $assign->get_user_grade($student->id, true);
        $data->grade = '0';
        $assign->update_grade($data);

        // Student should see 2 feedback availables.
        $this->setUser($student);
        $actual = activity::events_graded();
        $expected = 2;
        $this->assertCount($expected, $actual);
    }

    /**
     * Test feedback where course is hidden.
     *
     * @throws \coding_exception
     */
    public function test_feedback_hidden() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course((object)['visible' => 0, 'oldvisible' => 0]);
        $teacher = $generator->create_user();
        $student = $generator->create_user();

        $courses = [$course1, $course2];

        // Enrol teacher on both courses.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        foreach ([$course1, $course2] as $course) {
            $this->getDataGenerator()->enrol_user($teacher->id,
                $course->id,
                $teacherrole->id);
        }

        // Enrol student on both courses.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        foreach ($courses as $course) {
            $generator->enrol_user($student->id,
                $course->id,
                $studentrole->id);
        }

        // Create an assignment in each course and mark it.
        foreach ($courses as $course) {
            $assign = $this->create_assignment($course->id, time() + (DAYSECS * 2));

            // Mark the assignment.
            $data = $assign->get_user_grade($student->id, true);
            $data->grade = '50.5';
            $assign->update_grade($data);
        }

        // Student should see 1 feedback available as course2 is hidden.
        $this->setUser($student);
        $actual = activity::events_graded();
        $expected = 1;
        $this->assertCount($expected, $actual);
    }

    /**
     * Test feedback where enrolment has expired.
     *
     * @throws \coding_exception
     */
    public function test_feedback_enrolment_expired() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();

        // Enrol student on with an expired enrolment.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($student->id,
            $course->id,
            $studentrole->id,
            'manual',
            time() - (DAYSECS * 2),
            time() - DAYSECS
        );

        // Create assign instance.
        $assign = $this->create_assignment($course->id, time() + (DAYSECS * 2));

        // Mark assignment.
        $data = $assign->get_user_grade($student->id, true);
        $data->grade = '50.5';
        $assign->update_grade($data);

        // Student should see 0 feedback items as their enrollments have expired.
        $this->setUser($student);
        $actual = activity::events_graded();
        $expected = 0;
        $this->assertCount($expected, $actual);
    }

    /**
     * Test feedback with assignment restricted to future date.
     *
     * @throws \coding_exception
     */
    public function test_feedback_restricted() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();

        // Enrol student.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $generator->enrol_user($student->id,
            $course->id,
            $studentrole->id
        );

        // Create assign instance.
        $this->create_assignment($course->id, time() + (DAYSECS * 2));
        $actual = activity::upcoming_deadlines($student->id)->events;
        $expected = 1;
        $this->assertCount($expected, $actual);

        // Create restricted assign instance.
        $opts = ['availability' => $this->get_date_condition_json(time() + WEEKSECS)];
        $assign = $this->create_assignment($course->id, time() + (DAYSECS * 2), $opts);

        // Mark restricted assign instasnce.
        $data = $assign->get_user_grade($student->id, true);
        $data->grade = '50.5';
        $assign->update_grade($data);

        // Student should only see 1 feedback item as one is normal and one is restricted until next week.
        $this->setUser($student);
        $actual = activity::events_graded();
        $expected = 1;
        $this->assertCount($expected, $actual);
    }

    public function test_quiz_ungraded() {
        $this->resetAfterTest();

        $sixmonthsago = time() - YEARSECS / 2;

        list ($student, $teacher, $course, $group) = $this->course_group_user_setup();

        $this->setUser($student);

        // Test with no quizes.
        $actual = activity::quiz_ungraded([], $sixmonthsago);
        $this->assertCount(0, $actual);

        $actual = activity::quiz_ungraded([$course->id], $sixmonthsago);
        $this->assertCount(0, $actual);

        // Test with a quiz in course student is enrolled on.
        $this->create_quiz($course->id);

        $actual = activity::quiz_ungraded([], $sixmonthsago);
        $this->assertCount(0, $actual);

        $actual = activity::quiz_ungraded([$course->id], $sixmonthsago);
        $this->assertCount(0, $actual);
    }

}
