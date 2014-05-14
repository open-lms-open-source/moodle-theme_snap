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
     * Print  table of contents for a course
     *
     * @Author: Stuart Lamour
     */
    public function print_course_toc() {

        global $COURSE;

        $viewhiddensections = has_capability('moodle/course:viewhiddensections', context_course::instance($COURSE->id));

        $format     = course_get_format($this->page->course);
        $course     = $format->get_course();
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

        if ($singlepage) {
            $search = get_string('search');
            $o .= '
            <input class="pull-right" id="toc-search-input"  type="text" title="'.s($search).'" placeholder="'.s($search).'" />
            '.$this->modulesearch();
        }
        $o .= '</div>';

        $toc = '<ol id="chapters" class="chapters" role="menu" start="0">';

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
                    $linkinfo = '<em class="published-status"><small>'.get_string('notavailable').'</small></em>';
                } else {
                    continue; // Completely hidden section.
                }
            } else {
                // NOTE: $thissection->uservisible evaluates to true when the section is hidden but the user can
                // view hidden sections but we stil need to let teachers see that the section is not published to
                // students.
                if (!$thissection->visible) {
                    if ($viewhiddensections) {
                        $linkinfo = '<em class="published-status"><small>'.get_string('notpublished', 'theme_snap').'</small></em>';
                    } else {
                        $outputlink = false; // Students don't get links for hidden sections.
                        $linkinfo = '<em class="published-status"><small>'.get_string('notavailable').'</small></em>';
                    }
                } else if (!$thissection->available) {
                    if (!$viewhiddensections) {
                        // Note student still gets a link for conditionally unavailable sections so that they can see
                        // conditional criteria by following link.
                        $linkinfo = '<em class="published-status"><small>'.get_string('conditional', 'theme_snap').'</small></em>';
                    }
                }
            }

            if ($outputlink) {
                if ($singlepage) {
                    $url = '#section-'.$section;
                } else {
                    $url = course_get_url($course, $section, array('navigation' => true, 'sr' => $section));
                }
                $link = html_writer::link($url, get_section_name($course, $section), array('role' => 'menuitem'));
            } else {
                $link = get_section_name($course, $section);
            }
            $li   = '<li>'.$link.' '.$linkinfo.'</li>';
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

        $links[] = array(
            'link' => 'user/index.php?id='.$COURSE->id.'&mode=1',
            'title' => get_string('participants')
        );

        // Only show if joule grader enabled.
        if ($this->joulegraderenabled()) {
            $links[] = array(
                'link' => 'local/joulegrader/view.php?courseid='.$COURSE->id,
                'title' => get_string('pluginname', 'local_joulegrader')
            );
        }

        $links[] = array(
            'link' => 'grade/index.php?id='.$COURSE->id,
            'title' => get_string('gradebook', 'grades')
        );

        // Only show outcomes if enabled.
        if (!empty($CFG->enableoutcomes)) {
            $links[] = array(
                'link' => 'outcome/course.php?contextid='.context_course::instance($COURSE->id)->id,
                'title' => get_string('outcomes', 'outcome'),
                'capability' => 'moodle/course:update' // Capability required to view this item.
            );
        }

        $links[] = array(
            // What is the 'type=2' bit ?? I'm not happy with this hardcoded but I don't know where it gets the type
            // from yet.
            'link' => 'badges/view.php?type=2&id='.$COURSE->id,
            'title' => get_string('badgesview', 'badges')
        );

        $links[] = array(
            'link' => 'badges/index.php?type=2&id='.$COURSE->id,
            'title' => get_string('managebadges', 'badges'),
            'capability' => 'moodle/course:update', // Capability required to view this item.
        );

        $links[] = array(
            'link' => 'badges/newbadge.php?type=2&id='.$COURSE->id,
            'title' => get_string('newbadge', 'badges'),
            'capability' => 'moodle/course:update', // Capability required to view this item.
        );

        $links[] = array(
            'link' => 'local/pld/view.php?courseid='.$COURSE->id,
            'title' => get_string('pluginname', 'local_pld'),
            'capability' => 'moodle/course:update', // Capability required to view this item.
        );

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
                if (!has_capability($item->capability, $coursecontext)) {
                    // Skip item - required capability not present.
                    continue;
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
        $o = '<div id="toc-search-results"></div>';
        $o .= '<div id="toc-searchables">';
        $format  = course_get_format($this->page->course);
        $course  = $format->get_course();
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
            $url = '#section-'.$cm->sectionnum.'&modid-'.$cm->id;
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

        $fs      = get_file_storage();
        $context = context_course::instance($COURSE->id);
        $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);

        if (count($files) > 0) {
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    return moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        false,
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                }
            }
        }
        return false;
    }


    /**
     * is joule grader enabled?
     * @return bool
     */
    protected function joulegraderenabled() {
        return (is_callable('mr_on') && mr_on('joulegrader', 'local'));
    }

}
