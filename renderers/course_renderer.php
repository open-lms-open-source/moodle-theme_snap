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
            if (!$mod->available
                || !empty($mod->conditionscompletion)
                || !empty($mod->conditionsgrade)
                || !empty($mod->conditionsfield)
            ) {
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
            $output .= html_writer::tag('li', $modulehtml, $attr);
        }
        return $output;
    }
}

