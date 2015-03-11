<?php
/**
 * Snap event hooks
 *
 * @author Guy Thomas
 * @package theme_snap
 **/

$observers = array(
    array (
        'eventname' => '\core\event\course_updated',
        'callback'  => '\theme_snap\event_handlers::course_updated',
    )
);