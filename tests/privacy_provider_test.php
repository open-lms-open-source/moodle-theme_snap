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
 * Testcase for Snap theme privacy implementation.
 *
 * @package    theme_snap
 * @author     Sam Chaffee
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\transform;
use theme_snap\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Testcase for Snap theme privacy implementation.
 *
 * @package    theme_snap
 * @copyright  Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_privacy_provider_testcase extends provider_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_get_contexts_for_userid() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->assertEmpty(provider::get_contexts_for_userid($user1->id));

        $favorites = [];
        foreach ([1, 2] as $courseid) {
            $favorites[] = (object) ['userid' => $user1->id, 'courseid' => $courseid, 'timefavorited' => time()];
        }
        $favorites[] = (object) ['userid' => $user2->id, 'courseid' => 2, 'timefavorited' => time()];
        $this->create_favorites($favorites);

        $contextlist = provider::get_contexts_for_userid($user1->id);
        $this->assertCount(1, $contextlist);

        $usercontext = \context_user::instance($user1->id);
        $this->assertEquals($usercontext->id, $contextlist->get_contextids()[0]);
    }

    public function test_export_user_data() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $favorites = [];
        foreach ([$course1, $course2] as $course) {
            $favorites[] = (object) ['userid' => $user1->id, 'courseid' => $course->id, 'timefavorited' => time()];
        }
        $favorites[] = (object) ['userid' => $user2->id, 'courseid' => $course2->id, 'timefavorited' => time()];
        $this->create_favorites($favorites);

        $user1context = \context_user::instance($user1->id);

        $writer = writer::with_context($user1context);
        $this->assertFalse($writer->has_any_data());

        $approvedlist = new approved_contextlist($user1, 'theme_snap', [$user1context->id]);
        provider::export_user_data($approvedlist);

        $data = $writer->get_data(['theme_snap-course-favorites']);

        $this->assertCount(2, $data->favorites);

        $this->assertEquals(format_string($course1->fullname, true), $data->favorites[0]->course);
        $this->assertEquals($user1->id, $data->favorites[0]->user);
        $this->assertEquals(transform::datetime($favorites[0]->timefavorited), $data->favorites[0]->timefavorited);

        $this->assertEquals(format_string($course2->fullname, true), $data->favorites[1]->course);
        $this->assertEquals($user1->id, $data->favorites[1]->user);
        $this->assertEquals(transform::datetime($favorites[1]->timefavorited), $data->favorites[1]->timefavorited);
    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        list($user1, $user2) = $this->delete_setup();
        $this->assertEquals(4, $DB->count_records('theme_snap_course_favorites', []));

        $user1context = \context_user::instance($user1->id);
        provider::delete_data_for_all_users_in_context($user1context);
        $this->assertEquals(0, $DB->count_records('theme_snap_course_favorites', ['userid' => $user1->id]));
        $this->assertEquals(2, $DB->count_records('theme_snap_course_favorites', ['userid' => $user2->id]));

        $user2context = \context_user::instance($user2->id);
        provider::delete_data_for_all_users_in_context($user2context);

        $this->assertEquals(0, $DB->count_records('theme_snap_course_favorites', []));
    }

    public function test_delete_data_for_user() {
        global $DB;

        list($user1, $user2) = $this->delete_setup();
        $this->assertEquals(4, $DB->count_records('theme_snap_course_favorites', []));

        $user1context = \context_user::instance($user1->id);
        $approvedcontextlist = new approved_contextlist($user1, 'theme_snap', [$user1context->id]);
        provider::delete_data_for_user($approvedcontextlist);

        $this->assertEquals(0, $DB->count_records('theme_snap_course_favorites', ['userid' => $user1->id]));
        $this->assertEquals(2, $DB->count_records('theme_snap_course_favorites', ['userid' => $user2->id]));

        $user2context = \context_user::instance($user2->id);
        $approvedcontextlist = new approved_contextlist($user2, 'theme_snap', [$user2context->id]);
        provider::delete_data_for_user($approvedcontextlist);

        $this->assertEquals(0, $DB->count_records('theme_snap_course_favorites', []));
    }

    private function delete_setup() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $favorites = [];
        foreach ([$course1, $course2] as $course) {
            $favorites[] = (object) ['userid' => $user1->id, 'courseid' => $course->id, 'timefavorited' => time()];
            $favorites[] = (object) ['userid' => $user2->id, 'courseid' => $course->id, 'timefavorited' => time()];
        }

        $this->create_favorites($favorites);

        return [$user1, $user2];
    }

    /**
     * @param array $favorites
     */
    private function create_favorites($favorites) {
        global $DB;

        if (!is_array($favorites)) {
            return;
        }

        $DB->insert_records('theme_snap_course_favorites', $favorites);
    }
}