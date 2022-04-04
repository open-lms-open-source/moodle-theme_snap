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

namespace theme_snap\renderables;
// Prepare for code checker update. Will be remove on INT-17966.
// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die();

use theme_snap\local,
    moodle_url;

/**
 * Featured courses renderable
 *
 * @author    Guy Thomas
 * @copyright Copyright (c) 2017 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class featured_courses implements \renderable, \templatable {

    use trait_exportable;

    /**
     * @var featured_course[];
     */
    public $cards = [];

    /**
     * @var string
     */
    public $heading;

    /**
     * @var null | moodle_url
     */
    public $browseallurl = null;

    /**
     * @var null | moodle_url
     */
    public $editurl = null;

    public function __construct() {
        global $PAGE, $DB;

        $config = get_config('theme_snap');

        // Featured courses title.
        if (!empty($config->fc_heading)) {
            $this->heading = $config->fc_heading;
        }

        if (!empty($config->fc_browse_all)) {
            $url = new moodle_url('/course/');
            $this->browseallurl = $url;
        }

        if ($PAGE->user_is_editing()) {
            $url = new moodle_url('/admin/settings.php?section=themesettingsnap#themesnapfeaturedcourses');
            $this->editurl = $url;
        }

        // Build array of course ids to display.
        $ids = array("fc_one", "fc_two", "fc_three", "fc_four", "fc_five", "fc_six", "fc_seven", "fc_eight");
        $courseids = array();
        $config = get_config('theme_snap');
        foreach ($ids as $id) {
            if (!empty($config->$id)) {
                $courseids[] = $config->$id;
            }
        }

        // Get DB records for course ids.
        if (count($courseids)) {
            list ($coursesql, $params) = $DB->get_in_or_equal($courseids);
            $sql = "SELECT * FROM {course} WHERE id $coursesql";
            $courses = $DB->get_records_sql($sql, $params);
        } else {
            return;
        }

        // Order records to match order input.
        $orderedcourses = array();
        foreach ($courseids as $courseid) {
            if (!empty($courses[$courseid])) {
                $orderedcourses[] = $courses[$courseid];
            }
        }

        // Calculate boostrap column class.
        $count = count($orderedcourses);
        $colclass = '';
        if ($count >= 4) {
            $colclass = 'col-sm-3'; // Four cards = 25%.
        }
        if ($count === 1 || $count === 2) {
            $colclass = 'col-sm-6'; // One or two cards = 50%.
        }
        if ($count === 3 || $count === 6) {
            $colclass = 'col-sm-4'; // Three cards = 33.3%.
        }
        $this->colclass = $colclass;

        // Build featured course card renderables.
        $i = 0;
        foreach ($orderedcourses as $course) {
            $i ++;
            $url = new moodle_url('/course/view.php?id=' .$course->id);
            $coverimageurl = local::course_coverimage_url($course->id);
            $coverimageurl = $coverimageurl ?: null;
            $this->cards[] = new featured_course($url, $coverimageurl, $course->fullname, $i, $this->colclass);
        }
    }
}
