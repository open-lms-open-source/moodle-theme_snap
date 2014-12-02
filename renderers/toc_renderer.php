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
        global $CFG, $OUTPUT;

        require_once($CFG->libdir.'/completionlib.php');

        $completioninfo = new completion_info($course);
        if (!$completioninfo->is_enabled()) {
            return ''; // Completion tracking not enabled.
        }
        $sac = snap_shared::section_activity_summary($section, $course, null);
        if (!empty($sac->progress)) {
            if ($perc) {
                $percentage = $sac->progress->percentage != null ? round($sac->progress->percentage, 0).'%' : '';
                return ('<span class="completionstatus percentage">'.$percentage.'</span>');
            } else {
                if ($sac->progress->total > 0) {
                    $progress = get_string('progresstotal', 'completion', $sac->progress);
                    $completed = '';
                    if ($sac->progress->complete === $sac->progress->total) {
                        $winbadge = $OUTPUT->pix_url('i/completion-manual-y');
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
     * Is a section conditional
     *
     * @author Guy Thomas
     * @param section_info $section
     * @param bool $checkdates
     * @return bool
     */
    protected function is_section_conditional(section_info $section) {
        // Are there any conditional fields populated?
        if (   !empty($section->availableinfo)
            || !empty($section->conditionsgrade)
            || !empty($section->conditionscompletion)
            || !empty($section->conditionsfield)) {
            return true;
        }
        // OK - this isn't conditional.
        return false;
    }


    /**
     * Print  table of contents for a course
     *
     * @Author: Stuart Lamour
     */
    public function print_course_toc() {

        global $COURSE;

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

        $singlepage = true;
        if ($COURSE->format === 'folderview') {
            $singlepage = false;
        }
        $contents = get_string('contents', 'theme_snap');

        $o = '<nav id="course-toc">
        <div>
        <h2 id="toc-desktop-menu-heading">
        <span class=sr-only>Page</span>'.$contents.'</h2>
        <form id="toc-search" onSubmit="return false;">
        <input id="toc-search-input" type="search" title="'.get_string("search").'" placeholder="'.get_string("search").
        '" aria-autocomplete="list" aria-haspopup="true" aria-activedescendant="toc-search-results" autocomplete="off" />
        '.$this->modulesearch().'
        </form>
        <a id="toc-mobile-menu-toggle" title="'.$contents.'" href="#course-toc"><i class="icon icon-office-52"></i></a>
        </div>';

        $listlarge = '';
        if ($course->numsections > 9) {
            $listlarge = "list-large";
        }
        $toc = '<ol id="chapters" class="chapters '.$listlarge.'" start="0">';

        course_create_sections_if_missing($course, range(0, $course->numsections));

        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section > $course->numsections) {
                continue;
            }

            $linkinfo    = '';

            // Is this conditional.
            $conditional = $this->is_section_conditional($thissection);

            // Make sure conditionally restricted section is skipped in toc if we aren't able to view hidden sections
            // and restriction hides section completely.
            if (!$thissection->uservisible && empty($thissection->availableinfo)) {
                continue;
            }

            $showsection = $conditional || ($thissection->uservisible
                || ($thissection->visible && !$thissection->available
                    && !empty($thissection->availableinfo)));

            $outputlink = true;
            if (!$showsection) {
                if (!$course->hiddensections && $thissection->available) {
                    // Section is hidden, but show that it is not available.
                    $outputlink = false; // Students don't get links for hidden sections.
                    $linkinfo = $this->toc_linkinfo(get_string('notavailable'));
                } else {
                    continue; // Completely hidden section.
                }
            } else if ($conditional) {
                // NOTE: $thissection->uservisible evaluates to true when the section is hidden but the user can
                // view hidden sections but we still need to let teachers see that the section is not published to
                // students.
                if (!$thissection->visible) {
                    if ($viewhiddensections) {
                        $linkinfo = $this->toc_linkinfo(get_string('notpublished', 'theme_snap'));
                    } else {
                        $outputlink = false; // Students don't get links for hidden sections.
                        $linkinfo = $this->toc_linkinfo(get_string('notavailable'));
                    }
                } else {
                    if (\theme_snap\local::is_teacher()) {
                        $linkinfo = $this->toc_linkinfo(get_string('conditional', 'theme_snap'));
                    } else if (!empty($thissection->availableinfo)) {
                        $linkinfo = $this->toc_linkinfo(get_string('notavailable'));
                    }
                }
            }

            $sectionstring = get_section_name($course, $section);
            if ($sectionstring == get_string('general')) {
                $sectionstring = get_string('introduction', 'theme_snap');
            }
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

                $link = "<a class='$sectionclass' href='$url'>$sectionstring</a>";

            } else {
                $link = "<span class='$sectionclass' >$sectionstring</a>";
            }

            $progress = $this->toc_progress ($thissection, $course);

            $li   = '<li>'.$link.$highlight.$progress.' '.$linkinfo.'</li>';

            $toc .= $li;
        }
        $coursetools = get_string('coursetools', 'theme_snap');
        $url = "#coursetools";
        if ($COURSE->format == 'folderview') {
            $url = new moodle_url('/course/view.php', ['id' => $course->id, 'section' => 0], 'coursetools');
        }
        $link = html_writer::link($url, $coursetools);
        $toc .= "<li>$link</li>";

        $toc .= "</ol>";
        $toc .= "</nav>";
        $o .= $toc;
        return $o;
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

        // If course does not have any sections then exit - it can't be a course without sections!!!
        if (!isset($course->numsections)) {
            return;
        }

        $o = '<ul id="toc-search-results" class="list-unstyled" role="listbox" aria-label="search results" '.
             'aria-live="polite" aria-relevant="additions"></ul>';
        $o .= '<ul role="listbox" id="toc-searchables" aria-hidden="true">';

        $modinfo = get_fast_modinfo($course);

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname == 'label') {
                continue;
            }
            if ($cm->sectionnum > $course->numsections) {
                continue; // Module outside of number of sections.
            }
            if (!$cm->uservisible && (empty($cm->availableinfo))) {
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

            $img = "<img src='".$cm->get_icon_url()."' alt='' />";

            // #section-1&module-7255.
            $url = '#section-'.$cm->sectionnum.'&module-'.$cm->id;
            if ($COURSE->format == 'folderview') {
                $url = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'&section='.$cm->sectionnum.'#module-'.$cm->id;
            }

            $linkcontent = $img.$info.$cm->get_formatted_name().$pubstat;
            $link = html_writer::link($url, $linkcontent, ['tabindex' => 0]);
            $o .= "<li role=option>$link</li>";
        }
        $o .= '</ul>';

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
