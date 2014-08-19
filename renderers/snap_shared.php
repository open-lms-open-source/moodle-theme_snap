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

    /**
     * Taken from /format/renderer.php
     * Generate a summary of the activites in a section
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course the course record from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    public static function section_activity_summary($section, $course, $mods) {
        global $CFG;

        require_once($CFG->libdir.'/completionlib.php');

        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->sections[$section->section])) {
            return '';
        }

        // Generate array with count of activities in this section.
        $sectionmods = array();
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new completion_info($course);
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->modname == 'label') {
                // Labels are special (not interesting for students)!
                continue;
            }

            if ($thismod->uservisible) {
                if (isset($sectionmods[$thismod->modname])) {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modplural;
                    $sectionmods[$thismod->modname]['count']++;
                } else {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modfullname;
                    $sectionmods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        if (empty($sectionmods)) {
            // No sections.
            return '';
        }

        // Output section activities summary.
        $o = '';
        $o .= html_writer::start_tag('div', array('class' => 'section-summary-activities mdl-right'));
        foreach ($sectionmods as $mod) {
            $o .= html_writer::start_tag('span', array('class' => 'activity-count'));
            $o .= $mod['name'].': '.$mod['count'];
            $o .= html_writer::end_tag('span');
        }
        $o .= html_writer::end_tag('div');

        $a = false;

        // Output section completion data.
        if ($total > 0) {
            $a = new stdClass;
            $a->complete = $complete;
            $a->total = $total;
            $a->percentage = ($complete / $total) * 100;

            $o .= html_writer::start_tag('div', array('class' => 'section-summary-activities mdl-right'));
            $o .= html_writer::tag('span', get_string('progresstotal', 'completion', $a), array('class' => 'activity-count'));
            $o .= html_writer::end_tag('div');
        }

        $retobj = (object) array (
            'output' => $o,
            'progress' => $a,
            'complete' => $complete,
            'total' => $total
        );

        return $retobj;
    }

    /**
     * Based on get_nav_links function in class format_section_renderer_base
     * This function has been modified to provide a link to section 0
     * Generate next/previous section links for naviation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return array associative array with previous and next section link
     */
    public static function get_nav_links($course, $sections, $sectionno) {
        global $OUTPUT;
        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        while ($back > -1 and empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->uservisible) {
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }

                $previouslink = html_writer::tag('span', $OUTPUT->larrow(), array('class' => 'larrow'));
                $previouslink .= get_section_name($course, $sections[$back]);
                if ($back > 0 ) {
                    $courseurl = course_get_url($course, $back);
                } else {
                    // We have to create the course section url manually if its 0.
                    $courseurl = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $back));
                }
                $links['previous'] = html_writer::link($courseurl, $previouslink, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        while ($forward <= $course->numsections and empty($links['next'])) {
            if ($canviewhidden || $sections[$forward]->uservisible) {
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = get_section_name($course, $sections[$forward]);
                $nextlink .= html_writer::tag('span', $OUTPUT->rarrow(), array('class' => 'rarrow'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }

        return $links;
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

    /**
     * Javascript required by both flexpage layout and header layout
     *
     * @return void
     */
    public static function page_requires_js() {
        global $PAGE, $COURSE;
        $PAGE->requires->jquery();
        $PAGE->requires->strings_for_js(array(
            'close',
            'debugerrors',
            'problemsfound',
            'forumtopic',
            'forumauthor',
            'forumpicturegroup',
            'forumreplies',
            'forumlastpost',
            'more'
        ), 'theme_snap');

        $module = array(
            'name' => 'theme_snap_core',
            'fullpath' => '/theme/snap/javascript/module.js'
        );

        $PAGE->requires->js_init_call('M.theme_snap.core.init', array('courseid' => $COURSE->id), false, $module);
    }

    /**
     * Render a warning where flexpage is the course format for the front page.
     *
     * @author: Guy Thomas
     * @date: 2014-07-17
     * @param bool $adminsonly
     * @return string
     */
    public static function flexpage_frontpage_warning($adminsonly = false) {
        global $CFG, $OUTPUT;

        if ($adminsonly) {
            if (!is_siteadmin()) {
                // Only for admin users.
                return '';
            }
        }

        // Check to see if the front page course has a format of flexpage.
        $fpage = get_site();
        if ($fpage->format != 'flexpage') {
            // Front page format is not flexpage.
            return '';
        }

        // Output warning.
        return ($OUTPUT->notification(get_string('warnsiteformatflexpage', 'theme_snap', $CFG->wwwroot.'/admin/settings.php?section=frontpagesettings')));
    }
}
