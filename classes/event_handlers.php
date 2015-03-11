<?php

namespace theme_snap;
use core\event\course_updated;

/**
 * Event handlers
 *
 * This class contains all of our event handlers
 *
 * @package theme_snap
 * @author Guy Thomas
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