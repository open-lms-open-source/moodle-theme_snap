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
        $conditional = false;
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            if ($mod->modname === 'resource') {
                // Get file type from icon
                // (note, I also tried this using a combo of substr and strpos and preg_match was much faster!)
                $matches = array();
                preg_match ('#/(\w+)-#', $mod->icon, $matches);
                $filetype = $matches[1];
                $modclasses = array('snap-resource', 'snap-mime-'.$filetype);
                $extension = array(
                    'powerpoint' => 'ppt',
                    'document' => 'doc',
                    'spreadsheet' => 'xls',
                    'archive' => 'zip',
                    'pdf' => 'pdf',
                );
                if (in_array($filetype, array_keys($extension))) {
                    $filetype = $extension[$filetype];
                }
                $attr['data-type'] = $filetype;
            } else if ($mod->modname === 'label') {
                // Do nothing.
            } else if ($mod->modname === 'folder' && !$mod->get_url()) {
                // Folder mod set to display on page.
                $modclasses = array('snap-activity');
            } else if (plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE) === MOD_ARCHETYPE_RESOURCE) {
                $modclasses = array('snap-resource');
                $attr['data-type'] = $mod->modfullname;
            } else {
                $modclasses = array('snap-activity');
                $attr['data-type'] = $mod->modfullname;
            }
            if (!$mod->visible) {
                $modclasses [] = 'draft';
            }
            // Not we don't want to apply the conditional class using ->available as this will show a conditional
            // notice for all activities that are inside a conditional section.
            if (!empty($mod->conditionscompletion)
                || !empty($mod->conditionsgrade)
                || !empty($mod->conditionsfield)
            ) {
                $conditional = true;
                $modclasses [] = 'conditional';
            }
            if (!$mod->available && !$mod->uservisible) {
                $modclasses [] = 'unavailable';
            }
            $modclasses [] = 'activity';
            $modclasses [] = $mod->modname;
            $modclasses [] = "modtype_$mod->modname";
            $modclasses [] = $mod->extraclasses;

            $attr['class'] = implode(' ', $modclasses);
            $attr['id'] = 'module-' . $mod->id;

            if ($conditional) {
                // Show availability info (if module is not available).
                $modulehtml .= html_writer::tag('aside', get_string('conditional', 'theme_snap').
                    $this->course_section_cm_availability($mod, $displayoptions), array('class' => 'conditional_info'));
            }

            $draftmarker = html_writer::tag('aside', get_string('draft', 'theme_snap'), array('class' => 'draft_info'));

            // Ugly workaround to integrate with existing JS.
            $draftwrapperclass = "contentafterlink draftwrapperworkaround";
            if (!$mod->visible) {
                $draftwrapperclass .= " dimmed_text";
            }
            $draftmarker = html_writer::div($draftmarker, $draftwrapperclass);

            $modulehtml .= $draftmarker;

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

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        // Start a wrapper for the actual content to keep the indentation consistent.
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url).
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;

            if ($this->page->user_is_editing()) {
                $output .= ' ' . course_get_cm_rename_action($mod, $sectionreturn);
            }

            // Module can put text after the link (e.g. forum unread).
            $output .= $mod->get_after_link();

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div');
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->get_url();
        if (empty($url)) {
            $output .= $contentpart;
        }

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->get_after_edit_icons();
        }

        $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        if (!empty($modicons)) {
            $output .= html_writer::span($modicons, 'actions');
        }

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before.
        if (!empty($url)) {
            $output .= $contentpart;
        }

        $output .= html_writer::end_tag('div');

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }


}

