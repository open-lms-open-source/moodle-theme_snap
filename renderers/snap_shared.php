<?php
// This file is part of The Snap theme
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
 * Snap shared renderers
 *
 * @package    theme_snap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class snap_shared extends renderer_base {

    /**
     * Next and previous links for Snap theme sections
     *
     * Mostly a spruced up version of the get_nav_links logic, since that
     * renderer mixes the logic of retrieving and building the link targets
     * based on availability with creating the HTML to display them niceley.
     * @return string
     */
    public static function next_previous($course, $sections, $sectionno) {
        $course = course_get_format($course)->get_course();

        $previousarrow = '<i class="icon-arrows-03"></i>';
        $nextarrow = '<i class="icon-arrows-04"></i>';

        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
            or !$course->hiddensections;

        $previous = '';
        $target = $sectionno - 1;
        while ($target >= 0 && empty($previous)) {
            if ($canviewhidden || $sections[$target]->uservisible) {
                $attributes = array('id' => 'previous_section');
                if (!$sections[$target]->visible) {
                    $attributes['class'] = 'dimmed_text';
                }
                $sectionname = get_section_name($course, $sections[$target]);
                $previousstring = get_string('previoussection', 'theme_snap');
                $linkcontent = self::target_link_content($sectionname, $previousarrow, $previousstring);
                $previous = html_writer::link(course_get_url($course, $target), $linkcontent, $attributes);
            }
            $target--;
        }

        $next = '';
        $target = $sectionno + 1;
        while ($target <= $course->numsections && empty($next)) {
            if ($canviewhidden || $sections[$target]->uservisible) {
                $attributes = array('id' => 'next_section');
                if (!$sections[$target]->visible) {
                    $attributes['class'] = 'dimmed_text';
                }
                $sectionname = get_section_name($course, $sections[$target]);
                $nextstring = get_string('nextsection', 'theme_snap');
                $linkcontent = self::target_link_content($sectionname, $nextarrow, $nextstring);
                $next = html_writer::link(course_get_url($course, $target), $linkcontent, $attributes);
            }
            $target++;
        }
        return html_writer::tag('nav', $previous.$next, array('id' => 'section_footer'));
    }

    private static function target_link_content($name, $arrow, $string) {
        $html = html_writer::div($arrow, 'nav_icon');
        $html .= html_writer::start_span('text');
        $html .= html_writer::span($string, 'nav_guide');
        $html .= html_writer::empty_tag('br');
        $html .= $name;
        $html .= html_writer::end_tag('span');
        return $html;
    }
}
