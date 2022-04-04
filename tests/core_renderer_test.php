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
 * Test snap core renderer page heading
 * @author    Juan Ibarra
 * @copyright Copyright (c) 2021 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap;

/**
 * Class theme_snap_core_renderer_testcase
 * @author    Juan Ibarra
 * @copyright Copyright (c) 2021 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer_test extends \advanced_testcase {

    /**
     * Test heading should show only h1 tags on site level.
     */
    public function test_default_heading_on_standard_pagelayout_sitelevel() {
        global $COURSE, $PAGE;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $COURSE = $this->getDataGenerator()->create_course();
        $COURSE->id = SITEID; // Simulate site level.

        $PAGE = new \moodle_page();
        $PAGE->set_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
        $PAGE->set_context(\context_user::instance($user->id));
        $PAGE->set_pagelayout('standard');

        $target = null;
        $corerenderer = new \theme_snap\output\core_renderer($PAGE, $target);
        $heading = $corerenderer->page_heading();

        $this->assertEquals("<h1></h1>", $heading);
    }

    /**
     * Test heading should not show links on sitelevel.
     */
    public function test_course_link_should_not_appear_on_sitelevel() {
        global $COURSE, $PAGE;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $COURSE = $this->getDataGenerator()->create_course();
        $COURSE->id = SITEID; // Simulate site level.

        $PAGE = new \moodle_page();
        $PAGE->set_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
        $PAGE->set_context(\context_user::instance($user->id));
        $PAGE->set_pagelayout('mypublic');

        $target = null;
        $corerenderer = new \theme_snap\output\core_renderer($PAGE, $target);
        $heading = $corerenderer->page_heading();

        $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id]);
        $this->assertFalse(strpos($heading, $url->out()));
    }

    /**
     * Test heading should show link to course on courselevel.
     */
    public function test_course_link_should_appear_on_courselevel() {
        global $COURSE, $PAGE;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $COURSE = $this->getDataGenerator()->create_course();

        $PAGE = new \moodle_page();
        $PAGE->set_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
        $PAGE->set_context(\context_user::instance($user->id));
        $PAGE->set_pagelayout('mypublic');
        $PAGE->set_heading($COURSE->fullname);

        $target = null;
        $corerenderer = new \theme_snap\output\core_renderer($PAGE, $target);
        $heading = $corerenderer->page_heading();

        $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id]);
        $this->assertStringContainsString($url->out(), $heading);
    }
}
