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
namespace theme_snap;
use theme_snap\services\course;
use theme_snap\renderables\course_card;
use theme_snap\local;

/**
 * Test course card service.
 * @package   theme_snap
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class services_course_test extends \advanced_testcase {

    /**
     * @var stdClass
     */
    protected $user1;

    /**
     * @var array
     */
    protected $courses = [];

    /**
     * @var course
     */
    protected $courseservice;

    /**
     * Pre-requisites for tests.
     * @throws \core\exception\coding_exception
     */
    public function setUp(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest();

        $CFG->theme = 'snap';

        // Create 10 courses.
        for ($c = 0; $c < 10; $c++) {
            $this->courses[] = $this->getDataGenerator()->create_course();
        }

        // Create 5 courses in the past.
        for ($c = 0; $c < 5; $c++) {
            $enddate = time() - DAYSECS * ($c + 1) * 10;
            $startdate = $enddate - YEARSECS;
            $record = (object) [
                'startdate' => $startdate,
                'enddate' => $enddate,
            ];
            $this->courses[] = $this->getDataGenerator()->create_course($record);
        }

        $this->user1 = $this->getDataGenerator()->create_user();

        // Enrol user to all courses.
        $sturole = $DB->get_record('role', array('shortname' => 'student'));

        foreach ($this->courses as $course) {
            $this->getDataGenerator()->enrol_user($this->user1->id,
                $course->id,
                $sturole->id);
        }

        $this->courseservice = course::service();
    }

    public function test_service() {
        $testservice = course::service();
        $this->assertEquals($this->courseservice, $testservice);
        $this->assertTrue($testservice instanceof course);
    }

    public function test_favorited() {
        $service = $this->courseservice;
        $favorited = $service->favorited($this->courses[0], $this->user1->id);
        $this->assertFalse($favorited);

        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);

        // Make sure marked as favorite.
        $favorited = $service->favorited($this->courses[0]->id, $this->user1->id);
        $this->assertTrue($favorited);
    }

    public function test_favorites() {
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);

        $favorites = $service->favorites($this->user1->id, false);
        $this->assertTrue(isset($favorites[$this->courses[0]->id]));
        $this->assertTrue(isset($favorites[$this->courses[1]->id]));
        $this->assertFalse(isset($favorites[$this->courses[2]->id]));
    }

    public function test_my_courses_split_by_past_courses_favorites() {
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);

        $this->setUser($this->user1);
        list ($pastcourses, $favorites, $notfavorites) = $service->my_courses_split_by_favorites();
        $notfavorites = array_keys($notfavorites);
        sort($notfavorites);

        $expectedpastcourses = [
            $this->courses[10]->id,
            $this->courses[11]->id,
            $this->courses[12]->id,
            $this->courses[13]->id,
            $this->courses[14]->id,
        ];

        // Collapse pastcourses (currently hashed by year).
        $collapsed = [];
        foreach ($pastcourses as $year => $courses) {
            $collapsed = array_merge($collapsed, array_keys($courses));
        }
        $pastcourses = $collapsed;
        foreach ($expectedpastcourses as $expectedpastcourse) {
            $this->assertTrue(in_array($expectedpastcourse, $pastcourses));
        }
        $expectedfavorites = [
            $this->courses[0]->id,
            $this->courses[1]->id,
        ];
        $this->assertEquals($expectedfavorites, array_keys($favorites));

        $notfavoritecourses = array_slice($this->courses, 2, 8);
        $expectednotfavorites = array_keys($notfavoritecourses);
        sort($expectednotfavorites);
        $expectednotfavorites = [];
        foreach ($notfavoritecourses as $course) {
            $expectednotfavorites[] = $course->id;
        }
        $this->assertEquals($expectednotfavorites, $notfavorites);
    }

    public function test_setfavorite() {
        $returned = $this->courseservice->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $this->assertTrue($returned);
    }

    public function test_coursebyshortname() {
        $expected = get_course($this->courses[0]->id);
        $actual = $this->courseservice->coursebyshortname($this->courses[0]->shortname);

        $this->assertEquals($expected, $actual);
    }

    public function test_cardbyshortname() {
        $card = $this->courseservice->cardbyshortname($this->courses[0]->shortname);
        $this->assertTrue($card instanceof course_card);
        $this->assertEquals($card->courseid, $this->courses[0]->id);
    }

    // Records for favorite courses should not exist when the user is deleted.
    public function test_user_deletion() {
        global $DB;
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);
        $params = array('userid' => $this->user1->id, 'component' => 'core_course');
        $favorites = $DB->get_records('favourite', $params);
        $this->assertNotEmpty($favorites);
        delete_user($this->user1);
        $favorites = $DB->get_records('favourite', $params);
        $this->assertEmpty($favorites);
    }

    // Records for favorite courses should not exist when the course is deleted.
    public function test_course_deletion() {
        global $DB;
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);
        $params = array('userid' => $this->user1->id, 'component' => 'core_course');
        $favorites = $DB->count_records('favourite', $params);
        $this->assertEquals(2, $favorites);
        $this->assertNotEmpty($favorites);
        delete_course($this->courses[0], false);
        $favorites = $DB->count_records('favourite', $params);
        $this->assertEquals(1, $favorites);
        delete_course($this->courses[1], false);
        $favorites = $DB->get_records('favourite', $params);
        $this->assertEmpty($favorites);
    }

    private function count_course_sections($courseid) {
        global $DB;
        $count = $DB->count_records('course_sections', ['course' => $courseid]);
        return $count;
    }

    public function test_section_fragment() {
        $this->markTestSkipped('To be reviewed in INT-20324');
        global $CFG, $DB;
        require_once($CFG->dirroot .'/theme/snap/lib.php');
        $topics = $this->getDataGenerator()->create_course(
            array('numsections' => 5, 'format' => 'topics', 'initsections' => '1'),
            array('createsections' => true));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id,
            $topics->id,
            'student');
        $this->getDataGenerator()->enrol_user($teacher->id,
            $topics->id,
            'editingteacher');
        $this->getDataGenerator()->create_module('assign', ['course' => $topics->id, 'section' => 1,
            'name' => 'Section Assign', ]);
        $params = ['courseid' => $topics->id, 'section' => 1];
        $this->setUser($student);
        $section = theme_snap_output_fragment_section($params);
        $this->assertStringContainsString('aria-label="Section 1"', $section);
        // Section doesn't have the modchooser div.
        $this->assertStringNotContainsString('snap-modchooser', $section);
        $this->assertStringContainsString('Section Assign', $section);
        $this->getDataGenerator()->create_module('forum', ['course' => $topics->id, 'section' => 2,
            'name' => 'Fragment forum', ]);
        $params['section'] = 2;
        $section = theme_snap_output_fragment_section($params);
        $this->assertStringContainsString('Fragment forum', $section);
        $this->setUser($teacher);
        // Missing param will result on empty text.
        $params['section'] = '';
        $section = theme_snap_output_fragment_section($params);
        $this->assertEmpty($section);
        $params['section'] = 2;
        $section = theme_snap_output_fragment_section($params);
        $this->assertStringContainsString('Fragment forum', $section);
        $this->assertStringContainsString('snap-modchooser', $section);
    }
}
