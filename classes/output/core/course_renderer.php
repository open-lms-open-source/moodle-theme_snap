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

namespace theme_snap\output\core;

defined('MOODLE_INTERNAL') || die();

use cm_info;
use context_course;
use context_module;
use html_writer;
use moodle_url;
use stdClass;
use theme_snap\activity;
use theme_snap\activity_meta;

require_once($CFG->dirroot . "/mod/book/locallib.php");
require_once($CFG->libdir . "/gradelib.php");

class course_renderer extends \core_course_renderer {
    /**
     * override course render for course module list items
     * add additional classes to list item (see $modclass)
     *
     * @author: SL / GT
     * @param stdClass $course
     * @param \completion_info $completioninfo
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
                if (in_array($extension, $this->snap_multimedia())) {
                    $modclasses[] = 'js-snap-media';
                }
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

            $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $mod->context);
            // If the module isn't available, or we are a teacher (can view hidden activities) then get availability
            // info.
            $availabilityinfo = '';
            if (!$mod->available || $canviewhidden) {
                $availabilityinfo = $this->course_section_cm_availability($mod, $displayoptions);
            }

            if ($availabilityinfo !== '' && !$mod->uservisible || $canviewhidden) {
                $modclasses [] = 'conditional';
            }
            if (!$mod->available && !$mod->uservisible) {
                $modclasses [] = 'unavailable';
            }
            // TODO - can we add completion data.
            if (has_capability('moodle/course:update', $mod->context)) {
                $modclasses [] = 'snap-can-edit';
            }

            $modclasses [] = 'snap-asset'; // Added to stop conflicts in flexpage.
            $modclasses [] = 'activity'; // Moodle needs this for drag n drop.
            $modclasses [] = $mod->modname;
            $modclasses [] = "modtype_$mod->modname";
            $modclasses [] = $mod->extraclasses;

            $attr['data-type'] = $snapmodtype;
            $attr['class'] = implode(' ', $modclasses);
            $attr['id'] = 'module-' . $mod->id;
            $attr['data-modcontext'] = $mod->context->id;

            $output .= html_writer::tag('li', $modulehtml, $attr);
        }
        return $output;
    }


    /**
     * Renders HTML to show course module availability information
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_availability(cm_info $mod, $displayoptions = array()) {
        // If we have available info, always spit it out.
        if (!empty($mod->availableinfo)) {
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
     * @param \stdClass $course
     * @param \completion_info $completioninfo
     * @param \cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {

        global $COURSE;

        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        // hidden in a way that leaves no info, such as using the
        // eye icon.
        if (!$mod->uservisible
            && (empty($mod->availableinfo))) {
            return $output;
        }
        $output .= '<div class="asset-wrapper">';

        // Drop section notice.
        if (has_capability('moodle/course:update', $mod->context)) {
            $output .= '<a class="snap-move-note" href="#">'.get_string('movehere', 'theme_snap').'</a>';
        }
        // Start the div for the activity content.
        $output .= "<div class='activityinstance'>";
        // Display the link to the module (or do nothing if module has no url).
        $cmname = $this->course_section_cm_name($mod, $displayoptions);
        $assetlink = '';

        if (!empty($cmname)) {
            // Activity/resource type.
            $snapmodtype = $this->get_mod_type($mod)[0];
            $assetlink = '<div class="snap-assettype">'.$snapmodtype.'</div>';
            // Asset link.
            $assetlink .= '<h4 class="snap-asset-link">'.$cmname.'</h4>';
        }

        // Asset content.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);

        // Asset metadata - groups, completion etc.
        // Due date, feedback available and all the nice snap things.
        $snapcompletionmeta = '';
        $snapcompletiondata = $this->module_meta_html($mod);
        if($snapcompletiondata) {
            $snapcompletionmeta = '<div class="snap-completion-meta">'.$snapcompletiondata.'</div>';
        }

        // Completion tracking.
        $completiontracking = '<div class="snap-asset-completion-tracking">'.$this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions).'</div>';

        // Draft status - always output, shown via css of parent.
        $drafttag = '<div class="snap-draft-tag">'.get_string('draft', 'theme_snap').'</div>';

        // Group.
        $groupmeta = '';
        // Resources cannot have groups/groupings.
        if($mod->modname !== 'resource') {
            $canmanagegroups = has_capability('moodle/course:managegroups', context_course::instance($mod->course));
            if ($canmanagegroups && $mod->effectivegroupmode != NOGROUPS) {
                if ($mod->effectivegroupmode == VISIBLEGROUPS) {
                    $groupinfo = get_string('groupsvisible');
                } else if ($mod->effectivegroupmode == SEPARATEGROUPS) {
                    $groupinfo = get_string('groupsseparate');
                }
                $groupmeta .= '<div class="snap-group-tag">'.$groupinfo.'</div>';
            }

            // This will show a grouping (group of groups) name against a module if one has been assigned to the module instance.
            if ($canmanagegroups && !empty($mod->groupingid)) {
                // Grouping label.
                $groupings = groups_get_all_groupings($mod->course);
                $groupmeta .= '<div class="snap-grouping-tag">'.format_string($groupings[$mod->groupingid]->name).'</div>';
            }
        }

        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $mod->context);
        // If the module isn't available, or we are a teacher (can view hidden activities) then get availability
        // info.
        $conditionalmeta = '';
        if (!$mod->available || $canviewhidden) {
            $availabilityinfo = $this->course_section_cm_availability($mod, $displayoptions);
            if($availabilityinfo) {
                $conditionalmeta .= '<div class="snap-conditional-tag">'.$availabilityinfo.'</div>';
            }
        }

        // Add draft, contitional.
        $assetmeta = $drafttag.$conditionalmeta;

        // Build output.
        $postcontent = '<div class="snap-asset-meta" data-cmid="'.$mod->id.'">'.$assetmeta.$mod->afterlink.'</div>';
        $output .= $assetlink.$postcontent.$contentpart.$snapcompletionmeta.$groupmeta.$completiontracking;

        // Bail at this point if we aren't using a supported format. (Folder view is only partially supported).
        $supported = ['folderview', 'topics', 'weeks', 'site'];
        if (!in_array($COURSE->format, $supported)) {
            return parent::course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions).$assetmeta;
        }

        // Build up edit actions.
        $actions = '';
        $actionsadvanced = array();
        $coursecontext = context_course::instance($mod->course);
        $modcontext = context_module::instance($mod->id);
        $baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));

        if (has_capability('moodle/course:update', $modcontext)) {
            $str = get_strings(array('delete', 'move', 'duplicate', 'hide', 'show', 'roles'), 'moodle');
            // TODO - add snap strings here.

            // Move, Edit, Delete.
            if (has_capability('moodle/course:manageactivities', $modcontext)) {
                $movealt = get_string('move', 'theme_snap', $mod->get_formatted_name());
                $moveicon = "<img title='$movealt' aria-hidden='true' class='svg-icon' src='".$this->output->pix_url('move', 'theme')."' />";
                $editalt = get_string('edit', 'theme_snap', $mod->get_formatted_name());
                $editicon = "<img title='$editalt' alt='$editalt' class='svg-icon' src='".$this->output->pix_url('edit', 'theme')."'/>";
                $actions .= "<input id='snap-move-mod-$mod->id' class='js-snap-asset-move sr-only' type='checkbox'><label class='snap-asset-move' for='snap-move-mod-$mod->id'><span class='sr-only'>$movealt</span>$moveicon</label>";
                $actions .= "<a class='snap-edit-asset' href='".new moodle_url($baseurl, array('update' => $mod->id))."'>$editicon</a>";
                $actionsadvanced[] = "<a href='".new moodle_url($baseurl, array('delete' => $mod->id)).
                    "' class='js_snap_delete'>$str->delete</a>";
            }

            // Hide/Show.
            if (has_capability('moodle/course:activityvisibility', $modcontext)) {
                $actionsadvanced[] = "<a href='".new moodle_url($baseurl, array('hide' => $mod->id))."' class='editing_hide js_snap_hide'>$str->hide</a>";
                $actionsadvanced[] = "<a href='".new moodle_url($baseurl, array('show' => $mod->id))."' class='editing_show js_snap_show'>$str->show</a>";
                // AX click to change.
            }

            // Duplicate.
            $dupecaps = array('moodle/backup:backuptargetimport', 'moodle/restore:restoretargetimport');
            if (has_all_capabilities($dupecaps, $coursecontext) &&
            plugin_supports('mod', $mod->modname, FEATURE_BACKUP_MOODLE2) &&
            plugin_supports('mod', $mod->modname, 'duplicate', true)) {
                $actionsadvanced[] = "<a href='".new moodle_url($baseurl, array('duplicate' => $mod->id))."' class='js_snap_duplicate'>$str->duplicate</a>";
            }

            // Asign roles.
            if (has_capability('moodle/role:assign', $modcontext)) {
                $actionsadvanced[] = "<a href='".new moodle_url('/admin/roles/assign.php', array('contextid' => $modcontext->id))."'>$str->roles</a>";
            }

            // Give local plugins a chance to add icons.
            $localplugins = array();
            foreach (get_plugin_list_with_function('local', 'extend_module_editing_buttons') as $function) {
                $localplugins = array_merge($localplugins, $function($mod));
            }

            // TODO - pld string is far too long....
            $locallinks = '';
            foreach ($localplugins as $localplugin) {
                $url = $localplugin->url;
                $text = $localplugin->text;
                $class = $localplugin->attributes['class'];
                $actionsadvanced[] = "<a href='$url' class='$class'>$text</a>";
            }

        }

        $advancedactions = '';
        if (!empty($actionsadvanced)) {
            $moreicon = "<img title='".get_string('more', 'theme_snap')."' alt='".get_string('more', 'theme_snap')."' class='svg-icon' src='".$this->output->pix_url('more', 'theme')."'/>";
            $advancedactions = "<div class='dropdown snap-edit-more-dropdown'>
                      <a href='#' class='dropdown-toggle snap-edit-asset-more' data-toggle='dropdown' aria-expanded='false' aria-haspopup='true'>$moreicon</a>
                      <ul class='dropdown-menu'>";
            foreach ($actionsadvanced as $action) {
                $advancedactions .= "<li>$action</li>";
            }
            $advancedactions .= "</ul></div>";
        }
        $output .= "</div>"; // Close .activityinstance.

        // Add actions menu.
        if ($actions) {
            $output .= "<div class='snap-asset-actions' role='region' aria-label='actions'>";
            $output .= $actions.$advancedactions;
            $output .= "</div>";
        }
        $output .= "</div>"; // Close .asset-wrapper.
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
                $output = html_writer::tag('div', $content, array('class' => trim('contentafterlink ' . $textclasses)));
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
     * Submission call to action.
     *
     * @param cm_info $mod
     * @param activity_meta $meta
     * @return string
     * @throws coding_exception
     */
    public function submission_cta(cm_info $mod, activity_meta $meta) {
        global $CFG;

        if (empty($meta->submissionnotrequired)) {
            $class = 'snap-assignment-stage';
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
            return html_writer::link($url, $message);
        }
        return '';
    }

    /**
     * Get the module meta data for a specific module.
     *
     * @param cm_info $mod
     * @return string
     */
    protected function module_meta_html(cm_info $mod) {

        global $COURSE, $OUTPUT;

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
        $content .= '';

        if ($meta->isteacher) {
            // Teacher - useful teacher meta data.
            $engagementmeta = array();

            // Below, !== false means we get 0 out of x submissions.
            if (!$meta->submissionnotrequired && $meta->numsubmissions !== false) {
                $engagementmeta[] = get_string('xofy'.$meta->submitstrkey, 'theme_snap',
                    (object) array(
                        'completed' => $meta->numsubmissions,
                        'participants' => \theme_snap\local::course_participant_count($COURSE->id, $mod->modname)
                    )
                );
            }

            if ($meta->numrequiregrading) {
                $engagementmeta[] = get_string('xungraded', 'theme_snap', $meta->numrequiregrading);
            }
            if (!empty($engagementmeta)) {
                $engagementstr = implode(', ', $engagementmeta);

                $params = array(
                    'action' => 'grading',
                    'id' => $mod->id,
                    'tsort' => 'timesubmitted',
                    'filter' => 'require_grading'
                );
                $url = new moodle_url("/mod/{$mod->modname}/view.php", $params);

                $content .= html_writer::link($url, $engagementstr);
            }

        } else {
            // Feedback meta.
            if (!empty($meta->grade)) {
                // Note - the link that a module takes you to would be better off defined by a function in
                // theme/snap/activity - for now its just hard coded.
                $url = new \moodle_url('/grade/report/user/index.php', ['id' => $COURSE->id]);
                if (in_array($mod->modname, ['quiz', 'assign'])) {
                    $url = new \moodle_url('/mod/'.$mod->modname.'/view.php?id='.$mod->id);
                }
                $feedbackavailable = get_string('feedbackavailable', 'theme_snap');
                $content .= html_writer::link($url, $feedbackavailable);
            }

            // If submissions are not allowed, return the content.
            if (!empty($meta->timeopen) && $meta->timeopen > time()) {
                // TODO - spit out a 'submissions allowed from' tag.
                return $content;
            }
            $content .= $this->submission_cta($mod, $meta);
        }

        // Activity due date.
        if (!empty($meta->timeclose)) {
            $due = get_string('due', 'theme_snap');
            $url = new \moodle_url("/mod/{$mod->modname}/view.php", ['id' => $mod->id]);
            $dateformat = get_string('strftimedate', 'langconfig');
            $labeltext = $due . ' ' . userdate($meta->timeclose, $dateformat);
            $warningclass = '';
            if($meta->timeclose < time()){
                $warningclass = ' snap-date-overdue';
            }
            $content .= html_writer::link($url, $labeltext, array('class' => 'snap-due-date'.$warningclass));
        }

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
     * @param cm_info $mod
     * @return string
     */
    protected function mod_page_html(cm_info $mod) {
        if (!$mod->uservisible) {
            return "";
        }

        $page = \theme_snap\local::get_page_mod($mod);

        $imgarr = \theme_snap\local::extract_first_image($page->content);

        $thumbnail = '';
        if ($imgarr) {
            $img = html_writer::img($imgarr['src'], $imgarr['alt']);
            $thumbnail = "<div class=summary-figure>$img</div>";
        }

        $readmore = get_string('readmore', 'theme_snap');
        $close = get_string('close', 'theme_snap');

        // Identify content elements which should force an AJAX lazy load.
        $elcontentblist = ['iframe', 'video', 'object', 'embed'];
        $content = $page->content;
        $lazyload = false;
        foreach ($elcontentblist as $el) {
            if (stripos($content, '<'.$el) !== false) {
                $content = ''; // Don't include the content as it is likely to slow the page load down considerably.
                $lazyload = true;
            }
        }
        $contentloaded = !$lazyload ? 1 : 0;

        $o = "
        {$thumbnail}
        <div class='summary-text'>
            {$page->summary}
            <p><a class='btn btn-default pagemod-readmore' title='{$mod->name}' href='{$mod->url}' data-pagemodcontext='{$mod->context->id}'>{$readmore}</a></p>
        </div>

        <div class=pagemod-content tabindex='-1' data-content-loaded={$contentloaded}>
            {$content}
            <div><hr><a  class='snap-action-icon' href='#'>
            <i class='icon icon-close'></i><small>$close</small></a></div>
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
     * Every mime type we consider to be multimedia.
     * @return array
     */
    protected function snap_multimedia() {
        return ['mp3', 'wav', 'audio', 'mov', 'wmv', 'video', 'mpeg', 'avi', 'quicktime', 'flash'];
    }

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {

        $output = '';

        // Nothing to be displayed to the user.
        if (!$mod->uservisible && empty($mod->availableinfo)) {
            return $output;
        }

        // Is this for labels or something with no other page url to point to?
        $url = $mod->url;
        if (!$url) {
            return $output;
        }

        // Get asset name.
        $instancename = $mod->get_formatted_name();
        $groupinglabel = $mod->get_grouping_label();

        $target = '';

        $activityimg = "<img class='iconlarge activityicon' alt='' role='presentation'  src='".$mod->get_icon_url()."' />";

        // Multimedia mods we want to open in the same window.
        $snapmultimedia = $this->snap_multimedia();

        if ($mod->modname === 'resource') {
            $extension = $this->get_mod_type($mod)[1];
            if (in_array($extension, $snapmultimedia) ) {
                // For multimedia we need to handle the popup setting.
                // If popup add a redirect param to prevent the intermediate page.
                if ($mod->onclick) {
                    $url .= "&amp;redirect=1";
                }
            } else {
                $url .= "&amp;redirect=1";
                $target = "target='_blank'";
            }
        }
        if ($mod->modname === 'url') {
            // Set the url to redirect 1 to avoid intermediate pages.
            $url .= "&amp;redirect=1";
            $target = "target='_blank'";
        }

        if ($mod->uservisible) {
            $output .= "<a $target  href='$url'>$activityimg<span class='instancename'>$instancename</span></a>" . $groupinglabel;
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->uservisible).
            $output .= "<div>$activityimg $instancename</div> $groupinglabel";
        }

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
            return !($action instanceof \action_menu_filler);
        });
        $rename = course_get_cm_rename_action($mod, $mod->indent, $sr);
        $edittitle = get_string('edittitle');
        $rename = str_replace('</a>', "$edittitle</a>", $rename);
        $actions['edit-rename'] = $rename;

        return $actions;
    }

    /**
     * Return move notice.
     * @return bool|string
     * @throws moodle_exception
     */
    public function snap_footer_alert() {
        global $OUTPUT;
        return $OUTPUT->render_from_template('theme_snap/footer_alert', null);
    }

    /**
     * Generates a notification if course format is not topics or weeks the user is editing and is a teacher/mananger.
     *
     * @return string
     * @throws \coding_exception
     */
    public function course_format_warning() {
        global $COURSE, $PAGE, $OUTPUT;

        $format = $COURSE->format;
        if (in_array($format, ['weeks', 'topics'])) {
            return '';
        }

        if (!$PAGE->user_is_editing()) {
            return '';
        }

        if (!has_capability('moodle/course:manageactivities', context_course::instance($COURSE->id))) {
            return '';
        }

        $url = new moodle_url('/course/edit.php', ['id' => $COURSE->id]);
        return $OUTPUT->notification(get_string('courseformatnotification', 'theme_snap', $url->out()));
    }
}
