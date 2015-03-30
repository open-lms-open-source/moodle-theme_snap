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
 * Renderer functions shared between multiple renderers.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class snap_shared extends renderer_base {

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
        $o .= "<div class='section-summary-activities mdl-right'>";
        foreach ($sectionmods as $mod) {
            $o .= "<span class='activity-count'>";
            $o .= $mod['name'].': '.$mod['count'];
            $o .= "</span>";
        }
        $o .= "</div>";

        $a = false;

        // Output section completion data.
        if ($total > 0) {
            $a = new stdClass;
            $a->complete = $complete;
            $a->total = $total;
            $a->percentage = ($complete / $total) * 100;

            $o .= "<div class='section-summary-activities mdl-right'>";
            $o .= "<span class='activity-count'>".get_string('progresstotal', 'completion', $a)."</span>";
            $o .= "</div>";
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

        $PAGE->requires->js_init_call('M.theme_snap.core.init', array($COURSE->id, $PAGE->context->id), false, $module);
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
        return ($OUTPUT->notification(get_string('warnsiteformatflexpage',
            'theme_snap', $CFG->wwwroot.'/admin/settings.php?section=frontpagesettings')));
    }

    /**
     * Is the gradebook accessible - i.e. are there any reports accessible to this user
     * @return bool
     */
    public static function gradebook_accessible($context) {

        // Find all enabled reports.
        $reports = core_component::get_plugin_list('gradereport');
        foreach (array_keys($reports) as $report) {
            if (!component_callback('gradereport_'.$report, 'is_enabled', array(), true)) {
                unset($reports[$report]);
            }
        }

        // Reduce reports list down to just those accessible to user.
        foreach ($reports as $plugin => $plugindir) {
            // Remove ones we can't see.
            if (!has_capability('gradereport/'.$plugin.':view', $context)) {
                unset($reports[$plugin]);
            }
        }
        return !empty($reports);
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
    public static function render_appendices(array $links) {
        global $CFG, $COURSE;

        $coursecontext = context_course::instance($COURSE->id);

        $o = '';
        foreach ($links as $item) {

            $item = (object) $item;

            // Check if user has appropriate access to see this item.
            if (!empty($item->capability)) {
                if (strpos($item->capability, '!') !== 0) {
                    if (!has_capability($item->capability, $coursecontext)) {
                        continue;
                    }
                } else {
                    if (has_capability(substr($item->capability, 1), $coursecontext)) {
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
                $linkhtml = "<span>$item->title</span>";
            }
            $o .= $linkhtml;
        }
        return $o;
    }
    /**
     * Course tools svg icons
     *
     * @return string
     */
    public static function coursetools_svg_icons() {
        return self::inline_svg('coursetools-icons.svg');
    }

    /**
     * Get inline svg icon.
     * @param $filename
     * @return string
     */
    public static function inline_svg($filename) {
        global $CFG;
        return file_get_contents(dirname(dirname(__DIR__)).'/snap/pix/'.$filename);
    }

    /**
     * generate list of course tools
     *
     * @author Guy Thomas
     * @date 2014-04-23
     * @return string
     */
    public static function appendices() {
        global $CFG, $COURSE, $PAGE, $OUTPUT;

        $links = array();
        $localplugins = core_component::get_plugin_list('local');
        $coursecontext = context_course::instance($COURSE->id);

        // Turn editing on.
        $iconsrc = $OUTPUT->pix_url('icon', 'label');
        $editcourseicon = '<img class="svg-icon" alt="" title="" src="'.$iconsrc.'">';
        $url = new moodle_url('/course/view.php', ['id' => $COURSE->id, 'sesskey' => sesskey()]);
        if ($PAGE->user_is_editing()) {
            $url->param('edit', 'off');
            $editstring = get_string('turneditingoff');
        } else {
            $url->param('edit', 'on');
            $editstring = get_string('editcoursecontent', 'theme_snap');
        }
        $links[] = array(
            'link' => $url,
            'title' => $editcourseicon.$editstring,
            'capability' => 'moodle/course:update' // Capability required to view this item.
        );

        // Course settings.
        $settingsicon = '<svg viewBox="0 0 100 100" class="svg-icon">
        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-settings"></use></svg>';
        $links[] = array(
            'link' => 'course/edit.php?id='.$COURSE->id,
            'title' => $settingsicon.get_string('editcoursesettings', 'theme_snap'),
            'capability' => 'moodle/course:update' // Capability required to view this item.
        );

        // Participants.
        $participanticon = '<svg viewBox="0 0 100 100" class="svg-icon">
        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-participants"></use></svg>';
        $links[] = array(
            'link' => 'user/index.php?id='.$COURSE->id.'&mode=1',
            'title' => $participanticon.get_string('participants'),
            'capability' => 'moodle/course:viewparticipants' // Capability required to view this item.
        );
        // Gradebook.
        $gradebookicon = '<svg viewBox="0 0 100 100" class="svg-icon">
        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-gradbook"></use></svg>';

        if (self::gradebook_accessible($coursecontext)) {
            // Gradebook.
            $links[] = array(
                'link' => 'grade/index.php?id='.$COURSE->id,
                'title' => $gradebookicon.get_string('gradebook', 'grades')
            );
        }

        // Only show if joule grader is installed.
        if (array_key_exists('joulegrader', $localplugins)) {
            if (has_capability('local/joulegrader:grade', $coursecontext)
                || has_capability('local/joulegrader:view', $coursecontext)
            ) {
                $links[] = array(
                    'link' => 'local/joulegrader/view.php?courseid='.$COURSE->id,
                    'title' => $gradebookicon.get_string('pluginname', 'local_joulegrader'),
                );
            }
        }

        // Only show Norton grader if installed.
        if (array_key_exists('nortongrader', $localplugins)) {
            if (has_capability('local/nortongrader:grade', $coursecontext)
                || has_capability('local/nortongrader:view', $coursecontext)
            ) {
                $links[] = array(
                    'link' => $CFG->wwwroot.'/local/nortongrader/view.php?courseid='.$COURSE->id,
                    'title' => $gradebookicon.get_string('pluginname', 'local_nortongrader')
                );
            }
        }

        // Only show core outcomes if enabled.
        $outcomesicon = '<svg viewBox="0 0 100 100" class="svg-icon">
        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-outcomes"></use></svg>';
        if (!empty($CFG->core_outcome_enable) && has_capability('moodle/grade:edit', $coursecontext)) {
            $links[] = array(
                'link'  => 'outcome/course.php?contextid='.$coursecontext->id,
                'title' => $outcomesicon.get_string('outcomes', 'outcome'),
            );
        } else if (!empty($CFG->core_outcome_enable) && !is_guest($coursecontext)) {
            $outcomesets = new \core_outcome\model\outcome_set_repository();
            if ($outcomesets->course_has_any_outcome_sets($COURSE->id)) {
                $links[] = array(
                    'link'  => 'outcome/course.php?contextid='.$coursecontext->id.'&action=report_course_user_performance_table',
                    'title' => $participanticon.get_string('report:course_user_performance_table', 'outcome'),
                );
            }
        }

        $badgesicon = '<svg viewBox="0 0 100 100" class="svg-icon">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-badges"></use></svg>';
        // Course badges.
        if (!is_guest($coursecontext)) {
            $links[] = array(
                // What is the 'type=2' bit ?? I'm not happy with this hardcoded but I don't know where it gets the type
                // from yet.
                'link' => 'badges/view.php?type=2&id='.$COURSE->id,
                'title' => $badgesicon.get_string('badgesview', 'badges'),
                'capability' => '!moodle/course:update' // You must not have this capability to view this item.
            );
        }
        // Manage badges.
        $links[] = array(
            'link' => 'badges/index.php?type=2&id='.$COURSE->id,
            'title' => $badgesicon.get_string('managebadges', 'badges'),
            'capability' => 'moodle/course:update' // Capability required to view this item.
        );

        // Only show Joule reports if installed.
        $reportsicon = '<svg viewBox="0 0 100 100" class="svg-icon">
            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-reports"></use></svg>';
        if (array_key_exists('reports', core_component::get_plugin_list('block'))) {
            if (has_capability('block/reports:viewown', $coursecontext, null, false)
                || has_capability('block/reports:view', $coursecontext)
            ) {
                $links[] = array(
                    'link' => $CFG->wwwroot.'/blocks/reports/view.php?action=dashboard&courseid='.$COURSE->id,
                    'title' => $reportsicon.get_string('joulereports', 'block_reports')
                );
            }
        }

        // Personalised Learning Designer.
        $pldicon = '<svg viewBox="0 0 100 100" class="svg-icon">
        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#coursetools-pld"></use></svg>';
        $pldname = get_string('pld', 'theme_snap');
        $links[] = array(
            'link' => 'local/pld/view.php?courseid='.$COURSE->id,
            'title' => $pldicon.$pldname,
            'capability' => 'moodle/course:update', // Capability required to view this item.
        );

        $toolssvg = self::inline_svg('tools.svg');
        // Output course tools.
        $coursetools = get_string('coursetools', 'theme_snap');
        $o = "<h2>$coursetools</h2>";
        $o .= "<div class='row'><div class='col-xs-4'>{$toolssvg}</div><div class='col-xs-8'><div id='coursetools-list'>".
            self::render_appendices($links)."</div></div></div><hr>";

        return $o;
    }


}
