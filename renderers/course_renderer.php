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
 * Snap Course Renderers
 *
 * @package    theme_snap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/course/renderer.php");

class theme_snap_core_course_renderer extends core_course_renderer {


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
        $output .= html_writer::tag('label', $strsearchcourses, array('for' => $inputid));
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
            return ((object) array('type' => $filetype, 'extension' => $ext));
        } else {
            return ($mod->modfullname);
        }
    }

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
            $modtype = $this->get_mod_type($mod);
            if ($mod->modname === 'resource') {
                $modclasses = array('snap-resource', 'snap-mime-'.$modtype->extension);
            } else if ($mod->modname === 'label') {
                // Do nothing.
            } else if ($mod->modname === 'folder' && !$mod->get_url()) {
                // Folder mod set to display on page.
                $modclasses = array('snap-activity');
            } else if (plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE) === MOD_ARCHETYPE_RESOURCE) {
                $modclasses = array('snap-resource');
            } else if ($mod->modname === 'scorm') {
                $modclasses = array('snap-resource');
            } else {
                $modclasses = array('snap-activity');
            }

            // Is this mod draft?
            if (!$mod->visible) {
                $modclasses [] = 'draft';
            }

            // Is this mod conditional?
            if ($this->is_cm_conditionally_hidden($mod)) {
                $modclasses [] = 'conditional';
            }
            if (!$mod->available && !$mod->uservisible) {
                $modclasses [] = 'unavailable';
            }

            // TODO - can we add completion data.

            $modclasses [] = 'snap-asset'; // Added to stop conflicts in flexpage.
            $modclasses [] = 'activity';
            $modclasses [] = $mod->modname;
            $modclasses [] = "modtype_$mod->modname";
            $modclasses [] = $mod->extraclasses;

            $snapmodtype = is_string($modtype) ? $modtype : $modtype->type;
            $attr['data-type'] = $snapmodtype;
            $attr['class'] = implode(' ', $modclasses);
            $attr['id'] = 'module-' . $mod->id;
            if ($modurl = $mod->get_url()) {
                if ($mod->uservisible) {
                    $attr['data-href'] = $modurl;
                }
            }

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
        // 2a) The 'showavailability' option is not set (if that is set,
        //     we need to display the activity so we can show
        //     availability info)
        // or
        // 2b) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible &&
            (empty($mod->showavailability) || empty($mod->availableinfo))) {
            return $output;
        }

        $output .= "<div class='clearfix'>";
        // Start the div for the activity content.
        $output .= "<div class='activityinstance'>";
        // Display the link to the module (or do nothing if module has no url).
        $cmname = $this->course_section_cm_name($mod, $displayoptions);
        if (!empty($cmname)) {
            $output .= $cmname;
        }
        // Meta.
        $output .= "<div class='snap-meta'>";
        // Activity/resource type.
        $modtype = $this->get_mod_type($mod);
        $snapmodtype = is_string($modtype) ? format_string($modtype) : format_string($modtype->type);
        $output .= "<span class='snap-assettype'>".$snapmodtype."</span>";

        if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', context_course::instance($mod->course))) {
            // Grouping label.
            $groupings = groups_get_all_groupings($mod->course);
            $output .= "<span class='snap-groupinglabel'>".format_string($groupings[$mod->groupingid]->name)."</span>";

            // TBD - add a title to show this is the Grouping...
        }

        // Draft status - always output, shown via css of parent.
        $output .= "<span class='draft_info'>".get_string('draft', 'theme_snap')."</span>";

        $availabilityinfo = $this->course_section_cm_availability($mod, $displayoptions);
        if ($availabilityinfo !== '') {
            $conditionalinfo = get_string('conditional', 'theme_snap');
            $output .= "<span class='conditional_info'>$conditionalinfo</span>";
            $output .= "<div class='availabilityinfo'>$availabilityinfo</div>";
        }
        $output .= "</div>"; // Close snap-meta.

        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $output .= $contentpart;

        if (!empty($cmname)) {
            // Module can put text after the link (e.g. forum unread).
            $output .= $mod->get_after_link();
        }
        $output .= "</div>";

        // Build up edit icons.
        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = $this->course_get_cm_edit_actions($mod, $sectionreturn);
            $modicons .= $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->get_after_edit_icons();
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
