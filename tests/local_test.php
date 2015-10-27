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

    public function test_grade_warning_debug_off() {
        global $CFG;

        $this->resetAfterTest();
        $CFG->debugdisplay = 0;

        $actual = local::skipgradewarning("warning text");
        $this->assertNull($actual);
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

        // This is crucial - without this you can't make a conditionally accsesed forum.
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
     * Test upcoming deadlines with assignmetn activity restricted to future date.
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
        $opts = ['availability' =>
            json_encode(
                \core_availability\tree::get_root_json(
                    [\availability_group\condition::get_json($group1->id)]
                )
            )
        ];
        $duedate1 = time() + (DAYSECS * 2);
        $this->create_assignment($course->id, $duedate1, $opts);

        // Create assignment restricted to group2.
        $opts = ['availability' =>
            json_encode(
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
        \set_config('poster', '/'.$filename, 'theme_snap');

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
        ];

        foreach ($fixtures as $filename => $shouldberesized) {

            $this->fake_site_image_setting_upload($filename);

            $css = '[[setting:poster]]';
            $css = local::site_coverimage_css($css);

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

        $css = '[[setting:poster]]';
        $css = local::site_coverimage_css($css);

        $this->assertSame('', $css);
        $this->assertFalse(local::site_coverimage());
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
        $gradeable_courses = local::gradeable_courseids($teacher->id);
        $this->assertCount(1, $gradeable_courses);
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

}
