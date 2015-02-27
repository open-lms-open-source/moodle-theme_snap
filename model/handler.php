<?php

use core\event\course_updated;
use theme_snap\local;

/**
 * Model Handler
 *
 * This class contains all of our event handlers
 *
 * @package theme_snap
 * @author Guy Thomas
 */
class theme_snap_model_handler {


    /**
     * The course update event
     *
     * Only trick here is that it must reverse engineer
     * the category.  On failure, the category is unset
     * and whatever is in conduit remains unmodified
     *
     * @param course_updated $event
     * @return void
     */
    public static function course_updated(course_updated $event) {

        $course  = $event->get_record_snapshot('course', $event->objectid);
        $context = context_course::instance($course->id);

        local::process_coverimage($context);
    }
}