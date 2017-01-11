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
use core\event\course_updated;
use core\event\course_deleted;
use core\event\course_completion_updated;
use core\event\course_module_created;
use core\event\course_module_updated;
use core\event\course_module_deleted;
use core\event\course_module_completion_updated;

defined('MOODLE_INTERNAL') || die();

/**
 * Event handlers.
 *
 * This class contains all the event handlers used by Snap.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class event_handlers {

    /**
     * The course update event.
     *
     * process cover image.
     *
     * @param course_updated $event
     * @return void
     */
    public static function course_updated(course_updated $event) {
        $context = \context_course::instance($event->objectid);

        local::process_coverimage($context);
    }

    /**
     * The course delete event.
     *
     * Delete course favorite records when course is deleted.
     *
     * @param course_deleted $event
     */
    public static function course_deleted(course_deleted $event) {
        global $DB;

        $select = ['courseid' => $event->objectid];
        $DB->delete_records('theme_snap_course_favorites', $select);
    }

    /**
     * The user delete event.
     *
     * Delete course favorite records when an user is deleted.
     *
     * @param user_deleted $event
     */
    public static function user_deleted($event) {
        global $DB;

        $select = ['userid' => $event->objectid];
        $DB->delete_records('theme_snap_course_favorites', $select);
    }

    /**
     * Update course completion time stamp for course affected by event.
     * @param course_completion_updated $event
     */
    public static function course_completion_updated(course_completion_updated $event) {
        // Force an update of course completion cache stamp.
        local::course_completion_cachestamp($event->courseid, true);
    }

    /**
     * Update course completion time stamp for course affected by event.
     * @param course_module_created $event
     */
    public static function course_module_created(course_module_created $event) {
        // Force an update of course completion cache stamp.
        local::course_completion_cachestamp($event->courseid, true);
    }

    /**
     * Update course completion time stamp for course affected by event.
     * @param course_module_updated $event
     */
    public static function course_module_updated(course_module_updated $event) {
        // Force an update of course completion cache stamp.
        local::course_completion_cachestamp($event->courseid, true);
    }

    /**
     * Update course completion time stamp for course affected by event.
     * @param course_module_deleted $event
     */
    public static function course_module_deleted(course_module_deleted $event) {
        // Force an update of course completion cache stamp.
        local::course_completion_cachestamp($event->courseid, true);
    }

    /**
     * Purge session level cache for affected course.
     * @param course_module_completion_updated $event
     */
    public static function course_module_completion_updated(course_module_completion_updated $event) {
        // Force an update for the specific course and user effected by this completion event.
        local::course_user_completion_cachestamp($event->courseid, $event->relateduserid, true);
    }

}
