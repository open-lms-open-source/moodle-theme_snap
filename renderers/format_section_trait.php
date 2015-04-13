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
 * Trait - format section
 * Code that is shared between course_format_topic_renderer.php and course_format_weeks_renderer.php
 * Used for section outputs.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

trait format_section_trait {

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
    protected function get_nav_links($course, $sections, $sectionno) {
        global $OUTPUT;
        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        while ($back > -1 and empty($links['previous'])) {
            if ($canviewhidden
            || $sections[$back]->uservisible
            || $sections[$back]->availableinfo) {
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
            if ($canviewhidden
            || $sections[$forward]->uservisible
            || $sections[$forward]->availableinfo) {
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

    /**
     * Create target link content
     *
     * @param $name
     * @param $arrow
     * @param $string
     * @return string
     */
    private function target_link_content($name, $arrow, $string) {
        $html = html_writer::div($arrow, 'nav_icon');
        $html .= html_writer::start_span('text');
        $html .= html_writer::span($string, 'nav_guide');
        $html .= html_writer::empty_tag('br');
        $html .= $name;
        $html .= html_writer::end_tag('span');
        return $html;
    }

    /**
     *
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;

        $o = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        // SHAME - the tabindex is intefering with moodle js.
        // SHAME - Remove tabindex when editing menu is shown.
        $sectionarrayvars = array('id' => 'section-'.$section->section,
        'class' => 'section main clearfix'.$sectionstyle,
        'role' => 'region',
        'aria-label' => get_section_name($course, $section));
        if (!$PAGE->user_is_editing()) {
            $sectionarrayvars['tabindex'] = '-1';
        }

        $o .= html_writer::start_tag('li', $sectionarrayvars);

        // Ok, in testing left content is actually empty...??
        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $rightcontent .= $leftcontent;
        $rightcontent = preg_replace("/<br\W*\/?>/", "\n", $rightcontent);

        $o .= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null.

        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one.
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }

        $sectiontitle = get_section_name($course, $section);
        // Better first section title.
        if ($sectiontitle == get_string('general') && $section->section == 0) {
            $sectiontitle = get_string('introduction', 'theme_snap');
        }

        $o .= $this->output->heading($sectiontitle, 2, 'sectionname' . $classes);

        // Editing commands.
        if ($PAGE->user_is_editing()) {
            $o .= html_writer::tag('div', $rightcontent, array(
                'class' => 'left right side snap-section-editing',
                'role' => 'region',
                'aria-label' => 'topic actions',
            ));
        }

        $o .= "<div class='summary'>";
        $o .= $this->format_summary_text($section);

        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:update', $context)) {
            $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
            $o .= html_writer::link($url,
                html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/settings'),
                    'class' => 'iconsmall edit', 'alt' => get_string('edit'))),
                array('title' => get_string('editsummary')));
        }
        $o .= "</div>";

        $o .= $this->section_availability_message($section,
            has_capability('moodle/course:viewhiddensections', $context));

        return $o;
    }

    /**
     * Next and previous links for Snap theme sections
     *
     * Mostly a spruced up version of the get_nav_links logic, since that
     * renderer mixes the logic of retrieving and building the link targets
     * based on availability with creating the HTML to display them niceley.
     * @return string
     */
    protected function next_previous($course, $sections, $sectionno) {
        $course = course_get_format($course)->get_course();

        $previousarrow = '<i class="icon-arrows-03"></i>';
        $nextarrow = '<i class="icon-arrows-04"></i>';

        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
        or !$course->hiddensections;

        $previous = '';
        $target = $sectionno - 1;
        while ($target >= 0 && empty($previous)) {
            if ($canviewhidden
                || $sections[$target]->uservisible
                || $sections[$target]->availableinfo) {
                $attributes = array('class' => 'previous_section');
                if (!$sections[$target]->visible) {
                    $attributes['class'] .= ' dimmed_text';
                }
                $sectionname = get_section_name($course, $sections[$target]);
                // Better first section title.
                if ($sectionname == get_string('general')) {
                    $sectionname = get_string('introduction', 'theme_snap');
                }
                $previousstring = get_string('previoussection', 'theme_snap');
                $linkcontent = $this->target_link_content($sectionname, $previousarrow, $previousstring);
                $url = course_get_url($course)."#section-$target";
                $previous = html_writer::link($url, $linkcontent, $attributes);
            }
            $target--;
        }

        $next = '';
        $target = $sectionno + 1;
        while ($target <= $course->numsections && empty($next)) {
            if ($canviewhidden
                || $sections[$target]->uservisible
                || $sections[$target]->availableinfo) {
                $attributes = array('class' => 'next_section');
                if (!$sections[$target]->visible) {
                    $attributes['class'] .= ' dimmed_text';
                }
                $sectionname = get_section_name($course, $sections[$target]);
                // Better first section title.
                if ($sectionname == get_string('general')) {
                    $sectionname = get_string('introduction', 'theme_snap');
                }
                $nextstring = get_string('nextsection', 'theme_snap');
                $linkcontent = $this->target_link_content($sectionname, $nextarrow, $nextstring);
                $url = course_get_url($course)."#section-$target";
                $next = html_writer::link($url, $linkcontent, $attributes);
            }
            $target++;
        }
        return "<nav class='section_footer'>".$previous.$next."</nav>";
    }


    // Basically unchanged from the core version  but inserts calls to
    // theme_snap_next_previous to add some navigation .
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {

            if ($section > $course->numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                continue;
            }

            $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id));

            // Student check.
            if (!$canviewhidden) {
                $conditional = false;
                if (!empty(json_decode($thissection->availability)->c)) {
                    $conditional = true;
                }
                // HIDDEN SECTION - If nothing in show hidden sections, and course section is not visible - don't print.
                if (!$conditional && $course->hiddensections && !$thissection->visible) {
                    continue;
                }
                // CONDITIONAL SECTIONS - If its not visible to the user and we have no info why - don't print.
                if ($conditional && !$thissection->uservisible && !$thissection->availableinfo) {
                    continue;
                }

                // If hidden sections are collapsed - print a fake li.
                if (!$conditional && !$course->hiddensections && !$thissection->visible) {
                    echo $this->section_hidden($section);
                    continue;
                }
            }

            echo $this->section_header($thissection, $course, false, 0);
            if ($thissection->uservisible || !empty($thissection->availableinfo)) {
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                if (!$PAGE->user_is_editing()) {
                    echo $this->next_previous($course, $modinfo->get_section_info_all(), $section);
                }
            }
            echo $this->section_footer();
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }
        }
        echo $this->end_section_list();
    }

    protected function end_section_list() {
        global $COURSE;
        $output = html_writer::end_tag('ul');
        $output .= $this->change_num_sections($COURSE);
        $output .= "<section id='coursetools' class='clearfix' tabindex='-1'>";
        $output .= snap_shared::coursetools_svg_icons();
        $output .= snap_shared::appendices();
        $output .= "</section>";
        return $output;
    }


    /**
     * Render a form to create a new course section, prompting for basic info.
     *
     * @return string
     */
    private function change_num_sections($course) {
        global $PAGE;

        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        if (!$PAGE->user_is_editing()
                || !has_capability('moodle/course:update', $context)) {
            return '';
        }

        $output = "<div class='snap-section-remove'>";
        if ($course->numsections > 0) {
            $strremovesection = get_string('removethissection', 'theme_snap');
            $url = new moodle_url('/course/changenumsections.php',
                array('courseid' => $course->id,
                    'increase' => false,
                    'sesskey' => sesskey()));
            $output .= html_writer::link($url, $strremovesection, array('class' => 'btn btn-default'));
        }
        $output .= "</div>";

        $url = new moodle_url('/theme/snap/index.php', array(
            'sesskey'  => sesskey(),
            'action' => 'addsection',
            'contextid' => $context->id,
        ));

        $heading = get_string('addanewsection', 'theme_snap');
        $output .= "<h3>$heading</h3>";
        $output .= html_writer::start_tag('form', array(
            'method' => 'post',
            'action' => $url->out_omit_querystring()
        ));
        $output .= html_writer::input_hidden_params($url);
        $output .= '<div class="form-group">';
        $output .= html_writer::label(get_string('sectionname'), 'newsection', true);
        $output .= html_writer::empty_tag('input', array(
            'id' => 'newsection',
            'type' => 'text',
            'size' => 50,
            'name' => 'newsection',
            'required' => 'required',
        ));
        $output .= '</div>';
        $output .= '<div class="form-group">';
        $output .= html_writer::label(get_string('summary'), 'summary', true);
        $output .= print_textarea(true, 10, 150, "100%",
            "auto", "summary", '', $course->id, true);
        $output .= '</div>';
        $output .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'addtopic',
            'value' => get_string('createsection', 'theme_snap'),
        ));
        $output .= html_writer::end_tag('form');

        return $output;
    }



}
