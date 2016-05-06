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

use theme_snap\services\course;
use theme_snap\renderables\course_card;

/**
 * Test course card service.
 * @package   theme_snap
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_services_course_test extends \advanced_testcase {

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
     * @throws \coding_exception
     */
    public function setUp() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest();

        // Create 10 courses
        for ($c = 0; $c < 10; $c++) {
            $this->courses[] = $this->getDataGenerator()->create_course();
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

        $favorites = $service->favorites($this->user1->id);
        $this->assertTrue(isset($favorites[$this->courses[0]->id]));
        $this->assertTrue(isset($favorites[$this->courses[1]->id]));
        $this->assertFalse(isset($favorites[$this->courses[2]->id]));
    }

    public function test_my_courses_split_by_favorites() {
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);

        $this->setUser($this->user1);
        list ($favorites, $notfavorites) = $service->my_courses_split_by_favorites();

        $expectedfavorites = [
            $this->courses[0]->id,
            $this->courses[1]->id
        ];

        $this->assertEquals($expectedfavorites, array_keys($favorites));
        $notfavoritecourses = array_slice($this->courses, 2);
        $expectednotfavorites = [];
        foreach ($notfavoritecourses as $course) {
            $expectednotfavorites[] = $course->id;
        }
        asort($notfavorites);
        $this->assertEquals($expectednotfavorites, array_keys($notfavorites));
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

}
