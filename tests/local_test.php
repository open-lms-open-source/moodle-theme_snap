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
 * Local Tests
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\tests;

use theme_snap\local;
use theme_snap\activity;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_local_test extends \advanced_testcase {

    public function setUp() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/assign/tests/base_test.php');
    }

    public function test_get_course_categories() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $cat1 = $generator->create_category((object)['name' => 'cat1']);
        $cat2 = $generator->create_category((object)['name' => 'cat2', 'parent' => $cat1->id]);
        $cat3 = $generator->create_category((object)['name' => 'cat3', 'parent' => $cat2->id]);
        $course1 = $generator->create_course((object) ['category' => $cat3->id, 'visible' => 0, 'oldvisible' => 0]);
        $categories = local::get_course_categories($course1);
        // First item in array should be immediate parent - $cat3.
        $expected = $cat3;
        $actual = reset($categories);
        $this->assertEquals($expected->id, $actual->id);

        // Second item in array should be parent of immediate parent - $cat2.
        $expected = $cat2;
        $actual = array_slice($categories, 1, 1);
        $actual = reset($actual);
        $this->assertEquals($expected->id, $actual->id);

        // Final item in array should be a root category - $cat1.
        $actual = end($categories);
        $this->assertEmpty($actual->parent);
        $expected = $cat1;
        $this->assertEquals($expected->id, $actual->id);
    }

    /**
     * Note, although the resolve_theme function is copied from the core moodle_page class there do not appear to be
     * any tests for resolve_theme in core code.
     */
    public function test_resolve_theme() {
        global $CFG, $COURSE;

        $this->resetAfterTest();

        $COURSE = get_course(SITEID);

        $CFG->enabledevicedetection = false;
        $CFG->theme = 'snap';

        $theme = local::resolve_theme();
        $this->assertEquals('snap', $theme);

        $CFG->allowcoursethemes = true;
        $CFG->allowcategorythemes = true;
        $CFG->allowuserthemes = true;

        $generator = $this->getDataGenerator();
        $cat1 = $generator->create_category((object)['name' => 'cat1']);
        $cat2 = $generator->create_category((object)['name' => 'cat2', 'parent' => $cat1->id]);
        $cat3 = $generator->create_category((object)['name' => 'cat3', 'parent' => $cat2->id, 'theme' => 'clean']);
        $course1 = $generator->create_course((object) ['category' => $cat3->id]);

        $COURSE = $course1;
        $theme = local::resolve_theme();
        $this->assertEquals('clean', $theme);

        $cat4 = $generator->create_category((object)['name' => 'cat4', 'theme' => 'more']);
        $cat5 = $generator->create_category((object)['name' => 'cat5', 'parent' => $cat4->id]);
        $cat6 = $generator->create_category((object)['name' => 'cat6', 'parent' => $cat5->id]);
        $course2 = $generator->create_course((object) ['category' => $cat6->id]);

        $COURSE = $course2;
        $theme = local::resolve_theme();
        $this->assertEquals('more', $theme);

        $course3 = $generator->create_course((object) ['category' => $cat1->id, 'theme' => 'clean']);
        $COURSE = $course3;
        $theme = local::resolve_theme();
        $this->assertEquals('clean', $theme);

        $user1 = $generator->create_user(['theme' => 'more']);
        $COURSE = get_course(SITEID);
        $this->setUser($user1);
        $theme = local::resolve_theme();
        $this->assertEquals('more', $theme);

    }

    public function test_get_course_color() {
        $actual = local::get_course_color(1);
        $this->assertSame('c4ca42', $actual);

        $actual = local::get_course_color(10);
        $this->assertSame('d3d944', $actual);

        $actual = local::get_course_color(100);
        $this->assertSame('f89913', $actual);

        $actual = local::get_course_color(1000);
        $this->assertSame('a9b7ba', $actual);
    }

    public function test_simpler_time() {
        $testcases = array (
            1 => 1,
            22 => 22,
            33 => 33,
            59 => 59,
            60 => 60,
            61 => 60,
            89 => 60,
            90 => 120,
            91 => 120,
            149 => 120,
            150 => 180,
            151 => 180,
            1234567 => 1234560,
        );

        foreach ($testcases as $input => $expected) {
            $actual = local::simpler_time($input);
            $this->assertSame($expected, $actual);
        }
    }

    public function test_relative_time() {

        $timetag  = array(
            'tag' => 'time',
            'attributes' => array(
                'is' => 'relative-time',
            ),
        );

        $actual = local::relative_time(time());
        $this->assertTag($timetag + ['content' => 'now'], $actual);

        $onesecbeforenow = time() - 1;

        $actual = local::relative_time($onesecbeforenow);
        $this->assertTag($timetag + ['content' => '1 sec ago'], $actual);

        $relativeto = date_timestamp_get(date_create("01/01/2001"));

        $onesecago = $relativeto - 1;

        $actual = local::relative_time($onesecago, $relativeto);
        $this->assertTag($timetag + ['content' => '1 sec ago'], $actual);

        $oneminago = $relativeto - 60;

        $actual = local::relative_time($oneminago, $relativeto);
        $this->assertTag($timetag + ['content' => '1 min ago'], $actual);
    }

    public function test_sort_graded() {
        $time = time();
        $oldertime = $time - 100;
        $newertime = $time + 100;

        $older = new \StdClass;
        $older->opentime = $oldertime;
        $older->closetime = $oldertime;
        $older->coursemoduleid = 123;

        $newer = new \StdClass;
        $newer->opentime = $newertime;
        $newer->closetime = $newertime;
        $newer->coursemoduleid = 789;

        $actual = local::sort_graded($older, $newer);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($newer, $older);
        $this->assertSame(1, $actual);

        $olderopenonly = new \StdClass;
        $olderopenonly->opentime = $oldertime;
        $olderopenonly->coursemoduleid = 101;

        $neweropenonly = new \StdClass;
        $neweropenonly->opentime = $newertime;
        $neweropenonly->coursemoduleid = 102;

        $actual = local::sort_graded($olderopenonly, $newer);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($olderopenonly, $neweropenonly);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($neweropenonly, $older);
        $this->assertSame(1, $actual);

        $actual = local::sort_graded($neweropenonly, $olderopenonly);
        $this->assertSame(1, $actual);

        $one = new \StdClass;
        $one->opentime = $time;
        $one->closetime = $time;
        $one->coursemoduleid = 1;

        $two = new \StdClass;
        $two->opentime = $time;
        $two->closetime = $time;
        $two->coursemoduleid = 2;

        $actual = local::sort_graded($one, $two);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($two, $one);
        $this->assertSame(1, $actual);

        // Everything equals itself.
        $events = [$older, $newer, $olderopenonly, $neweropenonly, $one, $two];
        foreach ($events as $event) {
            $actual = local::sort_graded($event, $event);
            $this->assertSame(0, $actual);
        }
    }

    public function test_extract_first_image() {

        $actual = local::extract_first_image('no image here');
        $this->assertFalse($actual);

        $html = '<img src="http://www.example.com/image.jpg" alt="example image">';
        $actual = local::extract_first_image($html);
        $this->assertSame('http://www.example.com/image.jpg', $actual['src']);
        $this->assertSame('example image', $actual['alt']);
    }

    /**
     * Test upcoming deadlines
     */
    public function test_upcoming_deadlines() {
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

        // 5 items should be shown as final deadline 3rd quiz gets cut off.
        $actual = local::upcoming_deadlines($student->id);
        $expected = 5;

        // Check deadlines are listed in appropriate order.
        $this->assertCount($expected, $actual);
        $deadlinelist = [];
        foreach ($actual as $item) {
            $deadlinelist[] = $item;
        }
        $this->assertEquals('Assign 1', $deadlinelist[0]->name);
        $this->assertEquals('Quiz 1', $deadlinelist[1]->name);
        $this->assertEquals('Assign 2', $deadlinelist[2]->name);
        $this->assertEquals('Quiz 2', $deadlinelist[3]->name);
        $this->assertEquals('Assign 3', $deadlinelist[4]->name);

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
            'GMT'   => 'Atlantic/Azores',
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
            $tzoneusers[$offset] =  $generator->create_user(['timezone' => $tz]);
            $generator->enrol_user($tzoneusers[$offset]->id, $course->id, $studentrole->id);
            $this->setUser($tzoneusers[$offset]);
            $actual = local::upcoming_deadlines($tzoneusers[$offset]);
            $expected = 5;
            $this->assertCount($expected, $actual);
        }
    }

    /**
     * Test no upcoming deadlines.
     */
    public function test_no_upcoming_deadlines() {
        global $USER;

        $actual = local::upcoming_deadlines($USER->id);
        $expected = array();
        $this->assertSame($actual, $expected);

        $actual = local::deadlines();
        $expected = '<p>You have no upcoming deadlines.</p>';
        $this->assertSame($actual, $expected);
    }

    /**
     * Crete an assign module instance.
     *
     * @param int $courseid
     * @param int $duedate
     * @param array $opts - an array of field values to go into the assign record.
     * @return mixed
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

        $actual = local::upcoming_deadlines($student->id);
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
        $course2 = $generator->create_course((object) ['visible' => 0, 'oldvisible' => 0]);
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
            $this->create_assignment($course->id, time() + (DAYSECS * 2));
        }

        // Student should see 1 deadline as course2 is hidden.
        $actual = local::upcoming_deadlines($student->id);
        $expected = 1;
        $this->assertCount($expected, $actual);

        // Teacher should see 2 deadlines as they can see hidden courses.
        $actual = local::upcoming_deadlines($teacher->id);
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
        $actual = local::upcoming_deadlines($student->id);
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
        $actual = local::upcoming_deadlines($student->id);
        $expected = 1;
        $this->assertCount($expected, $actual);

        // Create restricted assign instance.
        $opts = ['availability' => $this->get_date_condition_json(time() + WEEKSECS)];
        $this->create_assignment($course->id, time() + (DAYSECS * 2), $opts);

        // Student should see 1 deadlines as the second assignment is restricted until next week.
        $actual = local::upcoming_deadlines($student->id);
        $expected = 1;
        $this->assertCount($expected, $actual);
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
        $group1 = $this->create_group($course->id, 'group1');
        $group2 = $this->create_group($course->id, 'group2');

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
        $stu1deadlines = local::upcoming_deadlines($student1->id);
        $this->assertCount(1, $stu1deadlines);
        $this->assertEquals($duedate1, reset($stu1deadlines)->timestart);

        // Ensure student2 only has 1 deadline and that it is for group2.
        $stu2deadlines = local::upcoming_deadlines($student2->id);
        $this->assertCount(1, $stu2deadlines);
        $this->assertEquals($duedate2, reset($stu2deadlines)->timestart);

        // Ensure teacher can see both deadlines.
        $tchdeadlines = local::upcoming_deadlines($teacher->id);
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
        $course2 = $generator->create_course((object) ['visible' => 0, 'oldvisible' => 0]);
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
     * Create group for specific course.
     *
     * @param int $courseid
     * @param str $name
     * @return \stdClass
     * @throws \coding_exception
     */
    protected function create_group($courseid, $name) {
        $generator = $this->getDataGenerator();
        $group = [
            'courseid' => $courseid,
            'name' => $name
        ];
        return $generator->create_group($group);
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
        $actual = local::upcoming_deadlines($student->id);
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

    public function test_no_messages() {
        global $USER;

        $actual = local::get_user_messages($USER->id);
        $expected = array();
        $this->assertSame($actual, $expected);

        $actual = local::messages();
        $expected = '<p>You have no messages.</p>';
        $this->assertSame($actual, $expected);
    }

    public function test_one_message() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $userfrom = $generator->create_user();
        $userto = $generator->create_user();

        $message = new \core\message\message();
        $message->component         = 'moodle';
        $message->name              = 'instantmessage';
        $message->userfrom          = $userfrom;
        $message->userto            = $userto;
        $message->subject           = 'message subject 1';
        $message->fullmessage       = 'message body';
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = '<p>message body</p>';
        $message->smallmessage      = 'small message';
        $message->notification      = '0';

        message_send($message);
        $aftersent = time();

        $actual = local::get_user_messages($userfrom->id);
        $this->assertCount(0, $actual);

        $actual = local::get_user_messages($userto->id);
        $this->assertCount(1, $actual);
        $this->assertSame($actual[0]->subject, "message subject 1");

        $actual = local::get_user_messages($userto->id, $aftersent);
        $this->assertCount(0, $actual);
    }


    public function test_one_message_deleted() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $userfrom = $generator->create_user();
        $userto = $generator->create_user();

        $message = new \core\message\message();
        $message->component         = 'moodle';
        $message->name              = 'instantmessage';
        $message->userfrom          = $userfrom;
        $message->userto            = $userto;
        $message->subject           = 'message subject 1';
        $message->fullmessage       = 'message body';
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = '<p>message body</p>';
        $message->smallmessage      = 'small message';
        $message->notification      = '0';

        $messageid = message_send($message);

        $actual = local::get_user_messages($userfrom->id);
        $this->assertCount(0, $actual);

        $actual = local::get_user_messages($userto->id);
        $this->assertCount(1, $actual);

        $todelete = $DB->get_record('message', ['id' => $messageid]);
        message_delete_message($todelete, $userto->id);
        $actual = local::get_user_messages($userto->id);
        $this->assertCount(0, $actual);
    }

    public function test_one_message_user_deleted() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        $userfrom = $generator->create_user();
        $userto = $generator->create_user();

        $message = new \core\message\message();
        $message->component         = 'moodle';
        $message->name              = 'instantmessage';
        $message->userfrom          = $userfrom;
        $message->userto            = $userto;
        $message->subject           = 'message subject 1';
        $message->fullmessage       = 'message body';
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = '<p>message body</p>';
        $message->smallmessage      = 'small message';
        $message->notification      = '0';

        message_send($message);

        $actual = local::get_user_messages($userfrom->id);
        $this->assertCount(0, $actual);

        $actual = local::get_user_messages($userto->id);
        $this->assertCount(1, $actual);

        delete_user($userfrom);
        $actual = local::get_user_messages($userto->id);
        $this->assertCount(0, $actual);
    }

    public function test_no_grading() {
        $actual = local::grading();
        $expected = '<p>You have no submissions to grade.</p>';
        $this->assertSame($actual, $expected);
    }

    /**
     * Imitates an admin setting the site cover image via the
     * Snap theme settings page. Creates a file, sets a theme
     * setting with the filname, then calls the callback triggered
     * by submitting the form.
     *
     * @param $fixturename
     * @return array
     * @throws \Exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function fake_site_image_setting_upload($filename) {
        global $CFG;

        $syscontext = \context_system::instance();

        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'theme_snap',
            'filearea'  => 'poster',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
        );

        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$filename;

        $fs = \get_file_storage();

        $fs->delete_area_files($syscontext->id, 'theme_snap', 'poster');

        $fs->create_file_from_pathname($filerecord, $filepath);
        \set_config('poster', $filename, 'theme_snap');

        local::process_coverimage($syscontext);
    }

    /**
     * Imitates an admin deleting the site cover image via the
     * Snap theme settings page. Deletes a file, sets a theme
     * setting to blank, then calls the callback triggered
     * by submitting the form.
     *
     * @param $fixturename
     * @return array
     * @throws \Exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function fake_site_image_setting_cleared() {
        $syscontext = \context_system::instance();
        $fs = \get_file_storage();

        $fs->delete_area_files($syscontext->id, 'theme_snap', 'coverimage');

        \set_config('poster', '', 'theme_snap');
        local::process_coverimage($syscontext);
    }

    public function test_poster_image_upload() {
        $this->resetAfterTest();

        $beforeupload = local::site_coverimage_original();
        $this->assertFalse($beforeupload);

        $fixtures = [
            'bpd_bikes_3888px.jpg' => true , // True means SHOULD get resized.
            'bpd_bikes_1381px.jpg' => true,
            'bpd_bikes_1380px.jpg' => false,
            'bpd_bikes_1379px.jpg' => false,
            'bpd_bikes_1280px.jpg' => false,
            'testpng.png' => false,
            'testpng_small.png' => false,
            'testgif.gif' => false,
            'testgif_small.gif' => false,
            'testsvg.svg' => false
        ];

        foreach ($fixtures as $filename => $shouldberesized) {

            $this->fake_site_image_setting_upload($filename);

            $css = local::site_coverimage_css();

            $this->assertContains('/theme_snap/coverimage/', $css);

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $this->assertContains("/site-image.$ext", $css);

            if ($shouldberesized) {
                $image = local::site_coverimage();
                $finfo = $image->get_imageinfo();
                $this->assertSame(1280, $finfo['width']);
            }
        }

        $this->fake_site_image_setting_cleared();

        $css = local::site_coverimage_css();

        $this->assertSame('', $css);
        $this->assertFalse(local::site_coverimage());
    }

    /**
     * Imitates an admin setting the course cover image via the
     * Snap theme settings page. Creates a file, sets a theme
     * setting with the filname, then calls the callback triggered
     * by submitting the form.
     *
     * @param $fixturename
     * @param $context
     * @return array
     * @throws \Exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function fake_course_image_setting_upload($filename, $context) {
        global $CFG;

        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'theme_snap',
            'filearea'  => 'coverimage',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
        );

        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$filename;

        $fs = \get_file_storage();

        $fs->delete_area_files($context->id, 'theme_snap', 'coverimage');

        $fs->create_file_from_pathname($filerecord, $filepath);
        \set_config('coverimage', $filename, 'theme_snap');
    }

    /**
     * Test the functions that creates or handles the course card images.
     *
     */

    public function test_resize_cover_image_functions() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $fixtures = [
            'bpd_bikes_3888px.jpg' => true , // True means SHOULD get resized.
            'bpd_bikes_1381px.jpg' => true,
            'bpd_bikes_1380px.jpg' => true,
            'bpd_bikes_1379px.jpg' => true,
            'bpd_bikes_1280px.jpg' => true,
            'bpd_bikes_1000px.jpg' => false,
            'bpd_bikes_640px.jpg' => false
        ];
        foreach ($fixtures as $filename => $shouldberesized) {

            $this->fake_course_image_setting_upload($filename, $context);
            $originalfile = local::course_coverimage($course->id);
            $this->assertNotEmpty($originalfile);
            $resized = local::set_course_card_image($context, $originalfile);
            $this->assertNotEmpty($resized);
            $finfo = $resized->get_imageinfo();
            if ($shouldberesized) {
                $this->assertSame(720, $finfo['width']);
                $this->assertNotEquals($originalfile, $resized);
            } else {
                $this->assertEquals($resized, $originalfile);
            }
        }
        $fs = \get_file_storage();
        $fs->delete_area_files($context->id, 'theme_snap', 'coverimage');
        $originalfile = local::course_coverimage($course->id);
        $coursecardimage = local::set_course_card_image($context, $originalfile);
        $this->assertFalse($coursecardimage);
        $cardimages = $fs->get_area_files($context->id, 'theme_snap', 'coursecard', 0, "itemid, filepath, filename", false);
        $this->assertCount(5, $cardimages);
        $this->fake_course_image_setting_upload('bpd_bikes_1381px.jpg', $context);
        $originalfile = local::course_coverimage($course->id);
        local::set_course_card_image($context, $originalfile);
        $cardimages = $fs->get_area_files($context->id, 'theme_snap', 'coursecard', 0, "itemid, filepath, filename", false);
        $this->assertCount(6, $cardimages);
        // Call 2 times this function should not duplicate the course card images.
        local::set_course_card_image($context, $originalfile);
        $this->assertCount(6, $cardimages);
        $url = local::course_card_image_url($course->id);
        $id = $originalfile->get_id();
        $this->assertNotFalse(strpos($url, $id));
        local::course_card_clean_up($context);
        $cardimages = $fs->get_area_files($context->id, 'theme_snap', 'coursecard', 0, "itemid, filepath, filename", false);
        $this->assertCount(0, $cardimages);
    }


    /**
     * Test gradeable_courseids function - i.e. courses where user is allowed to view the grade book.
     */
    public function test_gradeable_courseids() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course((object) ['visible' => 0, 'oldvisible' => 0]);
        $teacher = $generator->create_user();

        // Enrol teacher as teacher on course1.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
            $course1->id,
            $teacherrole->id);

        // Enrol teacher as student on course2.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($teacher->id,
            $course2->id,
            $studentrole->id);

        // Check teacher can only grade 1 course (not a teacher on course2).
        $gradeablecourses = local::gradeable_courseids($teacher->id);
        $this->assertCount(1, $gradeablecourses);
    }

    /**
     * Test swap global user.
     */
    public function test_swap_global_user() {
        global $USER;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $originaluserid = $USER->id;

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        local::swap_global_user($user1);
        $this->assertEquals($user1->id, $USER->id);
        local::swap_global_user($user2);
        $this->assertEquals($user2->id, $USER->id);
        local::swap_global_user($user3);
        $this->assertEquals($user3->id, $USER->id);
        local::swap_global_user(false);
        $this->assertEquals($user2->id, $USER->id);
        local::swap_global_user(false);
        $this->assertEquals($user1->id, $USER->id);
        local::swap_global_user(false);
        $this->assertEquals($originaluserid, $USER->id);
    }

    public function test_current_url_path() {
        global $PAGE;

        // Note, $CFG->wwwroot is set to http://www.example.com/moodle which is ideal for this test.
        // We want to make sure we can get the local path whilst moodle is in a subpath of the url.

        $this->resetAfterTest();
        $PAGE->set_url('/course/view.php', array('id' => 1));
        $urlpath = $PAGE->url->get_path();
        $expected = '/moodle/course/view.php';
        $this->assertEquals($expected, $urlpath);
        $localpath = local::current_url_path();
        $expected = '/course/view.php';
        $this->assertEquals($expected, $localpath);
    }

    /**
     * Test that the summary, when generated from the content field, strips out images and does not exceed 200 chars.
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function test_get_page_mod_content_summary() {
        global $DB;

        $this->resetAfterTest();

        $testtxt = 'Hello world, Καλημέρα κόσμε, コンニチハ, àâæçéèêë';

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $pagegen = $generator->get_plugin_generator('mod_page');
        $page = $pagegen->create_instance([
            'course' => $course->id,
            'content' => '<img src="http://fakeurl.local/testimg.png" alt="some alt text" />' .
                    '<p>'.$testtxt.'</p>' . str_pad('-', 200)
        ]);
        $cm = get_course_and_cm_from_instance($page->id, 'page', $course->id)[1];
        // Remove the intro text from the page record
        $page->intro = '';
        $DB->update_record('page', $page);

        $pagemod = local::get_page_mod($cm);

        // Ensure summary contains text.
        $this->assertContains($testtxt, $pagemod->summary);

        // Ensure summary contains text without tags.
        $this->assertNotContains('<p>'.$testtxt.'</p>', $pagemod->summary);

        // Ensure summary does not contain any images.
        $this->assertNotContains('<img', $pagemod->summary);

        // Make sure summary text has been shortened with elipsis.
        $this->assertStringEndsWith('...', $pagemod->summary);

        // Make sure no images are preserved in summary text.
        $page->content = '<img src="http://fakeurl.local/img1.png" alt="image 1" />' .
                         '<img src="http://fakeurl.local/img2.png" alt="image 2" />';
        $DB->update_record('page', $page);
        $pagemod = local::get_page_mod($cm);
        $this->assertNotContains('image 1', $pagemod->summary);
        $this->assertNotContains('image 2', $pagemod->summary);
    }

    /**
     * Test that the summary, when generated from the intro text, does not strip out images or trim the text in anyway.
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function test_get_page_mod_intro_summary() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $pagegen = $generator->get_plugin_generator('mod_page');
        $page = $pagegen->create_instance([
            'course' => $course->id,
            'intro' => '<img src="http://fakeurl.local/testimg.png" alt="some alt text" />' .
                '<p>Some content text</p>' . str_pad('-', 200)
        ]);
        $cm = get_course_and_cm_from_instance($page->id, 'page', $course->id)[1];
        $pagemod = local::get_page_mod($cm);

        // Ensure summary contains text and is sitll within tags.
        $this->assertContains('<p>Some content text</p>', $pagemod->summary);

        // Ensure summary contains images.
        $this->assertContains('<img', $pagemod->summary);

        // Make sure summary text can be greater than 200 chars.
        $this->assertGreaterThan(200, strlen($pagemod->summary));
    }

    /**
     * @param array $params
     * @return \cm_info
     * @throws \coding_exception
     */
    private function add_assignment(array $params) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('assign', $instance->id);
        $cm = \cm_info::create($cm);

        // Trigger course module created event.
        $event = \core\event\course_module_created::create_from_cm($cm);
        $event->trigger();
        return ($cm);
    }

    /**
     * Test getting course completion cache stamp + resetting it to a new stamp.
     */
    public function test_course_completion_cachestamp() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $ts = local::course_completion_cachestamp($course->id);
        $this->assertNotNull($ts);

        // Make sure getting the cache stamp a second time results in same timestamp.
        $this->waitForSecond();
        $ts2 = local::course_completion_cachestamp($course->id);
        $this->assertEquals($ts, $ts2);

        // Reset cache stamp and make sure it is now different to the first one.
        $ts3 = local::course_completion_cachestamp($course->id, true);
        $this->assertNotEquals($ts, $ts3);
    }

    public function test_course_completion_progress() {
        global $DB, $CFG;

        $this->resetAfterTest();

        // Set up.
        $CFG->enablecompletion = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course((object) ['enablecompletion' => 1]);
        $student = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);
        $teacher = $generator->create_user();
        $editingteacherrole = $DB->get_record('role', array('shortname'=>'editingteacher'));
        $generator->enrol_user($teacher->id, $course->id, $editingteacherrole->id);

        $this->setUser($student);

        // Assert no completion when no trackable items.
        $comp = local::course_completion_progress($course);
        $this->assertTrue(property_exists($comp, 'complete'));
        $this->assertNull($comp->complete);
        // Assert null completion data not in cache.
        $this->assertFalse($comp->fromcache);
        // Assert null completion data in cache on 2nd hit.
        $comp = local::course_completion_progress($course);
        $this->assertTrue($comp->fromcache);

        // Assert completion data populated and cache dumped on assignment creation.
        $params = [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC
        ];
        $cm = $this->add_assignment($params);
        $comp = local::course_completion_progress($course);
        $this->assertFalse($comp->fromcache);
        $this->assertInstanceOf('stdClass', $comp);
        $this->assertEquals(0, $comp->complete);
        $this->assertEquals(1, $comp->total);
        $this->assertEquals(0, $comp->progress);

        // Assert from cache again on 2nd get.
        $comp = local::course_completion_progress($course);
        $this->assertTrue($comp->fromcache);

        // Assert completion does not update for current user when grading someone else's assignment.
        $this->setUser($teacher); // We need to be a teacher if we are grading.
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $cm->id]);
        $assign = new \assign($cm->context, $cm, $course);
        $gradeitem = $assign->get_grade_item();
        \grade_object::set_properties($gradeitem, array('gradepass' => 50.0));
        $gradeitem->update();
        $assignrow = $assign->get_instance();
        $grades = array();
        $grades[$student->id] = (object) [
            'rawgrade' => 60,
            'userid' => $student->id
        ];
        $assignrow->cmidnumber = null;
        assign_grade_item_update($assignrow, $grades);
        $comp = local::course_completion_progress($course);
        $this->assertFalse($comp->fromcache);
        $this->assertInstanceOf('stdClass', $comp);
        $this->assertEquals(0, $comp->complete);
        $this->assertEquals(1, $comp->total);
        $this->assertEquals(0, $comp->progress);

        // Assert completion does update for current user when they grade their own assignment.
        // Note, we need to stay as a teacher because if we logged out to test as student it would invalidate the
        // cache and we are testing for cache invalidation here!!!!
        $grades = array();
        $grades[$teacher->id] = (object) [
            'rawgrade' => 60,
            'userid' => $teacher->id
        ];
        $assignrow->cmidnumber = null;
        assign_grade_item_update($assignrow, $grades);
        $comp = local::course_completion_progress($course);
        $this->assertFalse($comp->fromcache); // Cache should have been dumped at this point.
        $this->assertEquals(1, $comp->complete);
        $this->assertEquals(1, $comp->total);
        $this->assertEquals(100, $comp->progress);

        // Assert from cache again on 2nd get.
        $comp = local::course_completion_progress($course);
        $this->assertTrue($comp->fromcache);

        // Assert no completion when disabled at site level.
        $CFG->enablecompletion = false;
        $comp = local::course_completion_progress($course);
        $this->assertNull($comp->complete);

        // Assert no completion when disabled at course level.
        $CFG->enablecompletion = true;
        $DB->update_record('course', (object) ['id' => $course->id, 'enablecompletion' => 0]);
        $course = $DB->get_record('course', ['id' => $course->id]);
        $comp = local::course_completion_progress($course);
        $this->assertNull($comp->complete);

        // Assert completion restored when re-enabled at both site and course level.
        $DB->update_record('course', (object) ['id' => $course->id, 'enablecompletion' => 1]);
        $course = $DB->get_record('course', ['id' => $course->id]);
        $comp = local::course_completion_progress($course);
        $this->assertTrue($comp->fromcache); // Cache should still be valid.
        $this->assertEquals(1, $comp->complete);
        $this->assertEquals(1, $comp->total);
        $this->assertEquals(100, $comp->progress);
    }
}
