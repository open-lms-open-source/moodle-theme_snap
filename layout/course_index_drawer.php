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

use theme_snap\renderables\course_toc_module;

defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $PAGE, $COURSE, $USER, $DB;

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

// Load hidden TOC activities for this Course.
$hiddencmids = [];

if (!empty($COURSE->id)) {
    $sql = "
        SELECT h.cmid
          FROM {theme_snap_toc_hidden} h
          JOIN {course_modules} cm ON cm.id = h.cmid
         WHERE cm.course = :courseid
    ";

    $records = $DB->get_records_sql($sql, ['courseid' => $COURSE->id]);
    $hiddencmids = array_keys($records);
}

// Add snap activities for course index search.
$coursetocmodules = get_modules($hiddencmids);
$tocmodules = (object) [
    'modules' => $coursetocmodules,
];
$searchmodule = $OUTPUT->render_from_template('theme_snap/course_toc_module_search', $tocmodules);

// Set Snap course index footer.
$footer = (object) [
    'canaddnewsection' => has_capability('moodle/course:update', context_course::instance($PAGE->course->id)),
    'imgurladdnewsection' => $OUTPUT->image_url('pencil', 'theme'),
    'imgurltools' => $OUTPUT->image_url('course_dashboard', 'theme'),
];
$tocfooter = $OUTPUT->render_from_template('theme_snap/course_toc_footer', $footer);

// Add progressbar to course Index.
$progressbar = $OUTPUT->render_from_template('theme_snap/course_toc_progress_bar',
    $OUTPUT->get_course_completion_data($COURSE, $USER));

$courseindex = $searchmodule . $progressbar . core_course_drawer() . $tocfooter;

if (!$courseindex) {
    $courseindexopen = false;
}

// Pass hidden TOC activities list to JavaScript.
$PAGE->requires->js_amd_inline("
    require(['core/config'], function(cfg) {
        cfg.hiddenTocActivities = " . json_encode($hiddencmids) . ";
    });
");

$templatecontext = [
    'iscourseindex' => true,
    'courseindex' => $courseindex,
    'courseindexopen' => $courseindexopen,
];
echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);


/**
 * Get modules for course index search tool.
 * @throws \core\exception\coding_exception
 */
function get_modules(array $hiddencmids) {
    global $OUTPUT, $PAGE;
    // If course does not have any sections then exit - note, module search is not supported in course formats
    // that don't have sections.
    $numsections = course_get_format($PAGE->course)->get_last_section_number();
    if (empty($numsections)) {
        return;
    }

    $modinfo = get_fast_modinfo($PAGE->course);
    $modules = [];

    foreach ($modinfo->get_cms() as $cm) {
        if ($cm->modname == 'label') {
            continue;
        }
        if ($cm->sectionnum > $numsections) {
            continue; // Module outside of number of sections.
        }
        if (!$cm->uservisible && (empty($cm->availableinfo))) {
            continue; // Hidden completely.
        }

        // Skip modules that are hidden in TOC.
        if (in_array($cm->id, $hiddencmids)) {
            continue;
        }

        $module = new course_toc_module();
        $module->cmid = $cm->id;
        $module->uservisible = $cm->uservisible;
        $module->modname = $cm->modname;
        $module->iconurl = $cm->get_icon_url();
        if ($cm->modname !== 'resource') {
            $module->srinfo = get_string('pluginname', $cm->modname);
        }
        $module->url = '#section-'.$cm->sectionnum.'&module-'.$cm->id;

        $module->formattedname = $cm->get_formatted_name();
        $modules[] = $module;
    }
    return $modules;
}