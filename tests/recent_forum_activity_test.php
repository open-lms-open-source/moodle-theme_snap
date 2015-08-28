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
class theme_snap_recent_forum_activity_test extends \advanced_testcase {

    /**
     * @var stdClass
     */
    protected $user1;

    /**
     * @var stdClass
     */
    protected $user2;

    /**
     * @var stdClass
     */
    protected $teacher;

    /**
     * @var stdClass
     */
    protected $course1;

    /**
     * @var stdClass
     */
    protected $course2;

    /**
     * @var stdClass
     */
    protected $group1;

    /**
     * @var stdClass
     */
    protected $group2;

    /**
     * Pre-requisites for tests.
     * @throws \coding_exception
     */
    public function setUp() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest();

        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Enrol (as students) user1 to both courses but user2 only to course2.
        $sturole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->user1->id,
            $this->course1->id,
            $sturole->id);
        $this->getDataGenerator()->enrol_user($this->user1->id,
            $this->course2->id,
            $sturole->id);
        $this->getDataGenerator()->enrol_user($this->user2->id,
            $this->course2->id,
            $sturole->id);

        // Enrol teacher on both courses.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($this->teacher->id,
            $this->course1->id,
            $teacherrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id,
            $this->course2->id,
            $teacherrole->id);

        // Add 2 groups to course2.
        $this->group1 = $this->getDataGenerator()->create_group([
            'courseid' => $this->course2->id,
            'name' => 'Group 1'
        ]);
        $this->group2 = $this->getDataGenerator()->create_group([
            'courseid' => $this->course2->id,
            'name' => 'Group 2'
        ]);

        // Add user1 to both groups but user2 to just group1.
        groups_add_member($this->group1->id, $this->user1);
        groups_add_member($this->group2->id, $this->user1);
        groups_add_member($this->group1->id, $this->user2);
    }

    /**
     * Test single discussion.
     * @throws \coding_exception
     */
    public function test_forum_discussion_simple($ftype = 'forum', $toffset = 0, $u1offset = 0, $u2offset = 0) {

        // If this is not a combined test then check and make sure there is no activity (nothing done yet).
        if ($toffset === 0 && $u1offset === 0 && $u2offset === 0) {
            $activity = local::recent_forum_activity($this->teacher->id);
            $this->assertEmpty($activity);
            $activity = local::recent_forum_activity($this->user1->id);
            $this->assertEmpty($activity);
            $activity = local::recent_forum_activity($this->user2->id);
            $this->assertEmpty($activity);
        }

        $record = new \stdClass();
        $record->course = $this->course1->id;
        $forum = $this->getDataGenerator()->create_module($ftype, $record);

        // Add discussion to course 1 started by user1.
        // Note: In testing number of posts, discussions are counted too as there is a post for each discussion created.
        $this->create_discussion($ftype, $this->course1->id, $this->user1->id, $forum->id);

        // Check teacher viewable posts is 1.
        $this->assert_user_activity($this->teacher, $toffset + 1);

        // Check user1 viewable posts is 1.
        $this->assert_user_activity($this->user1, $u1offset + 1);

        // Check user2 viewable posts is 0 (user1 is not enrolled in course1).
        $this->assert_user_activity($this->user2, $u2offset + 0);
    }

    /**
     * Test hsuforum single discussion.
     */
    public function test_hsuforum_discussion_simple() {
        $this->test_forum_discussion_simple('hsuforum');
    }

    /**
     * Test hsuforum single discussion.
     */
    public function test_combined_discussion_simple() {
        $this->test_forum_discussion_simple('forum');
        $this->test_forum_discussion_simple('hsuforum', 1, 1, 0);
    }

    /**
     * Test single discussion + post.
     * @throws \coding_exception
     */
    public function test_forum_post_simple($ftype = 'forum', $toffset = 0, $u1offset = 0, $u2offset = 0) {

        $record = new \stdClass();
        $record->course = $this->course1->id;
        $forum1 = $this->getDataGenerator()->create_module($ftype, $record);

        // Add discussion to course 1 started by user1.
        // Note: In testing number of posts, discussions are counted too as there is a post for each discussion created.
        $discussion1 = $this->create_discussion($ftype, $this->course1->id, $this->user1->id, $forum1->id);
        $this->create_post($ftype, $this->course1->id, $this->user1->id, $forum1->id, $discussion1->id);

        // Check teacher viewable posts is 2.
        $this->assert_user_activity($this->teacher, $toffset + 2);

        // Check user1 viewable posts is 2.
        $this->assert_user_activity($this->user1, $u1offset + 2);

        // Check user2 viewable posts is 0 (user2 is not enrolled in course1).
        $this->assert_user_activity($this->user2, $u2offset + 0);

        // Create a forum and discussion in course2 so that user2 can see it.
        $record = new \stdClass();
        $record->course = $this->course2->id;
        $forum2 = $this->getDataGenerator()->create_module($ftype, $record);
        $discussion2 = $this->create_discussion($ftype, $this->course2->id, $this->user2->id, $forum2->id);
        $this->create_post($ftype, $this->course2->id, $this->user2->id, $forum2->id, $discussion2->id);

        // Check teacher viewable posts is 4.
        $this->assert_user_activity($this->teacher, $toffset + 4);

        // Check user1 viewable posts is 4.
        $this->assert_user_activity($this->user1, $u1offset + 4);

        // Check user2 viewable posts is 2 (user2 can only see posts in course1).
        $this->assert_user_activity($this->user2, $u2offset + 2);
    }

    /**
     * Test hsuforum single discussion + post.
     */
    public function test_hsuforum_post_simple() {
        $this->test_forum_post_simple('hsuforum');
    }

    /**
     * Test forum & hsuforum combined single discussion + post.
     */
    public function test_combined_post_simple() {
        $this->test_forum_post_simple('forum');
        $this->test_forum_post_simple('hsuforum', 4, 4, 2);
    }

    /**
     * Test a date restricted forum
     */
    public function test_forum_restricted($ftype = 'forum', $toffset = 0, $u1offset = 0, $u2offset = 0) {
        global $CFG;

        // This is crucial - without this you can't make a conditionally accsesed forum.
        $CFG->enableavailability = true;

        // Create a date restricted forum - won't be available to students until one week from now.
        $record = new \stdClass();
        $record->course = $this->course2->id;
        $opts = ['availability' => '{"op":"&","c":[{"type":"date","d":">=","t":'.(time() + WEEKSECS).'}],"showc":[true]}'];
        $record->availability = $opts['availability'];
        $forum = $this->getDataGenerator()->create_module($ftype, $record, $opts);

        // Add discussion to date restricted forum.
        $discussion = $this->create_discussion($ftype, $this->course2->id, $this->teacher->id, $forum->id);
        $this->create_post($ftype, $this->course2->id, $this->teacher->id, $forum->id, $discussion->id);

        // Check teacher viewable posts is 2.
        $this->assert_user_activity($this->teacher, $toffset + 2);

        // Check user1 viewable posts is 0 - can't see anything in restricted forum.
        $this->assert_user_activity($this->user1, $u1offset + 0);

        // Check user2 viewable posts is 0 - can't see anything in restricted forum.
        $this->assert_user_activity($this->user2, $u2offset + 0);
    }

    /**
     * Test hsuforum single discussion + post.
     */
    public function test_hsuforum_restricted() {
        $this->test_forum_restricted('hsuforum');
    }

    /**
     * Test forum & hsuforum combined single discussion + post.
     */
    public function test_combined_restricted() {
        $this->test_forum_restricted('forum');
        $this->test_forum_restricted('hsuforum', 2, 0, 0);
    }

    /**
     * Test forum posts restricted by group.
     * @param string $ftype
     */
    public function test_forum_group_posts($ftype = 'forum', $toffset = 0, $u1offset = 0, $u2offset = 0) {
        // Create a forum with group mode enabled.
        $record = new \stdClass();
        $record->course = $this->course2->id;
        $forum = $this->getDataGenerator()->create_module($ftype, $record, ['groupmode' => SEPARATEGROUPS]);

        // Add a discussion and 2 posts for group1 users.
        $discussion1 = $this->create_discussion($ftype,
            $this->course2->id, $this->user1->id, $forum->id,  $this->group1->id);

        for ($p = 1; $p <= 2; $p++) {
            // Create 1 post by user1 and user2.
            $user = $p == 1 ? $this->user1 : $this->user2;
            $this->create_post($ftype, $this->course2->id, $user->id, $forum->id, $discussion1->id);
        }

        // Add a discussion and 1 post for group2 users.
        $discussion2 = $this->create_discussion($ftype,
            $this->course2->id, $this->user1->id, $forum->id,  $this->group2->id);
        $this->create_post($ftype, $this->course2->id, $this->user1->id, $forum->id, $discussion2->id);

        // Check teacher viewable posts is 5 (can view all posts).
        $this->assert_user_activity($this->teacher, $toffset + 5);

        // Check user1 viewable posts is 5 (in all groups, can view all posts).
        $this->assert_user_activity($this->user1, $u1offset + 5);

        // Check user2 viewable posts is 3 (only in group2).
        $this->assert_user_activity($this->user2, $u2offset + 3);
    }

    /**
     * Test hsuforum posts restricted by group.
     */
    public function test_hsuforum_group_posts() {
        $this->test_forum_group_posts('hsuforum');
    }

    /**
     * Test forum & hsuforum combined  posts restricted by group.
     */
    public function test_combined_group_posts() {
        $this->test_forum_group_posts('forum');
        $this->test_forum_group_posts('hsuforum', 5, 5, 3);
    }

    /**
     * Create a discussion.
     *
     * @param $ftype
     * @param $courseid
     * @param $userid
     * @param $forumid
     * @return mixed
     * @throws \coding_exception
     */
    protected function create_discussion($ftype, $courseid, $userid, $forumid, $groupid = null) {
        // Add discussion to course 1 started by user1.
        $record = new \stdClass();
        $record->course = $courseid;
        $record->userid = $userid;
        $record->forum = $forumid;
        if ($groupid !== null) {
            $record->groupid = $groupid;
        }
        return ($this->getDataGenerator()->get_plugin_generator('mod_'.$ftype)->create_discussion($record));
    }

    /**
     * Create a post.
     *
     * @param $ftype
     * @param $courseid
     * @param $userid
     * @param $forumid
     * @param $discussionid
     * @return mixed
     * @throws \coding_exception
     */
    protected function create_post($ftype, $courseid, $userid, $forumid, $discussionid) {
        $record = new \stdClass();
        $record->course = $courseid;
        $record->userid = $userid;
        $record->forum = $forumid;
        $record->discussion = $discussionid;
        return ($this->getDataGenerator()->get_plugin_generator('mod_'.$ftype)->create_post($record));
    }

    /**
     * Assert user activity.
     * @param $user
     * @param $expected
     */
    protected function assert_user_activity($user, $expected) {
        $activity = local::recent_forum_activity($user->id);
        $this->assertEquals($expected, count($activity));
    }

}