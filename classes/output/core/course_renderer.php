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
 * Snap course renderer.
 * Overrides core course renderer.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output\core;

defined('MOODLE_INTERNAL') || die();

use cm_info;
use context_course;
use context_module;
use \core\url as moodle_url;
use coursecat;
use stdClass;
use theme_snap\activity;
use theme_snap\activity_meta;
use core\output\local\dropdown\dialog as dropdown_dialog;
use core_completion\cm_completion_details;

require_once($CFG->dirroot . "/mod/book/locallib.php");
require_once($CFG->libdir . "/gradelib.php");
require_once($CFG->dirroot . '/course/renderer.php');
require_once("$CFG->libdir/resourcelib.php");

class course_renderer extends \core_course_renderer {

    /**
     * Output frontpage summary text and frontpage modules (stored as section 1 in site course)
     *
     * This may be disabled in settings
     * Copied from course/renderer.php. Exactly the same just to change frontpage to use our site_render.
     *
     */
    public function frontpage_section1() {
        global $SITE, $USER;

        $output = '';
        $editingmode = $this->page->user_is_editing();

        // Simulate editing On for rendering controlmenu.
        $USER->editing = true;

        if ($editingmode) {
            // Make sure section with number 1 exists.
            course_create_sections_if_missing($SITE, 1);
        }

        $modinfo = get_fast_modinfo($SITE);
        $section = $modinfo->get_section_info(1);

        if (($section && (!empty($modinfo->sections[1]) or !empty($section->summary)))) {

            $format = course_get_format($SITE);

            $frontpageclass = $format->get_output_classname('content\\frontpagesection');
            $frontpagesection = new $frontpageclass($format, $section);

            // Use Snap site render instead of core one.
            $renderer = new \theme_snap\output\site_renderer($this->page, null);

            $output .= $renderer->render($frontpagesection);
        }
        $USER->editing = $editingmode;

        return $output;
    }

    /**
     * Renders HTML to show course module availability information
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function snap_course_section_cm_availability(cm_info $mod, $displayoptions = []) {
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $mod->context);
        // If the module isn't available, or we are a teacher (can view hidden activities) then get availability
        // info. Restrictions will appear on click over a lock image inside the activity header.
        $coursetoolsicon = '';
        if (!$mod->available || $canviewhidden) {
            $availabilityinfo = $this->course_section_cm_availability($mod, []);
            if ($availabilityinfo) {
                $ariaconditionaltag = get_string('activityrestriction', 'theme_snap');
                $conditionaltagsrc = $this->output->image_url('lock', 'theme');
                $datamodcontext = $mod->context->id;
                $conditionaliconid = "snap-restriction-$datamodcontext";
                $restrictionsource = \core\output\html_writer::tag('img', '', [
                    'class' => 'svg-icon',
                    'title' => $ariaconditionaltag,
                    'aria-hidden' => 'true',
                    'src' => $conditionaltagsrc,
                ]);
                $coursetoolsicon = \core\output\html_writer::tag('a', $restrictionsource, [
                    'tabindex' => '0',
                    'class' => 'snap-conditional-tag',
                    'role' => 'button',
                    'data-toggle' => 'popover',
                    'data-trigger' => 'focus',
                    'data-placement' => 'right',
                    'id' => $conditionaliconid,
                    'data-html' => 'true',
                    'clickable' => 'true',
                    'data-content' => $availabilityinfo,
                    'aria-label' => $ariaconditionaltag,
                ]);
            }
        }
        return $coursetoolsicon;
    }

    /**
     * Renders HTML to show course module availability information
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     * @deprecated since 4.0, use core_courseformat\\output\\local\\content\\cm\\availability instead
     */
    public function course_section_cm_availability(cm_info $mod, $displayoptions = []) {
        // If we have available info, always spit it out.
        if (!$mod->uservisible && !empty($mod->availableinfo)) {
            $availinfo = $mod->availableinfo;
        } else {
            $ci = new \core_availability\info_module($mod);
            $availinfo = $ci->get_full_information();
        }

        if ($availinfo) {
            $formattedinfo = \core_availability\info::format_info(
                $availinfo, $mod->get_course());
            return $formattedinfo;
        }

        return '';
    }

    /**
     * Renders HTML for completion tracking box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param \completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     * @throws \dml_exception
     * @deprecated since 4.0, Use the activity_completion output component instead.
     */
    public function snap_course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = []) {
        global $CFG, $USER, $DB;

        $output = '';

        $istrackeduser = $completioninfo->is_tracked_user($USER->id);
        $isediting = $this->page->user_is_editing();

        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return $output;
        }
        if ($completioninfo === null) {
            $completioninfo = new \completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);

        if ($completion == COMPLETION_TRACKING_NONE) {
            if ($isediting) {
                $output .= \core\output\html_writer::span('&nbsp;', 'filler');
            }
            return $output;
        }

        $completionicon = '';

        if ($isediting || !$istrackeduser) {
            switch ($completion) {
                case COMPLETION_TRACKING_MANUAL :
                    $completionicon = 'manual-enabled';
                    break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    $completionicon = 'auto-enabled';
                    break;
            }
        } else {
            $completiondata = $completioninfo->get_data($mod, true);
            if ($completion == COMPLETION_TRACKING_MANUAL) {
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'manual-n' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'manual-y' . ($completiondata->overrideby ? '-override' : '');
                        break;
                }
            } else { // Automatic completion.
                switch($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE:
                        $completionicon = 'auto-n' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE:
                        $completionicon = 'auto-y' . ($completiondata->overrideby ? '-override' : '');
                        break;
                    case COMPLETION_COMPLETE_PASS:
                        $completionicon = 'auto-pass';
                        break;
                    case COMPLETION_COMPLETE_FAIL:
                        $completionicon = 'auto-fail';
                        break;
                }
            }
        }
        if ($completionicon) {
            $formattedname = html_entity_decode($mod->get_formatted_name(), ENT_QUOTES, 'UTF-8');
            if (!$isediting && $istrackeduser && $completiondata->overrideby) {
                $args = new stdClass();
                $args->modname = $formattedname;
                $overridebyuser = \core_user::get_user($completiondata->overrideby, '*', MUST_EXIST);
                $args->overrideuser = fullname($overridebyuser);
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $args);
            } else {
                $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);
            }

            if ($isediting || !$istrackeduser || !has_capability('moodle/course:togglecompletion', $mod->context)) {
                // When editing, the icon is just an image.
                $completionpixicon = new \core\output\pix_icon('i/completion-'.$completionicon, $imgalt, '',
                    ['class' => 'iconsmall', 'id' => 'completion-button-' . $mod->id]);
                $output .= \core\output\html_writer::tag('span', $this->output->render($completionpixicon),
                    ['class' => 'autocompletion']);
            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                $newstate =
                    $completiondata->completionstate == COMPLETION_COMPLETE
                        ? COMPLETION_INCOMPLETE
                        : COMPLETION_COMPLETE;
                // In manual mode the icon is a toggle form...

                // If this completion state is used by the
                // conditional activities system, we need to turn
                // off the JS.
                $extraclass = '';
                if (!empty($CFG->enableavailability) &&
                    \core_availability\info::completion_value_used($course, $mod->id)) {
                    $extraclass = ' preventjs';

                }

                $output .= \core\output\html_writer::start_tag('form', ['method' => 'post',
                    'action' => new moodle_url('/course/togglecompletion.php'),
                    'class' => 'togglecompletion', ]);
                $output .= \core\output\html_writer::start_tag('div');
                $output .= \core\output\html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'id', 'value' => $mod->id, ]);
                $output .= \core\output\html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(), ]);
                $output .= \core\output\html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'modulename', 'value' => $formattedname, ]);
                $output .= \core\output\html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate, ]);
                $output .= \core\output\html_writer::tag('button',
                    $this->output->pix_icon('i/completion-' . $completionicon, $imgalt,'', ['title' => '']),
                    ['class' => 'btn btn-link', 'id' => 'completion-button-' . $mod->id]);
                $output .= \core\output\html_writer::end_tag('div');
                $output .= \core\output\html_writer::end_tag('form');
            } else {
                // In auto mode, the icon is just an image.
                $showcompletionconditions = $course->showcompletionconditions == COMPLETION_SHOW_CONDITIONS;
                $completiondetails = cm_completion_details::get_instance($mod, $USER->id, $showcompletionconditions);
                $showcompletioninfo = $completiondetails->has_completion() &&
                ($showcompletionconditions || $completiondetails->show_manual_completion());
                if (!$showcompletioninfo) {
                    return $output;
                }
                $completionpixicon = new \core\output\pix_icon('i/completion-'.$completionicon, $imgalt, '', ['id' => 'completion-button-' . $mod->id]);
                $span = \core\output\html_writer::tag('span', $this->output->render($completionpixicon),
                    ['class' => 'autocompletion']);
                $data = (object) [
                    'istrackeduser' => true,
                    'hasconditions' => true,
                    'completiondetails' => $completiondetails
                ];
                $dialogcontent = $this->output->render_from_template('core_courseformat/local/content/cm/completion_dialog', $data);
                $dialog = new dropdown_dialog(
                    $this->output->render($completionpixicon),
                    $this->get_completion_dialog_content($mod),
                    [
                        'classes' => 'completion-dropdown',
                        'buttonclasses' => 'btn btn-icon',
                        'dropdownposition' => dropdown_dialog::POSITION['end'],
                    ]
                );
                $dialog = $this->output->render($dialog);
                $output .= $dialog;
            }
        }
        return $output;
    }

    /*
    ***** SNAP SPECIFIC DISPLAY OF RESOURCES *******
    */

    /**
     * Is this an image module
     * @param cm_info $mod
     * @return bool
     */
    public function is_image_mod(cm_info $mod) {
        if ($mod->modname == 'resource') {
            $fs = get_file_storage();
            $files = $fs->get_area_files($mod->context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
            $mainfile = $files ? reset($files) : null;
            if (file_extension_in_typegroup($mainfile->get_filename(), 'web_image')) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Submission call to action.
     *
     * @param cm_info $mod
     * @param activity_meta $meta
     * @return string
     * @throws \core\exception\coding_exception
     */
    public static function submission_cta(cm_info $mod, activity_meta $meta) {
        global $CFG;

        if (empty($meta->submissionnotrequired)) {
            $url = $CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id;

            if ($meta->submitted) {
                if (empty($meta->timesubmitted)) {
                    $submittedonstr = '';
                } else {
                    $submittedonstr = ' '.userdate($meta->timesubmitted, get_string('strftimedate', 'langconfig'));
                }
                $message = $meta->submittedstr.$submittedonstr;
            } else {
                $warningstr = $meta->draft ? $meta->draftstr : $meta->notsubmittedstr;
                $warningstr = $meta->reopened ? $meta->reopenedstr : $warningstr;
                $message = $warningstr;
            }
            return \core\output\html_writer::link($url, $message);
        }
        return '';
    }

    /**
     * Get the module meta data for a specific module.
     *
     * @param cm_info $mod
     * @return string
     */
    public function module_meta_html(cm_info $mod) {
        global $COURSE;

        $content = '';

        if (is_guest(context_course::instance($COURSE->id))) {
            return '';
        }

        // Do we have an activity function for this module for returning meta data?
        // @todo - check module lib.php for a meta function (won't work for core mods but will for ours if we wish).
        $meta = activity::module_meta($mod);
        if (!$meta->is_set(true)) {
            // Can't get meta data for this module.
            return '';
        }

        if ($meta->isteacher) {
            // Teacher - useful teacher meta data.
            $engagementmeta = [];

            // Below, !== false means we get 0 out of x submissions.
            if (!$meta->submissionnotrequired && $meta->numsubmissions !== false) {
                $engagementmeta[] = get_string('xofy'.$meta->submitstrkey, 'theme_snap',
                    (object) [
                        'completed' => $meta->numsubmissions,
                        'participants' => \theme_snap\local::course_participant_count($COURSE->id, $mod->modname),
                    ]
                );
            }

            if ($meta->numrequiregrading) {
                $engagementmeta[] = get_string('xungraded', 'theme_snap', $meta->numrequiregrading);
            }
            if (!empty($engagementmeta)) {
                $engagementstr = implode(', ', $engagementmeta);

                $params = [
                    'action' => 'grading',
                    'id' => $mod->id,
                    'tsort' => 'timesubmitted',
                    'filter' => 'require_grading',
                ];
                $url = new moodle_url("/mod/{$mod->modname}/view.php", $params);

                $link = \core\output\html_writer::link($url, $engagementstr);
                $content .= \core\output\html_writer::tag('p', $link);
            }
            $suspended = \theme_snap\local::suspended_participant_count($COURSE->id, $mod->id);
            if ($suspended) {
                $content .= \core\output\html_writer::tag('p', get_string("quizattemptswarn", "theme_snap"));
            }

        } else {
            // Feedback meta.
            if (!empty($meta->grade)) {
                // Note - the link that a module takes you to would be better off defined by a function in
                // theme/snap/activity - for now its just hard coded.
                $url = new moodle_url('/grade/report/user/index.php', ['id' => $COURSE->id]);
                if (in_array($mod->modname, ['quiz', 'assign'])) {
                    $url = new moodle_url('/mod/'.$mod->modname.'/view.php?id='.$mod->id);
                }
                $feedbackavailable = get_string('feedbackavailable', 'theme_snap');
                if ($mod->modname != 'lesson') {
                    $content .= \core\output\html_writer::link($url, $feedbackavailable);
                }
            }

            // @codingStandardsIgnoreLine
            /* @var cm_info $mod */
            $content .= self::submission_cta($mod, $meta);
        }

        $modstoshowactivityopendate = ['data', 'quiz', 'scorm', 'workshop', 'assign'];

        // Activity open date.
         if (!empty($meta->timeopen) && in_array($mod->modname, $modstoshowactivityopendate)) {
            $dateformat = get_string('strftimedate', 'langconfig');
            $url = new moodle_url("/mod/{$mod->modname}/view.php", ['id' => $mod->id]);
            $pastopen = $meta->timeopen < time();
            $labeltext = $pastopen ? get_string('opened', 'theme_snap', userdate($meta->timeopen, $dateformat)) :
                get_string('opens', 'theme_snap', userdate($meta->timeopen, $dateformat));
            $dateclass = $pastopen ? 'snap-opened-date' : 'snap-open-date';
            $content .= \core\output\html_writer::link($url, $labeltext,
                [
                    'class' => 'tag tag-success ' . $dateclass,
                    'data-from-cache' => $meta->timesfromcache ? 1 : 0,
                ]);
        }

        // Activity due date.
        if (!empty($meta->extension) || !empty($meta->timeclose)) {
            $dateformat = get_string('strftimedate', 'langconfig');
            if (!empty($meta->extension)) {
                $field = 'extension';
            } else if (!empty($meta->timeclose)) {
                $field = 'timeclose';
            }
            $labeltext = get_string('due', 'theme_snap', userdate($meta->$field, $dateformat));
            $pastdue = $meta->$field < time();
            $url = new moodle_url("/mod/{$mod->modname}/view.php", ['id' => $mod->id]);
            $dateclass = $pastdue ? 'tag-danger' : 'tag-warning';
            $content .= \core\output\html_writer::link($url, $labeltext,
                    [
                        'class' => 'snap-due-date tag '.$dateclass,
                        'data-from-cache' => $meta->timesfromcache ? 1 : 0,
                    ]);
        }

        return $content;
    }


    /**
     * Get resource module image html
     *
     * @param stdClass $mod
     * @return string
     */
    public function mod_image_html($mod) {
        if (!$mod->uservisible) {
                return "";
        }

        $fs = get_file_storage();
        $context = \context_module::instance($mod->id);
        // TODO: this is not very efficient!!
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
        if (count($files) > 0) {
            foreach ($files as $file) {
                $imgsrc = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename(),
                );
            }
        }

        $summary = '';
        $summary = $mod->get_formatted_content(['overflowdiv' => false, 'noclean' => true]);
        $modname = format_string($mod->name);
        $img = format_text('<img src="' .$imgsrc. '" alt="' .$modname. '"/>');
        $icon = '<img title="' .get_string('vieworiginalimage', 'theme_snap'). '"
                alt="' .get_string('vieworiginalimage', 'theme_snap'). '"
                src="' .$this->output->image_url('arrow-expand', 'theme'). '">';
        $imglink = '<a class="snap-expand-link" href="' .$imgsrc. '" target="_blank">' .$icon. '</a>';

        $output = '<figure class="snap-resource-figure figure">'
                    .$img.$imglink.
                    '<figcaption class="snap-resource-figure-caption figure-caption">'
                        .$modname.$summary.
                    '</figcaption>
                </figure>';

        return $output;
    }

    /**
     * Get page module html
     * @param cm_info $mod
     * @return string
     */
    public function mod_page_html(cm_info $mod) {
        if (!$mod->uservisible) {
            return "";
        }

        $page = \theme_snap\local::get_page_mod($mod);

        $preview = $page->summary;

        $showexpandicon = true;
        if (!$page->intro) {
            $preview = shorten_text($page->content, 200);
            if ($preview == $page->content) {
                $showexpandicon = false;
            }
        }

        $readmore = get_string('readmore', 'theme_snap');
        $close = get_string('collapseicon', 'theme_snap');
        $expand = get_string('expandicon', 'theme_snap');

        $content = '';
        $contentloaded = 0;
        if (empty(get_config('theme_snap', 'lazyload_mod_page'))) {
            // Identify content elements which should force an AJAX lazy load.
            $elcontentblist = ['iframe', 'video', 'object', 'embed', 'model-viewer'];
            $content = $page->content;
            $lazyload = false;
            foreach ($elcontentblist as $el) {
                if (stripos($content, '<'.$el) !== false) {
                    $content = ''; // Don't include the content as it is likely to slow the page load down considerably.
                    $lazyload = true;
                }
            }
            $contentloaded = !$lazyload ? 1 : 0;
        }
        // With previous design, we allow displaying videos.
        if ($content == '') {
            if (stripos($page->content, '<video') !== false) {
                $content = $page->content;
                $contentloaded = 1;
            }
        }

        $pmcontextattribute = 'data-pagemodcontext="'.$mod->context->id.'"';
        $expandpagebutton = "
            <button 
                class='btn collapsed pagemod-readmore readmore-button snap-action-icon btn-outline-primary p-2'
                {$pmcontextattribute}
                aria-expanded='false'>
                <i aria-hidden='true' class='icon fa fa-chevron-down fa-fw m-0' title='{$expand} {$page->name}'></i>
            </button>
        ";
        $o = "
        <div class='summary-container'>
            <div class='summary-text'>
                {$preview}
            </div>
        </div>";
        if ($showexpandicon) {
            $o .= "
            <div class='readmore-container d-flex justify-content-center'>
                {$expandpagebutton}
            </div>
            <div class=pagemod-content tabindex='-1' data-content-loaded={$contentloaded}>
                <div id='pagemod-content-container'>
                    {$content}
                </div>
                <div class='d-flex justify-content-center w-100 pt-3'>
                    <button class='snap-action-icon btn btn-outline-primary p-2 d-inline-flex' aria-expanded='true' title='{$close} {$page->name}'>
                        <i aria-hidden='true' class='icon fa fa-chevron-up fa-fw m-0' title='{$expand} {$page->name}'></i>
                    </button>
                </div>
            </div>";
        }
        return $o;
    }

    public function mod_book_html($mod) {
        if (!$mod->uservisible) {
            return "";
        }
        global $DB;

        $cm = get_coursemodule_from_id('book', $mod->id, 0, false, MUST_EXIST);
        $book = $DB->get_record('book', ['id' => $cm->instance], '*', MUST_EXIST);
        $chapters = book_preload_chapters($book);

        if ($book->intro) {
            $context = context_module::instance($mod->id);
            $content = file_rewrite_pluginfile_urls($book->intro, 'pluginfile.php', $context->id, 'mod_book', 'intro', null);
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $formatoptions->overflowdiv = true;
            $formatoptions->context = $context;
            $content = format_text($content, $book->introformat, $formatoptions);
            $o = '<div class="summary-text row">';
            $o .= '<div class="content-row col-sm-6">' .$content. '</div>';
            $o .= '<div class="chapters-row col-sm-6">' .$this->book_get_toc($chapters, $book, $cm) . '</div>';
            $o .= '</div>';
            return $o;
        }
        return $this->book_get_toc($chapters, $book, $cm);
    }

    /**
     * Simplified book toc Get assignment module html (includes meta data);
     *
     * Based on the function of same name in mod/book/localib.php
     * @param $mod
     * @return string
     */
    public function book_get_toc($chapters, $book, $cm) {
        $context = context_module::instance($cm->id);

        switch ($book->numbering) {
            case BOOK_NUM_BULLETS :
                $numclass = 'list-bullets';
                break;
            case BOOK_NUM_INDENTED:
                $numclass = 'list-indented';
                break;
            case BOOK_NUM_NONE:
                $numclass = 'list-none';
                break;
            case BOOK_NUM_NUMBERS :
            default :
                $numclass = 'list-numbers';
        }

        $toc = "<h4>".get_string('chapters', 'theme_snap')."</h4>";
        $toc .= '<ol class="bookmod-chapters '.$numclass.'">';
        $closemeflag = false; // Control for indented lists.
        $chapterlist = '';
        foreach ($chapters as $ch) {
            $title = trim(format_string($ch->title, true, ['context' => $context]));
            if (!$ch->hidden) {
                if ($closemeflag && !$ch->parent) {
                    $chapterlist .= "</ul></li>";
                    $closemeflag = false;
                }
                $chapterlist .= "<li>";
                $chapterlist .= \core\output\html_writer::link(new moodle_url('/mod/book/view.php',
                    ['id' => $cm->id, 'chapterid' => $ch->id]), $title, []);
                if ($ch->subchapters) {
                    $chapterlist .= "<ul>";
                    $closemeflag = true;
                } else {
                    $chapterlist .= "</li>";
                }
            }
        }
        $toc .= $chapterlist.'</ol>';
        return $toc;
    }

    /**
     * Return move notice.
     * @return bool|string
     * @throws \core\exception\moodle_exception
     */
    public function snap_footer_alert() {
        return $this->output->render_from_template('theme_snap/footer_alert', null);
    }

    /**
     * Generates a notification if course format is not topics or weeks the user is editing and is a teacher/mananger.
     *
     * @return string
     * @throws \core\exception\coding_exception
     */
    public function course_format_warning() {
        global $COURSE;

        $format = $COURSE->format;
        if (in_array($format, ['weeks', 'topics', 'tiles'])) {
            return '';
        }

        if (!$this->page->user_is_editing()) {
            return '';
        }

        if (!has_capability('moodle/course:manageactivities', context_course::instance($COURSE->id))) {
            return '';
        }

        $url = new moodle_url('/course/edit.php', ['id' => $COURSE->id]);
        return $this->output->notification(get_string('courseformatnotification', 'theme_snap', $url->out()));
    }

    /**
     * Renders html to display a course search form.
     *
     * @param string $value default value to populate the search field
     * @param string $format display format - 'plain' (default), 'short' or 'navbar'
     * @return string
     */
    public function course_search_form($value = '', $format = 'plain') {
        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }

        switch ($format) {
            case 'navbar' :
                $formid = 'coursesearchnavbar';
                $inputid = 'navsearchbox';
                $inputsize = 20;
                break;
            case 'short' :
                $inputid = 'shortsearchbox';
                $inputsize = 12;
                break;
            default :
                $inputid = 'coursesearchbox';
                $inputsize = 30;
        }

        $data = (object) [
            'searchurl' => (new moodle_url('/course/search.php'))->out(false),
            'id' => $formid,
            'inputid' => $inputid,
            'inputsize' => $inputsize,
            'value' => $value,
        ];

        return $this->render_from_template('theme_snap/course_search_form', $data);
    }

    /**
     * Renders HTML to display particular course category - list of it's subcategories and courses
     *
     * Invoked from /course/index.php
     *
     * @param int|stdClass|coursecat $category
     */
    public function course_category($category) {
        global $CFG;
        $this->page->blocks->add_region('side-pre');
        $basecategory = \core_course_category::get(0);
        $coursecat = \core_course_category::get(is_object($category) ? $category->id : $category);
        $site = get_site();
        $output = '';
        $categoryselector = '';
        // NOTE - we output manage catagory button in the layout file in Snap.

        if (!$coursecat->id) {
            if (\core_course_category::is_simple_site() == 1) {
                // There exists only one category in the system, do not display link to it.
                $coursecat = \core_course_category::get_default();
                $strfulllistofcourses = get_string('fulllistofcourses');
                $this->page->set_title("$site->shortname: $strfulllistofcourses");
            } else {
                $strcategories = get_string('categories');
                $this->page->set_title("$site->shortname: $strcategories");
            }
        } else {
            $title = $site->shortname;
            if ($basecategory->get_children_count() > 1) {
                $title .= ": ". $coursecat->get_formatted_name();
            }
            $this->page->set_title($title);

            // Print the category selector.
            if ($basecategory->get_children_count() > 1) {
                $select = new \core\output\single_select(new moodle_url('/course/index.php'), 'categoryid',
                        \core_course_category::make_categories_list(), $coursecat->id, null, 'switchcategory');
                $select->set_label(get_string('category').':');
                $categoryselector .= $this->render($select);
            }
        }
        $output .= '<div class="row">';
        $output .= '<div class="d-flex flex-wrap row-gap-1">';
        // Add cat select box if available.
        if(!empty($categoryselector)){
            $output .= '<div class="px-3">';
            $output .= $categoryselector;
            $output .= '</div>';
        }
        $output .= '<div class="px-3">';
        // Add course search form.
        $output .= $this->course_search_form();
        $output .= '</div>';
        $output .= '</div>';

        $chelper = new \coursecat_helper();
        // Prepare parameters for courses and categories lists in the tree.
        $atts = ['class' => 'category-browse category-browse-'.$coursecat->id];
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_AUTO)->set_attributes($atts);

        $coursedisplayoptions = [];
        $catdisplayoptions = [];
        $browse = optional_param('browse', null, PARAM_ALPHA);
        $perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $baseurl = new moodle_url('/course/index.php');
        if ($coursecat->id) {
            $baseurl->param('categoryid', $coursecat->id);
        }
        if ($perpage != $CFG->coursesperpage) {
            $baseurl->param('perpage', $perpage);
        }
        $coursedisplayoptions['limit'] = $perpage;
        $catdisplayoptions['limit'] = $perpage;
        if ($browse === 'courses' || !$coursecat->has_children()) {
            $coursedisplayoptions['offset'] = $page * $perpage;
            $coursedisplayoptions['paginationurl'] = new moodle_url($baseurl, ['browse' => 'courses']);
            $catdisplayoptions['nodisplay'] = true;
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, ['browse' => 'categories']);
            $catdisplayoptions['viewmoretext'] = new \core\lang_string('viewallsubcategories');
        } else if ($browse === 'categories' || !$coursecat->has_courses()) {
            $coursedisplayoptions['nodisplay'] = true;
            $catdisplayoptions['offset'] = $page * $perpage;
            $catdisplayoptions['paginationurl'] = new moodle_url($baseurl, ['browse' => 'categories']);
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, ['browse' => 'courses']);
            $coursedisplayoptions['viewmoretext'] = new \core\lang_string('viewallcourses');
        } else {
            // We have a category that has both subcategories and courses, display pagination separately.
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, ['browse' => 'courses', 'page' => 1]);
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, ['browse' => 'categories', 'page' => 1]);
        }
        $chelper->set_courses_display_options($coursedisplayoptions)->set_categories_display_options($catdisplayoptions);

        // Display course category tree.
        $output .= $this->coursecat_tree($chelper, $coursecat);

        // Add action buttons.
        $context = get_category_or_system_context($coursecat->id);

        return $output;
    }

    /**
     * Prints a course footer with course contacts, course description and recent updates.
     *
     * @return string
     */

    public function course_footer() {
        global $DB, $COURSE, $CFG;

        // Check toggle switch.
        if (empty($this->page->theme->settings->coursefootertoggle)) {
            return false;
        }

        $context = context_course::instance($COURSE->id);
        $courseteachers = '';
        $coursesummary = '';

        $clist = new \core_course_list_element($COURSE);
        $teachers = $clist->get_course_contacts();

        if (!empty($teachers)) {
            // Get all teacher user records in one go.
            $teacherids = [];
            foreach ($teachers as $teacher) {
                $teacherids[] = $teacher['user']->id;
            }
            $teacherusers = $DB->get_records_list('user', 'id', $teacherids);

            // Course contacts.
            $courseteachers .= '<h4 class="h5">'.get_string('coursecontacts', 'theme_snap').'</h4>';
            foreach ($teachers as $teacher) {
                if (!isset($teacherusers[$teacher['user']->id])) {
                    continue;
                }
                $teacheruser = $teacherusers[$teacher['user']->id];
                $courseteachers .= $this->print_teacher_profile($teacheruser);
            }
        }
        // If user can edit add link to manage users.
        if (has_capability('moodle/course:enrolreview', $context)) {
            if (empty($courseteachers)) {
                $courseteachers = "<h4 class='h5'>".get_string('coursecontacts', 'theme_snap')."</h4>";
            }
            $courseteachers .= '<br><a id="enrolled-users" class="btn btn-outline-secondary btn-sm"
                href="'.$CFG->wwwroot.'/user/index.php?id='.$COURSE->id.'">'.get_string('enrolledusers', 'enrol').'</a>';
        }

        // Course cummary.
        if (!empty($COURSE->summary)) {
            $coursesummary = '<h4 class="h5">'.get_string('aboutcourse', 'theme_snap').'</h4>';
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $formatoptions->overflowdiv = true;
            $formatoptions->context = $context;
            $coursesummarycontent = file_rewrite_pluginfile_urls($COURSE->summary,
                'pluginfile.php', $context->id, 'course', 'summary', null);
            $coursesummarycontent = format_text($coursesummarycontent, $COURSE->summaryformat, $formatoptions);
            $coursesummary .= '<div id="snap-course-footer-summary">'.$coursesummarycontent.'</div>';
        }

        // If able to edit add link to edit summary.
        if (has_capability('moodle/course:update', $context)) {
            if (empty($coursesummary)) {
                $coursesummary = '<h4 class="h5">'.get_string('aboutcourse', 'theme_snap').'</h4>';
            }
            $coursesummary .= '<br><a id="edit-summary" class="btn btn-outline-secondary btn-sm"
            href="'.$CFG->wwwroot.'/course/edit.php?id='.$COURSE->id.'#id_descriptionhdr">'.get_string('edit').'</a>';
        }

        // Get recent activities on mods in the course.
        $courserecentactivities = $this->get_mod_recent_activity($context);
        $courserecentactivity = '';
        if ($courserecentactivities) {
            $courserecentactivity = '<h4 class="h5">'.get_string('recentactivity').'</h4>';
            if (!empty($courserecentactivities)) {
                $courserecentactivity .= $courserecentactivities;
            }
        }
        // If user can edit add link to moodle recent activity stuff.
        if (has_capability('moodle/course:update', $context)) {
            if (empty($courserecentactivities)) {
                $courserecentactivity = '<h4 class="h5">'.get_string('recentactivity').'</h4>';
                $courserecentactivity .= get_string('norecentactivity');
            }
            $courserecentactivity .= '<div class="col-xs-12 clearfix"><a href="'.$CFG->wwwroot.'/course/recent.php?id='
                .$COURSE->id.'">'.get_string('showmore', 'form').'</a></div>';
        }

        if (!empty($courserecentactivity)) {
            $columns[] = $courserecentactivity;
        }
        if (!empty($courseteachers)) {
            $columns[] = $courseteachers;
        }
        if (!empty($coursesummary)) {
            $columns[] = $coursesummary;
        }

        $output = '';
        if (empty($columns)) {
            return $output;
        } else {
            $output .= '<div class="row">';
            $output .= '<div class="col-lg-3 col-md-4"><ul id="snap-course-footer-contacts">'.$courseteachers.'</ul></div>';
            $output .= '<div class="col-lg-9 col-md-8"><div id="snap-course-footer-about">'.$coursesummary.'</div></div>';
            $output .= '<div class="col-sm-12"><div id="snap-course-footer-recent-activity">'.$courserecentactivity.'</div></div>';
            $output .= '</div>';
        }

        $data = [
            'output' => $this,
        ];

        $output .= $this->render_from_template('theme_boost/footer', $data);

        return $output;
    }

    /**
     * Helper function to decide whether to show the communication link or not.
     *
     * @return bool
     */
    public function has_communication_links(): bool {
        if (during_initial_install() || !\core_communication\api::is_available()) {
            return false;
        }
        return !empty($this->communication_link());
    }

    /**
     * Returns the communication link, complete with html.
     *
     * @return string
     */
    public function communication_link(): string {
        $link = $this->communication_url() ?? '';
        $commicon = $this->pix_icon('t/messages-o', '', 'moodle', ['class' => 'fa fa-comments']);
        $newwindowicon = $this->pix_icon('i/externallink', get_string('opensinnewwindow'), 'moodle', ['class' => 'ms-1']);
        $content = $commicon . get_string('communicationroomlink', 'course') . $newwindowicon;
        $html = \core\output\html_writer::tag('a', $content, ['target' => '_blank', 'href' => $link]);

        return !empty($link) ? $html : '';
    }

    /**
     * Returns the communication url for a given instance if it exists.
     *
     * @return string
     */
    public function communication_url(): string {
        global $COURSE;
        $url = '';
        if ($COURSE->id !== SITEID) {
            $comm = \core_communication\api::load_by_instance(
                context: \core\context\course::instance($COURSE->id),
                component: 'core_course',
                instancetype: 'coursecommunication',
                instanceid: $COURSE->id,
            );
            $url = $comm->get_communication_room_url();
        }

        return !empty($url) ? $url : '';
    }

    /**
     * Print teacher profile
     * Prints a media object with the techers photo, name (links to profile) and desctiption.
     *
     * @param stdClass $user
     * @return string
     */
    public function print_teacher_profile($user) {
        global $CFG, $USER;

        $userpicture = new \core\output\user_picture($user);
        $userpicture->link = false;
        $userpicture->alttext = true;
        if (empty($userpicture->user->imagealt)) {
            $userpicture->user->imagealt = format_string(fullname($user));
        }
        $userpicture->size = 100;
        $picture = $this->render($userpicture);

        $fullname = '<a href="' .$CFG->wwwroot. '/user/profile.php?id=' .$user->id. '"><h3 class="title" >'.format_string(fullname($user)).'</h3></a>';
        $data = (object) [
            'image' => $picture,
            'content' => $fullname,
        ];
        if ($USER->id != $user->id) {
            $messageicon = '<img class="svg-icon" alt="" role="presentation" src="'
                .$this->output->image_url('messages', 'theme').' ">';
            $message = '<br><small><a href="'.$CFG->wwwroot.
                '/message/index.php?id='.$user->id.'">message'.$messageicon.'</a></small>';
            $data->content .= $message;
        }

        return $this->render_from_template('theme_snap/media_object', $data);
    }

    /**
     * Print recent activites for a course
     *
     * @param stdClass $context
     * @return string
     */
    public function get_mod_recent_activity($context) {
        global $COURSE;
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
        $recentactivity = [];
        $timestart = time() - (86400 * 7); // Only show last 7 days activity.
        if (optional_param('testing', false, PARAM_BOOL)) {
            $timestart = time() - (86400 * 3000); // 3000 days ago for testing.
        }
        $modinfo = get_fast_modinfo($COURSE);
        $usedmodules = $modinfo->get_used_module_names();
        // Don't show activity for folder mod.
        unset($usedmodules['folder']);
        if (empty($usedmodules)) {
            // No used modules so return null string.
            return '';
        }
        foreach ($usedmodules as $modname => $modfullname) {
            // Each module gets it's own logs and prints them.
            ob_start();
            $hascontent = component_callback('mod_'. $modname, 'print_recent_activity',
                    [$COURSE, $viewfullnames, $timestart], false);
            if ($hascontent) {
                $content = ob_get_contents();
                if (!empty($content)) {
                    $recentactivity[$modname] = $content;
                }
            }
            ob_end_clean();
        }

        $output = '';
        if (!empty($recentactivity)) {
            foreach ($recentactivity as $modname => $moduleactivity) {
                // Get mod icon, empty alt as title already there.
                $img = \core\output\html_writer::tag('img', '', [
                    'src' => $this->output->image_url('icon', $modname),
                    'alt' => '',
                ]);

                // Create media object for module activity.
                $data = (object) [
                    'image' => $img,
                    'content' => $moduleactivity,
                    'class' => $modname,
                ];
                $output .= $this->render_from_template('theme_snap/media_object', $data);
            }
        }
        return $output;
    }

    /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * @param cm_info $mod
     * @return bool
     */
    public function is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($mod);
            $conditionalhidden = !$info->is_available_for_all();
        }
        return $conditionalhidden;
    }

    private function get_completion_dialog_content($mod) {
        global $COURSE;

        $courseformat = course_get_format($mod->get_course());
        $section = $mod->get_section_info();
        $completionclass = $courseformat->get_output_classname('content\\cm\\completion');
        $completion = new $completionclass($courseformat, $section, $mod);
        $templatedata = $completion->export_for_template($this->output);

        return $templatedata->completiondialog['dialogcontent'];
    }

    // Callback to filter students from enrolled users.
    private function is_student($enrolledUsers) {
        $filteredStudents = array_filter($enrolledUsers, function($user) {
            if (isset($user[5])) {
                return true;
            }
        });
        return $filteredStudents;
    }

    /**
     * Override course render for course and category box in home page.
     *
     * This is an internal function, to display an information about just one course
     * please use {@link core_course_renderer::course_info_box()}
     *
     * @param coursecat_helper $chelper various display options
     * @param core_course_list_element|stdClass $course
     * @param string $additionalclasses additional classes to add to the main <div> tag (usually
     *    depend on the course position in list - first/last/even/odd)
     * @return string
     */
    protected function coursecat_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
        global $CFG, $PAGE, $OUTPUT, $USER;
        if ($PAGE->pagetype !== 'site-index') {
            return \core_course_renderer::coursecat_coursebox($chelper, $course, $additionalclasses);
        }
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }
        if ($course instanceof stdClass) {
            $course = new \core_course_list_element($course);
        }
        $content = '';
        $cardcontent = '';
        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            $classes = trim('coursebox clearfix '. $additionalclasses);

            $cardcontent .= \core\output\html_writer::start_tag('div', array('class' => 'info'));
            $cardcontent .= $this->course_name($chelper, $course);
            $cardcontent .= $this->course_enrolment_icons($course);
            $cardcontent .= \core\output\html_writer::end_tag('div');
            $cardcontent .= \core\output\html_writer::start_tag('div', array('class' => 'content'));
            $cardcontent .= $this->coursecat_coursebox_content($chelper, $course);
            $cardcontent .= \core\output\html_writer::end_tag('div');
        } else {
            //These are the course cards for Enrolled Courses and Available courses in the homepage.
            // Course image.
            $url = $OUTPUT->get_generated_image_for_id($course->id);
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $url = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            }
            // Course visibility.
            $isvisible = $course->visible;
            $hiddeninfobadge = '';
            $imageclasses = 'snap-home-courses-image';
            if (!$isvisible) {
                $hiddeninfobadge = \core\output\html_writer::tag('span',get_string('hiddenfromstudents'),
                    [
                        'class' => 'badge bg-info text-white hiddenbadge'
                    ]);
                $imageclasses .= ' hiddencourse';
            }
            $classes = trim('col-sm-3 coursebox clearfix '. $additionalclasses);
            // Course category information.
            $coursecategoryname = '';
            $category = \core_course_category::get($course->category, IGNORE_MISSING);
            if (isset($category)) {
                $category = $category->name;
                $coursecategoryname = \core\output\html_writer::tag('span', '<b>'.get_string('category').": ".
                    '</b>'.$category, ['class' => 'coursecategory']);
            }

            if (isloggedin()) {
                // Enrolled students information.
                $enrolledstudents = $this->is_student(enrol_get_course_users_roles($course->id));
                $studentscount = count($enrolledstudents);
                $studentsstring = strtolower(get_string('students'));
                if ($studentscount == 1 ) {
                    $studentsstring = strtolower(get_string('student','theme_snap'));
                }
                $enrolledstudentsinfo = $studentscount.' '.$studentsstring;

                // Starred courses information.
                $usercontext = \context_user::instance($USER->id);
                $service = \core_favourites\service_factory::get_service_for_user_context($usercontext);
                $isfavourite = $service->favourite_exists('core_course', 'courses', $course->id,
                    \context_course::instance($course->id));
            }

            $data  = array(
                'classes' => $classes,
                'courseid' => $course->id,
                'isloggedin' => isloggedin(),
                'isfavourite' => $isfavourite ?? '',
                'imageclasses' => $imageclasses,
                'courseimage' => $url ? $url : '',
                'hiddeninfobadge' => $hiddeninfobadge,
                'enrolledstudents' => $enrolledstudentsinfo ?? '',
                'data-type' => self::COURSECAT_TYPE_COURSE,
                'coursecategoryname' => $coursecategoryname,
                'imagestyle' => 'background-image: url('.$url.');',
                'coursecontacts' => $this->course_contacts($course),
                'coursename' => $chelper->get_course_formatted_name($course),
                'coursesummary' => $this->course_summary($chelper, $course),
                'coursecustomfields' => $this->course_custom_fields($course),
                'hassummary' => $this->course_summary($chelper, $course) ? true : false,
                'courselink' => new moodle_url('/course/view.php', ['id' => $course->id]),
            );

            $content = $this->output->render_from_template('theme_snap/home_page_coursebox', $data);
            return $content;
        }

        if ($chelper->get_show_courses() < self::COURSECAT_SHOW_COURSES_EXPANDED) {
            $classes .= ' collapsed';
        }

        $content .= \core\output\html_writer::start_tag('div', array(
            'class' => $classes,
            'data-courseid' => $course->id,
            'data-type' => self::COURSECAT_TYPE_COURSE,
        ));
        $content .= $cardcontent;
        $content .= \core\output\html_writer::end_tag('div');

        return $content;
    }

}
