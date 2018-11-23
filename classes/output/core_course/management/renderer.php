<?php
// This file is part of The Bootstrap Moodle theme
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
 * Renderers to align Moodle's HTML with that expected by Bootstrap.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output\core_course\management;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use course_in_list;

/**
 * Main renderer for the course management pages.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \theme_boost\output\core_course\management\renderer {
    /**
     * @inheritdoc
     */
    public function grid_column_start($size, $id = null, $class = null) {
        $bootstrapclass = 'd-flex flex-wrap px-3 mb-3';

        $yuigridclass = "col-sm";

        if (is_null($class)) {
            $class = $yuigridclass . ' ' . $bootstrapclass;
        } else {
            $class .= ' ' . $yuigridclass . ' ' . $bootstrapclass;
        }
        $attributes = array();
        if (!is_null($id)) {
            $attributes['id'] = $id;
        }
        return html_writer::start_div($class . " grid_column_start", $attributes);
    }

    /**
     * @inheritdoc
     */
    public function course_detail(course_in_list $course) {
        $details = \core_course\management\helper::get_course_detail_array($course);
        $fullname = $details['fullname']['value'];

        $html = html_writer::start_div('course-detail card w-100');
        $html .= html_writer::tag('h3', $fullname, array('id' => 'course-detail-title',
            'class' => 'card-header', 'tabindex' => '0'));
        $html .= html_writer::start_div('card-body');
        $html .= $this->course_detail_actions($course);
        foreach ($details as $class => $data) {
            $html .= $this->detail_pair($data['key'], $data['value'], $class);
        }
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }
}
