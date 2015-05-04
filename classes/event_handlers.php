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

        $course  = $event->get_record_snapshot('course', $event->objectid);
        $context = \context_course::instance($course->id);

        local::process_coverimage($context);
    }
}