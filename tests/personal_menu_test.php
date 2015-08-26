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

defined('MOODLE_INTERNAL') || die();

/**
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_personal_menu_test extends \advanced_testcase {

    public function setUp() {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_forum\subscriptions::reset_forum_cache();
    }

    public function tearDown() {
        // We must clear the subscription caches. This has to be done both before each test, and after in case of other
        // tests using these functions.
        \mod_forum\subscriptions::reset_forum_cache();
    }

    /**
     *
     * @throws \coding_exception
     */
    public function test_forums() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $sturole = $DB->get_record('role', array('shortname'=>'student'));

        // Enrol user1 to both courses but user2 only to course2
        $this->getDataGenerator()->enrol_user($user1->id,
            $course1->id,
            $sturole->id);
        $this->getDataGenerator()->enrol_user($user1->id,
            $course2->id,
            $sturole->id);
        $this->getDataGenerator()->enrol_user($user2->id,
            $course2->id,
            $sturole->id);

        // There are no forums to start with, check activity array is empty.
        $activity = local::recent_forum_activity($user1->id);
        $this->assertEmpty($activity);

        // Create 2 regular forums, one in each course.
        $record = new \stdClass();
        $record->course = $course1->id;
        $forum1 = $this->getDataGenerator()->create_module('forum', $record);

        $record = new \stdClass();
        $record->course = $course2->id;
        $forum2 = $this->getDataGenerator()->create_module('forum', $record);

        // Add discussion to course 1 started by user1.
        $record = new \stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->forum = $forum1->id;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add discussion to course 2 started by user1.
        $record = new \stdClass();
        $record->course = $course2->id;
        $record->userid = $user1->id;
        $record->forum = $forum2->id;
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add discussions to course2 started by user2.
        $record = new \stdClass();
        $record->course = $course2->id;
        $record->userid = $user2->id;
        $record->forum = $forum2->id;
        $discussion3 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to forum1 and 2 by user1.
        $record = new \stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->forum = $forum1->id;
        $record->discussion = $discussion1->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);
        $record = new \stdClass();
        $record->course = $course2->id;
        $record->userid = $user2->id;
        $record->forum = $forum2->id;
        $record->discussion = $discussion2->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // Add post to forum2 by user1.
        $record = new \stdClass();
        $record->course = $course2->id;
        $record->userid = $user2->id;
        $record->forum = $forum2->id;
        $record->discussion = $discussion2->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        $activity = local::recent_forum_activity($user1->id);
        // Should be 6 posts (a discussion is also a post).
        $this->assertEquals(6, count($activity));

    }
}