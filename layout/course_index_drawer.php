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
 * Similar to theme/boost/layout/drawers.php
 * Used to display the Course Index Drawer on Snap.
 *
 * @package    theme_snap
 * @copyright  2025 Copyright (c) 2024 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_snap\renderables\course_toc;

defined('MOODLE_INTERNAL') || die();

global $OUTPUT;

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
} else {
    $courseindexopen = false;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

// For Snap we add some HTML to Course Index.
$coursetoc = new course_toc();
$searchmodule = $OUTPUT->render_from_template('theme_snap/course_toc_module_search', $coursetoc);
$tocfooter = $OUTPUT->render_from_template('theme_snap/course_toc_footer', $coursetoc->footer);


$courseindex = $searchmodule . core_course_drawer() . $tocfooter;

if (!$courseindex) {
    $courseindexopen = false;
}

$templatecontext = [
    'courseindex' => $courseindex,
];
echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);