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
 * Snap event hooks.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array (
        'eventname' => '\core\event\course_updated',
        'callback'  => '\theme_snap\event_handlers::course_updated',
    ),
    array (
        'eventname' => '\core\event\course_deleted',
        'callback'  => '\theme_snap\event_handlers::course_deleted'
    ),
    array (
        'eventname' => '\core\event\user_deleted',
        'callback'  => '\theme_snap\event_handlers::user_deleted'
    ),

    // All events affecting course completion at course level.
    array (
        'eventname' => '\core\event\course_completion_updated',
        'callback'  => '\theme_snap\event_handlers::course_completion_updated'
    ),
    array (
        'eventname' => '\core\event\course_module_created',
        'callback'  => '\theme_snap\event_handlers::course_module_created'
    ),
    array (
        'eventname' => '\core\event\course_module_updated',
        'callback'  => '\theme_snap\event_handlers::course_module_updated'
    ),
    array (
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => '\theme_snap\event_handlers::course_module_deleted'
    ),

    // User level course completion event.
    array (
        'eventname' => '\core\event\course_module_completion_updated',
        'callback'  => '\theme_snap\event_handlers::course_module_completion_updated'
    )
);
