<?php
/**
 * Snap event hooks
 *
 * @author Guy Thomas
 * @package theme_snap
 **/

$observers = array(
    array(
        'eventname'   => '\core\event\course_updated',
        'includefile' => '/theme/snap/model/handler.php',
        'callback'    => 'theme_snap_model_handler::course_updated',
    )
);