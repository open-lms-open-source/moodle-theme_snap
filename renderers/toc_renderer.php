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
 * Snap TOC Renderer
 *
 * @package    theme_snap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class toc_renderer extends core_renderer {

    /**
     * table of contents link information
     * @param string $label
     * @return string
     *
     * @Author Guy Thomas
     * @Date 2014-05-23
     */
    protected function toc_linkinfo($label) {
        $linkinfo = '<small class="published-status">'.$label.'</small>';
        return ($linkinfo);
    }

    /**
     * toc progress percentage
     * @param stdClass $section
     * @param stdClass $course
     * @param boolean $perc - display as a percentage if true
     * @return string
     *
     * @Author Guy Thomas
     * @Date 2014-05-23
     */
    protected function toc_progress($section, $course, $perc = false) {
        global $CFG, $USER, $OUTPUT;

        require_once($CFG->libdir.'/completionlib.php');

        $completioninfo = new completion_info($course);
        if (!$completioninfo->is_enabled()) {
            return ''; // Completion tracking not enabled.
        }
        // If you have the ability to manage grades then you should NOT be looking at your own progress!
        if (has_capability('moodle/grade:manage', context_course::instance($course->id), $USER)) {
            return '';
        }
        $sac = snap_shared::section_activity_summary($section, $course, null);
        if (!empty($sac->progress)) {
            if ($perc) {
                $percentage = $sac->progress->percentage != null ? round($sac->progress->percentage, 0).'%' : '';
                return ('<span class="completionstatus percentage">'.$percentage.'</span>');
            } else {
                if ($sac->progress->total > 0) {
                    $progress = get_string('progresstotal', 'completion', $sac->progress);
                    if ($sac->progress->complete === $sac->progress->total) {
                        $winbadge = $OUTPUT->pix_url('i/completion-auto-y');
                        $completedstr = s(get_string('completed', 'completion'));
                        $completed = "<img class=snap-section-complete src='$winbadge' alt='$completedstr' />";
                    }
                    $printprogress = "<span class='completionstatus outoftotal'>$completed $progress</span>";
                    return $printprogress;
                } else {
                    return ('');
                }
            }
        }
    }


    /**
     * Print  table of contents for a course
     *
     * @Author: Stuart Lamour
     */
    public function print_course_toc() {

        global $COURSE, $PAGE, $OUTPUT;

        // No access to course, return nothing.
        if (!can_access_course($COURSE)) {
            return '';
        }

        $viewhiddensections = has_capability('moodle/course:viewhiddensections', context_course::instance($COURSE->id));

        $format     = course_get_format($this->page->course);
        $course     = $format->get_course();

        // We don't want to display the toc if the current course is the site.
        if ($COURSE->id == SITEID) {
            return;
        }

        // If course does not have any sections then exit - it can't be a course without sections!!!
        if (!isset($course->numsections)) {
            return;
        }

        $singlepage = (!property_exists($course, 'coursedisplay') || $course->coursedisplay == COURSE_DISPLAY_SINGLEPAGE);
        if ($COURSE->format === 'folderview') {
            // Folderview sets coursedisplay to COURSE_DISPLAY_SINGLEPAGE
            // but has multiple pages we want to navigate to.
            $singlepage = false;
        }
        $contents = get_string('contents', 'theme_snap');
        $appendices = get_string('appendices', 'theme_snap');
        $coursenavigation = get_string('coursenavigation', 'theme_snap');
        $o = '<nav id="course-toc" role="navigation" aria-label="'.s($coursenavigation).'">
        <div role="menubar">
        <span><a href="#sections">'.$contents.'</a></span>
        <span><a href="#blocks">'.$appendices.'</a></span>';

            $search = get_string('search');
            $o .= '
            <label class="sr-only" for="toc-search-input">Search</label>
            <input id="toc-search-input"  type="text" title="'.s($search).'" placeholder="&#xe0d0;" />
            '.$this->modulesearch();

        $o .= '</div>';
        $listlarge = '';
        if ($course->numsections > 11) {
            $listlarge = "list-large";
        }
        $toc = '<ol id="chapters" class="chapters '.$listlarge.'" role="menu" start="0">';

        course_create_sections_if_missing($course, range(0, $course->numsections));

        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section > $course->numsections) {
                continue;
            }

            $linkinfo    = '';
            $showsection = $thissection->uservisible ||
                ($thissection->visible && !$thissection->available && $thissection->showavailability
                    && !empty($thissection->availableinfo));

            $outputlink = true;
            if (!$showsection) {

                if (!$course->hiddensections && $thissection->available) {
                    // Section is hidden, but show that it is not available.
                    $outputlink = false; // Students don't get links for hidden sections.
                    $linkinfo = $this->toc_linkinfo(get_string('notavailable'));
                } else {
                    continue; // Completely hidden section.
                }
            } else {
                // NOTE: $thissection->uservisible evaluates to true when the section is hidden but the user can
                // view hidden sections but we stil need to let teachers see that the section is not published to
                // students.
                if (!$thissection->visible) {
                    if ($viewhiddensections) {
                        $linkinfo = $this->toc_linkinfo(get_string('notpublished', 'theme_snap'));
                    } else {
                        $outputlink = false; // Students don't get links for hidden sections.
                        $linkinfo = $this->toc_linkinfo(get_string('notavailable'));
                    }
                } else if (!$thissection->available) {
                    $linkinfo = $this->toc_linkinfo(get_string('conditional', 'theme_snap'));
                }
            }

            $sectionstring = get_section_name($course, $section);
            $sectionclass = '';
            $highlight = '';
            if (course_get_format($course)->is_section_current($section)) {
                $sectionclass = 'current';
                $highlight = ' <small class=highlight-tag>'.get_string('current', 'theme_snap').'</small>';
            }

            if ($outputlink) {
                if ($singlepage) {
                    $url = '#section-'.$section;
                } else {
                    if ($section > 0) {
                        $url = course_get_url($course, $section, array('navigation' => true, 'sr' => $section));
                    } else {
                        // We need to create the url for section 0, or a hash will get returned.
                        $url = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $section));
                    }
                }
                $link = html_writer::link($url, $sectionstring, array('role' => 'menuitem', 'class' => $sectionclass));
            } else {
                $link = html_writer::tag('span', $sectionstring, array('class' => $sectionclass));
            }
            $progress = $this->toc_progress ($thissection, $course);

            $li   = '<li>'.$link.$highlight.$progress.' '.$linkinfo.'</li>';
            $toc .= $li;
        }

        $toc .= "</ol>";
        $toc .= $this->appendices();
        $toc .= "</nav>";
        $o .= $toc;
        return $o;
    }


    /**
     * generate appendices string
     *
     * @author Guy Thomas
     * @date 2014-04-23
     * @return string
     */
    protected function appendices() {
        global $CFG, $COURSE;

        $links = array();
        $localplugins = core_component::get_plugin_list('local');
        $coursecontext = context_course::instance($COURSE->id);

        // Gradebook.
        $links[] = array(
            'link' => 'grade/index.php?id='.$COURSE->id,
            'title' => get_string('gradebook', 'grades')
        );

        // Only show if joule grader is installed.
        if (array_key_exists('joulegrader', $localplugins)) {
            $links[] = array(
                'link' => 'local/joulegrader/view.php?courseid='.$COURSE->id,
                'title' => get_string('pluginname', 'local_joulegrader')
            );
        }

        // Only show Norton grader if installed.
        if (array_key_exists('nortongrader', $localplugins)) {
            $links[] = array(
                'link' => $CFG->wwwroot.'/local/nortongrader/view.php?courseid='.$COURSE->id,
                'title' => get_string('pluginname', 'local_nortongrader')
            );
        }

        // Only show core outcomes if enabled.
        if (!empty($CFG->core_outcome_enable) && has_capability('moodle/grade:edit', $coursecontext)) {
            $links[] = array(
                'link'  => 'outcome/course.php?contextid='.$coursecontext->id,
                'title' => get_string('outcomes', 'outcome'),
            );
        } else if (!empty($CFG->core_outcome_enable) && !is_guest($coursecontext)) {
            $outcomesets = new \core_outcome\model\outcome_set_repository();
            if ($outcomesets->course_has_any_outcome_sets($COURSE->id)) {
                $links[] = array(
                    'link'  => 'outcome/course.php?contextid='.$coursecontext->id.'&action=report_course_user_performance_table',
                    'title' => get_string('report:course_user_performance_table', 'outcome'),
                );
            }
        }

        // Course badges.
        $links[] = array(
            // What is the 'type=2' bit ?? I'm not happy with this hardcoded but I don't know where it gets the type
            // from yet.
            'link' => 'badges/view.php?type=2&id='.$COURSE->id,
            'title' => get_string('badgesview', 'badges'),
            'capability' => '!moodle/course:update', // You must not have this capability to view this item.
        );

        // Personalised Learning Designer.
        $links[] = array(
            'link' => 'local/pld/view.php?courseid='.$COURSE->id,
            'title' => get_string('pluginname', 'local_pld'),
            'capability' => 'moodle/course:update', // Capability required to view this item.
        );

        // Only show Joule reports if installed.
        if (array_key_exists('reports', core_component::get_plugin_list('block'))) {
            $links[] = array(
                'link' => $CFG->wwwroot.'/blocks/reports/view.php?action=dashboard&courseid='.$COURSE->id,
                'title' => get_string('joulereports', 'block_reports')
            );
        }

        // Participants.
        $links[] = array(
            'link' => 'user/index.php?id='.$COURSE->id.'&mode=1',
            'title' => get_string('participants')
        );

        // Manage badges.
        $links[] = array(
            'link' => 'badges/index.php?type=2&id='.$COURSE->id,
            'title' => get_string('managebadges', 'badges'),
            'capability' => 'moodle/course:update', // Capability required to view this item.
        );

        // Output appendices.
        $o = html_writer::start_tag('ul', array('role' => 'menu', 'id' => 'appendices', 'class' => 'list-unstyled'));
        $o .= $this->render_appendices($links);
        $o .= html_writer::end_tag('ul');
        return ($o);
    }


    /**
     * generates a string list of links based on links array
     * structure of links array should be
     * array(
     *      array(
     *          'link'=>[url in a string]
     *          'title'=>[mandatory - anyold string title],
     *          'capability'=>[if you want to limit who can see link],
     *      )
     * )
     * note - couldn't use html_writer::alist function as it does not support sub lists
     *
     * @author Guy Thomas
     * @param array $links
     * @return string;
     */
    protected function render_appendices(array $links) {
        global $CFG, $COURSE;

        $o = '';

        $coursecontext = context_course::instance($COURSE->id);

        if (empty($links)) {
            return;
        }
        foreach ($links as $item) {

            $item = (object) $item;
            $subtree = '';

            // Check if user has appropriate access to see this item.
            if (!empty($item->capability)) {
                if (strpos($item->capability, '!') !== 0) {
                    if (!has_capability($item->capability, $coursecontext)) {
                        // Skip item - required capability not present.
                        continue;
                    }
                } else {
                    if (has_capability(substr($item->capability, 1), $coursecontext)) {
                        // Skip item - not appropriate for people with this capability.
                        continue;
                    }
                }
            }

            // Make sure item link is the correct type of url.
            if (stripos($item->link, 'http') !== 0) {
                $item->link = $CFG->wwwroot.'/'.$item->link;
            }

            // Generate linkhtml and add it to treestr.
            $linkhtml = '';
            if (!empty($item->link)) {
                $linkhtml = html_writer::link($item->link, $item->title);
            } else {
                $linkhtml = html_writer::tag('span', $item->title);
            }
            $o .= html_writer::tag('li', $linkhtml.$subtree);
        }
        return ($o);
    }


    /**
     * provide search function for all modules on page
     *
     * @author Guy Thomas
     * @date 2014-04-24
     * @return string
     */
    protected function modulesearch() {
        global $CFG, $COURSE;

        $format  = course_get_format($this->page->course);
        $course  = $format->get_course();
        $singlepage = (!property_exists($course, 'coursedisplay') || $course->coursedisplay == COURSE_DISPLAY_SINGLEPAGE);

        $o = '<div id="toc-search-results"></div>';
        $o .= '<div id="toc-searchables">';

        // If course does not have any sections then exit - it can't be a course without sections!!!
        if (!isset($course->numsections)) {
            return;
        }

        $modinfo = get_fast_modinfo($course);

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname == 'label') {
                continue;
            }
            if ($cm->sectionnum > $course->numsections) {
                continue; // Module outside of number of sections.
            }
            if (!$cm->uservisible && (empty($cm->showavailability) || empty($cm->availableinfo))) {
                continue; // Hidden completely.
            }
            $pubstat = '';
            if (!$cm->uservisible) {
                $pubstat = '<span class="linkinfo">'.get_string('notpublished', 'theme_snap').'</span>';
            }

            // Additional module information.
            $info = '';
            if ($cm->modname !== 'resource') {
                $info = '<span class="sr-only">'.get_string('pluginname', $cm->modname).'</span> ';
            }

            // Create image.
            $img = html_writer::tag('img', '', array('src' => $cm->get_icon_url()));

            // Create link.
            if ($singlepage && $COURSE->format != 'folderview') {
                $url = '#section-'.$cm->sectionnum.'&modid-'.$cm->id;
            } else {

                $url = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'&section='.$cm->sectionnum.'#modid-'.$cm->id;
            }
            $link = html_writer::link($url, $img.' '.$info.' '.$cm->get_formatted_name());
            $o .= $link;
        }
        $o .= '</div>';

        return ($o);
    }

    /**
     * get course image
     *
     * @return bool|moodle_url
     */
    public function get_course_image() {
        global $COURSE;

        return \theme_snap\local::get_course_image($COURSE->id);
    }
}
