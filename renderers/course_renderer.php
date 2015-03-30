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
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/course/renderer.php");
require_once($CFG->dirroot . "/mod/book/locallib.php");
require_once($CFG->libdir . "/gradelib.php");

class theme_snap_core_course_renderer extends core_course_renderer {
    /**
     * override course render for course module list items
     * add additional classes to list item (see $modclass)
     *
     * @author: SL / GT
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return String
     */
    public function course_section_cm_list_item($course,
    &$completioninfo,
    cm_info $mod,
    $sectionreturn,
    $displayoptions = array()
    ) {
        $output = '';
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            list($snapmodtype, $extension) = $this->get_mod_type($mod);

            if ($mod->modname === 'resource') {
                // Default for resources/attatchments e.g. pdf, doc, etc.
                $modclasses = array('snap-resource', 'snap-mime-'.$extension);
                // For images we overwrite with the native class.
                if ($this->is_image_mod($mod)) {
                    $modclasses = array('snap-native', 'snap-image', 'snap-mime-'.$extension);
                }
            } else if ($mod->modname === 'label') {
                // Do nothing.
            } else if ($mod->modname === 'folder' && !$mod->url) {
                // Folder mod set to display on page.
                $modclasses = array('snap-activity');
            } else if (plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE) === MOD_ARCHETYPE_RESOURCE) {
                $modclasses = array('snap-resource');
            } else if ($mod->modname === 'scorm') {
                $modclasses = array('snap-resource');
            } else {
                $modclasses = array('snap-activity');
            }

            // Special classes for native html elements.
            if (in_array($mod->modname, ['page', 'book'])) {
                $modclasses = array('snap-native', 'snap-mime-'.$mod->modname);
                $attr['aria-expanded'] = "false";
            } else if ($modurl = $mod->url) {
                // For snap cards, js uses this to make the whole card clickable.
                if ($mod->uservisible) {
                    $attr['data-href'] = $modurl;
                }
            }

            // Is this mod draft?
            if (!$mod->visible) {
                $modclasses [] = 'draft';
            }

            // TODO - does not seem to be working.
            $availabilityinfo = $this->course_section_cm_availability($mod, $displayoptions);
            if ($availabilityinfo !== '') {
                $modclasses [] = 'conditional';
            }
            if (!$mod->available && !$mod->uservisible) {
                $modclasses [] = 'unavailable';
            }
            // TODO - can we add completion data.

            $modclasses [] = 'snap-asset'; // Added to stop conflicts in flexpage.
            $modclasses [] = 'activity'; // Moodle needs this.
            $modclasses [] = $mod->modname;
            $modclasses [] = "modtype_$mod->modname";
            $modclasses [] = $mod->extraclasses;

            $attr['data-type'] = $snapmodtype;
            $attr['class'] = implode(' ', $modclasses);
            $attr['id'] = 'module-' . $mod->id;

            $output .= html_writer::tag('li', $modulehtml, $attr);
        }
        return $output;
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link cm_info::get_after_link()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {

        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible
            && (empty($mod->availableinfo))) {
            return $output;
        }

        $output .= "<div class='asset-wrapper'>";
        // Start the div for the activity content.
        $output .= "<div class='activityinstance'>";
        // Display the link to the module (or do nothing if module has no url).
        $cmname = $this->course_section_cm_name($mod, $displayoptions);
        $assetlink = '';
        // SHAME - For moodles ajax show/hide call to work it needs activityinstance > a to add a class of dimmed to.
        // This dimmed class is of course inaccessible junk.
        if (!empty($cmname)) {
            $assetlink = "<a></a><h4 class='snap-asset-link'>".$cmname."</h4>";
        }
        // Meta.
        $assetmeta = "<div class='snap-meta'>";
        // Activity/resource type.
        $snapmodtype = $this->get_mod_type($mod)[0];
        $assetmeta .= "<span class='snap-assettype'>".$snapmodtype."</span>";

        if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', context_course::instance($mod->course))) {
            // Grouping label.
            $groupings = groups_get_all_groupings($mod->course);
            $assetmeta .= "<span class='snap-groupinglabel'>".format_string($groupings[$mod->groupingid]->name)."</span>";

            // TBD - add a title to show this is the Grouping...
        }

        // Draft status - always output, shown via css of parent.
        $assetmeta .= "<span class='draft_info'>".get_string('draft', 'theme_snap')."</span>";

        $availabilityinfo = $this->course_section_cm_availability($mod, $displayoptions);
        if ($availabilityinfo !== '') {
            $conditionalinfo = get_string('conditional', 'theme_snap');
            $assetmeta .= "<span class='conditional_info'>$conditionalinfo</span>";
            $assetmeta .= "<div class='availabilityinfo'>$availabilityinfo</div>";
        }
        $assetmeta .= "</div>"; // Close asset-meta.

        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        // Build output.
        $output .= $assetlink.$assetmeta.$contentpart;

        if (!empty($cmname)) {
            // Module can put text after the link (e.g. forum unread).
            $output .= $mod->afterlink;
        }
        $output .= "</div>"; // Close activity instance.

        // Build up edit icons.
        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = $this->course_get_cm_edit_actions($mod, $sectionreturn);
            $modicons .= $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
            $modicons .= course_get_cm_move($mod, $sectionreturn);
        }

        if (!$this->page->user_is_editing()) {
            $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
        }

        // Add actions menu.
        if ($modicons) {
            $output .= "<div class='actions' role='region' aria-label='actions'>";
                $output .= $modicons;
                $output .= "</div>";
        }
            $output .= "</div>";
            // Close clearfix.
            return $output;
    }

    /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_text(cm_info $mod, $displayoptions = array()) {
        $output = '';
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            // Nothing to be displayed to the user.
            return $output;
        }

        // Get custom module content for Snap, or get modules own content.
        $modmethod = 'mod_'.$mod->modname.'_html';
        if ($this->is_image_mod($mod)) {
            $content = $this->mod_image_html($mod);
        } else if (method_exists($this,  $modmethod )) {
            $content = call_user_func(array($this, $modmethod), $mod);
        } else {
            $content = $mod->get_formatted_content(array('overflowdiv' => false, 'noclean' => true));
        }

        $accesstext = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $content .= $this->module_meta_html($mod);
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
            has_capability('moodle/course:viewhiddenactivities',
            context_course::instance($mod->course));
            if ($accessiblebutdim) {
                if ($conditionalhidden) {
                    $textclasses .= ' conditionalhidden';
                }
                // Show accessibility note only if user can access the module himself.
                $accesstext = get_accesshide(get_string('hiddenfromstudents').':'. $mod->modfullname);
            }
        }
        if ($mod->url) {
            if ($content) {
                // If specified, display extra content after link.
                $output = html_writer::tag('div', $content, array('class' =>
                trim('contentafterlink ' . $textclasses)));
            }
        } else {
            // No link, so display only content.
            $output = html_writer::tag('div', $accesstext . $content,
            array('class' => 'contentwithoutlink ' . $textclasses));
        }
        return $output;
    }

    /*
    ***** SNAP SPECIFIC DISPLAY OF RESOURCES *******
    */

    /**
     * Get module type
     * Note, if module is a resource, get the actual file type
     *
     * @author Guy Thomas
     * @date 2014-06-16
     * @param cm_info $mod
     * @return stdClass | string
     */
    protected function get_mod_type(cm_info $mod) {
        if ($mod->modname === 'resource') {
            // Get file type from icon
            // (note, I also tried this using a combo of substr and strpos and preg_match was much faster!)
            $matches = array();
            preg_match ('#/(\w+)-#', $mod->icon, $matches);
            $filetype = $matches[1];
            $ext = $filetype;
            $extension = array(
                'powerpoint' => 'ppt',
                'document' => 'doc',
                'spreadsheet' => 'xls',
                'archive' => 'zip',
                'pdf' => 'pdf',
                'image' => get_string('image', 'theme_snap'),
            );
            if (in_array($filetype, array_keys($extension))) {
                $filetype = $extension[$filetype];
            }
            return [$filetype, $ext];
        } else {
            return [$mod->modfullname, null];
        }
    }

    /**
     * Is this an image module
     * @param cm_info $mod
     * @return bool
     */
    protected function is_image_mod(cm_info $mod) {
        if ($mod->modname == 'resource') {
            $matches = array();
            preg_match ('#/(\w+)-#', $mod->icon, $matches);
            $filetype = $matches[1];
            $extension = array('jpg', 'jpeg', 'png', 'gif', 'svg', 'image');
            if (in_array($filetype, $extension)) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * Get the module meta data for a specific module.
     *
     * @param cm_info $mod
     *
     * @return string
     */
    protected function module_meta_html(cm_info $mod) {

        global $COURSE, $CFG;

        $content = '';

        if (is_guest(context_course::instance($COURSE->id))) {
            return '';
        }

        // Do we have an activity function for this module for returning meta data?
        // @todo - check module lib.php for a meta function (won't work for core mods but will for ours if we wish).
        $methodname = $mod->modname.'_meta';
        if (method_exists('theme_snap\\activity', $methodname)) {
            $meta = call_user_func('theme_snap\\activity::'.$methodname, $mod);
        } else {
            // Can't get meta data for this module.
            return '';
        }

        $content .= '<div class="module-meta">';

        if ($meta->isteacher) {
            // Teacher - useful teacher meta data.
            if (!empty($meta->timeclose)) {
                $dueinfo = get_string('due', 'theme_snap');
                $dueclass = 'label-info';
                $content .= '<span class="label '.$dueclass.'">'.$dueinfo.' '.
                    userdate($meta->timeclose, get_string('strftimedate', 'langconfig').'</span>');
            }

            $engagementmeta = array();

            $gradedlabel = "info";
            // Below, !== false means we get 0 out of x submissions.
            if (!$meta->submissionnotrequired && $meta->numsubmissions !== false) {
                $engagementmeta[] = get_string('xofy'.$meta->submitstrkey, 'theme_snap',
                    (object) array(
                        'completed' => $meta->numsubmissions,
                        'participants' => \theme_snap\local::course_participant_count($COURSE->id)
                    )
                );
            }

            if ($meta->numrequiregrading) {
                $gradedlabel = "warning";
                $engagementmeta[] = get_string('xungraded', 'theme_snap', $meta->numrequiregrading);
            }

            $link = $CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?action=grading&id='.$mod->id.
                '&tsort=timesubmitted&filter=require_grading';
            $content .= '<a href="'.s($link).'"><span class="label label-'.$gradedlabel.'">'.
                implode(', ', $engagementmeta).'</span></a>';
        } else {
            // Student - useful student meta data.
            if (!empty($meta->timeopen) && $meta->timeopen > time()) {
                // Todo - spit out a 'submissions allowed form' tag.
                $content .= '</div>';
                return $content;
            }
            // Note, due date is rendered seperately for students as it has a warning class if overdue.
            if (!empty($meta->timeclose)) {
                if (empty($meta->submissionnotrequired)
                    && empty($meta->timesubmitted)
                    && time() > usertime($meta->timeclose)
                ) {
                    $dueinfo = get_string('overdue', 'theme_snap');
                    $dueclass = 'label-danger';
                } else {
                    $dueinfo = get_string('due', 'theme_snap');
                    $dueclass = 'label-info';
                }
                $content .= '<span class="label '.$dueclass.'">'.$dueinfo.' '.
                    userdate($meta->timeclose, get_string('strftimedate', 'langconfig').'</span>');
            }

            // Feedback meta.
            if (!empty($meta->grade)) {
                // Note - the link that a module takes you to would be better off defined by a function in
                // theme/snap/activity - for now its just hard coded.
                $url = new \moodle_url('/grade/report/user/index.php', ['id' => $COURSE->id]);
                if (in_array($mod->modname, ['quiz', 'assign'])) {
                    $url = new \moodle_url('/mod/'.$mod->modname.'/view.php?id='.$mod->id);
                }
                $content .= '<a href="'.$url->out().'"><span class="label label-info">'.
                    get_string('feedbackavailable', 'theme_snap').'</span></a>';
            }

            // Submission CTA.
            if (empty($meta->submissionnotrequired)) {
                $content .= '<a class="assignment_stage" href="'.
                    $CFG->wwwroot.'/mod/'.$mod->modname.'/view.php?id='.$mod->id.'">';

                if ($meta->submitted) {
                    if (empty($meta->timesubmitted)) {
                        $submittedonstr = '';
                    } else {
                        $submittedonstr = ' '.userdate($meta->timesubmitted, get_string('strftimedate', 'langconfig'));
                    }
                    $content .= '<span class="label label-success">'.$meta->submittedstr.$submittedonstr.'</span>';
                } else {
                    $warningstr = $meta->draft ? $meta->draftstr : $meta->notsubmittedstr;
                    $warningstr = $meta->reopened ? $meta->reopenedstr : $warningstr;
                    $content .= '<span class="label label-warning">'.$warningstr.'</span>';
                }
                $content .= '</a>';
            }
        }

        $content .= '</div>';
        return $content;
    }


    /**
     * Get resource module image html
     *
     * @param stdClass $mod
     * @return string
     */
    protected function mod_image_html($mod) {
        if (!$mod->uservisible) {
                return "";
        }

        $fs = get_file_storage();
        $context = \context_module::instance($mod->id);
        // TODO: this is not very efficient!!
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
        if (count($files) > 0) {
            foreach ($files as $file) {
                $imgsrc = \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                );
            }
        }
        $summary = $mod->get_formatted_content(array('overflowdiv' => false, 'noclean' => true));

        $imglink = "<a class='snap-image-link' href='{$imgsrc}' target='_blank'><img src='{$imgsrc}' alt=''/></a>";

        $modname = format_string($mod->name);

        if (!empty($summary)) {
            return "<div class='snap-image-image'>$imglink<div class='snap-image-summary'><h6>$modname</h6>$summary</div></div>";
        }

        return "<div class='snap-image-image'><div class='snap-image-title'><h6>$modname</h6></div>$imglink</div>";

    }

    /**
     * Get page module html
     * @param $mod
     * @return string
     */
    protected function mod_page_html($mod) {
        if (!$mod->uservisible) {
            return "";
        }
        global $DB;
        $sql = "SELECT * FROM {course_modules} cm
                  JOIN {page} p ON p.id = cm.instance
                WHERE cm.id = ?";
        $page = $DB->get_record_sql($sql, array($mod->id));

        $context = context_module::instance($mod->id);

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;

        // Make sure we have some summary/extract text for the course page.
        if (!empty($page->intro)) {
            $page->summary = file_rewrite_pluginfile_urls($page->intro,
                'pluginfile.php', $context->id, 'mod_page', 'intro', null);
            $page->summary = format_text($page->summary, $page->introformat, $formatoptions);
        } else {
            $preview = html_to_text($page->content, 0, false);
            $page->summary = shorten_text($preview, 200);
        }

        $content = file_rewrite_pluginfile_urls($page->content,
            'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
        $content = format_text($content, $page->contentformat, $formatoptions);

        $imgarr = \theme_snap\local::extract_first_image($content);

        $thumbnail = '';
        if ($imgarr) {
            $img = html_writer::img($imgarr['src'], $imgarr['alt']);
            $thumbnail = "<div class=summary-figure>$img</div>";
        }

        $readmore = get_string('readmore', 'theme_snap');
        $close = get_string('close', 'theme_snap');

        $o = "
        {$thumbnail}
        <div class='summary-text'>
            {$page->summary}
            <p><a class='pagemod-readmore' href='$mod->url'>$readmore</a></p>
        </div>

        <div class=pagemod-content tabindex='-1'>
            {$content}
            <div><hr><a  class='snap-action-icon' href='#'>
            <i class='icon icon-office-52'></i><small>$close</small></a></div>
        </div>";

        return $o;
    }

    protected function mod_book_html($mod) {
        if (!$mod->uservisible) {
            return "";
        }
        global $DB;

        $cm = get_coursemodule_from_id('book', $mod->id, 0, false, MUST_EXIST);
        $book = $DB->get_record('book', array('id' => $cm->instance), '*', MUST_EXIST);
        $chapters = book_preload_chapters($book);

        if ($book->intro) {
            $context = context_module::instance($mod->id);
            $content = file_rewrite_pluginfile_urls($book->intro, 'pluginfile.php', $context->id, 'mod_book', 'intro', null);
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $formatoptions->overflowdiv = true;
            $formatoptions->context = $context;
            $content = format_text($content, $book->introformat, $formatoptions);

            return "<div class=summary-text>
                    {$content}</div>".$this->book_get_toc($chapters, $book, $cm);
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

        $toc = "<h6>".get_string('chapters', 'theme_snap')."</h6>";
        $toc .= "<ol class=bookmod-chapters>";
        $closemeflag = false; // Control for indented lists.
        $chapterlist = '';
        foreach ($chapters as $ch) {
            $title = trim(format_string($ch->title, true, array('context' => $context)));
            if (!$ch->hidden) {
                if ($closemeflag && !$ch->parent) {
                    $chapterlist .= "</ul></li>";
                    $closemeflag = false;
                }
                $chapterlist .= "<li>";
                $chapterlist .= html_writer::link(new moodle_url('/mod/book/view.php',
                    array('id' => $cm->id, 'chapterid' => $ch->id)), $title, array());
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
     * Yes, this looks like it should be part of core renderer but moodles crazy ia means its part of course.
     */
    public function course_search_form($value = '', $format = 'plain') {
        if ($format !== 'fixy') {
            // For now only handle search in fixy menu.
            return parent::course_search_form($value, $format);
        }

        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }
        $inputid = 'coursesearchbox';
        $inputsize = 30;

        if ($format === 'navbar') {
            $formid = 'coursesearchnavbar';
            $inputid = 'navsearchbox';
        }

        $strsearchcourses = get_string("searchcourses");
        $searchurl = new moodle_url('/course/search.php');

        $form = array('id' => $formid, 'action' => $searchurl, 'method' => 'get', 'class' => "form-inline", 'role' => 'form');
        $output = html_writer::start_tag('form', $form);
        $output .= html_writer::tag('label', $strsearchcourses, array('for' => $inputid, 'class' => 'sr-only'));
        $output .= html_writer::start_div('input-group');
        $search = array('type' => 'text', 'id' => $inputid, 'size' => $inputsize, 'name' => 'search',
        'class' => 'form-control', 'value' => s($value), 'placeholder' => $strsearchcourses);
        $output .= html_writer::empty_tag('input', $search);
        $button = array('type' => 'submit', 'class' => 'btn btn-default');
        $output .= html_writer::start_span('input-group-btn');
        $output .= html_writer::tag('button', get_string('go'), $button);
        $output .= html_writer::end_span();
        $output .= html_writer::end_div(); // Close form-group.
        $output .= html_writer::end_tag('form');

        return $output;
    }




    /**
     * Wrapper around course_get_cm_edit_actions
     *
     * @param cm_info $mod The module
     * @param int $sr The section to link back to (used for creating the links)
     * @return array Of action_link or pix_icon objects
     */
    protected function course_get_cm_edit_actions(cm_info $mod, $sr = null) {
        $actions = course_get_cm_edit_actions($mod, -1, $sr);
        $actions = array_filter($actions, function($action) {
            return !($action instanceof action_menu_filler);
        });
        $rename = course_get_cm_rename_action($mod, $mod->indent, $sr);
        $edittitle = get_string('edittitle');
        $rename = str_replace('</a>', "$edittitle</a>", $rename);
        $actions['edit-rename'] = $rename;

        return $actions;
    }
}
