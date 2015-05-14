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
 * Snap folderview format renderer.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/format/folderview/renderer.php');

class theme_snap_format_folderview_renderer extends format_folderview_renderer {

    protected function end_section_list() {
        $output = html_writer::end_tag('ul');
        $output .= "<section id='coursetools' class='clearfix' tabindex='-1'>";
        $output .= snap_shared::coursetools_svg_icons();
        $output .= snap_shared::appendices();
        $output .= "</section>";
        return $output;
    }

}
