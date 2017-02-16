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
 * Coures toc renderable
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\renderables;

use context_course;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/format/lib.php');

class course_toc implements \renderable, \templatable{

    use \theme_snap\output\general_section_trait;
    use \theme_snap\renderables\trait_exportable;

    /**
     * @var bool
     */
    public $formatsupportstoc = false;

    /**
     * @var course_toc_module[]
     */
    public $modules = [];

    /**
     * @var \stdClass
     * @wsparam {
     *     chapters: {
     *        type: course_toc_chapter[],
     *        description: "An array of course_toc_chapter objects"
     *     },
     *     listlarge: {
     *        type: PARAM_ALPHAEXT,
     *        description: "list-large css class when TOC has more than 9 chapters"
     *     }
     * };
     */
    public $chapters;

    /**
     * @var course_toc_footer
     */
    public $footer;

    /**
     * @var \stdClass
     */
    protected $course;

    /**
     * @var \format_base
     */
    protected $format;

    /**
     * course_toc constructor.
     * @param null $course
     */
    function __construct($course = null) {
        global $COURSE;
        if (empty($course)) {
            $course = $COURSE;
        }

        $supportedformats = ['weeks', 'topics'];
        if (!in_array($course->format, $supportedformats)) {
            return;
        } else {
            $this->formatsupportstoc = true;
        }

        $this->format  = course_get_format($course);
        $this->course  = $this->format->get_course(); // Has additional fields.

        course_create_sections_if_missing($course, range(0, $this->course->numsections));

        $this->set_modules();
        $this->set_chapters();
        $this->set_footer();
    }

    /**
     * Set modules.
     * @throws \coding_exception
     */
    protected function set_modules() {
        global $CFG, $PAGE;

        // If course does not have any sections then exit - note, module search is not supported in course formats
        // that don't have sections.
        if (!isset($this->course->numsections)) {
            return;
        }

        if (!isset($PAGE->context) && AJAX_SCRIPT) {
            $PAGE->set_context(context_course::instance($this->course->id));
        }

        $modinfo = get_fast_modinfo($this->course);

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname == 'label') {
                continue;
            }
            if ($cm->sectionnum > $this->course->numsections) {
                continue; // Module outside of number of sections.
            }
            if (!$cm->uservisible && (empty($cm->availableinfo))) {
                continue; // Hidden completely.
            }

            $module = new course_toc_module();
            $module->cmid = $cm->id;
            $module->uservisible = $cm->uservisible;
            $module->modname = $cm->modname;
            $module->iconurl = $cm->get_icon_url();
            if ($cm->modname !== 'resource') {
                $module->srinfo = get_string('pluginname', $cm->modname);
            }
            $module->url = '#section-'.$cm->sectionnum.'&module-'.$cm->id;

            if ($this->course->format == 'folderview') {
                // For folder view we will need to add a regular link causing the page to reload.
                $module->url = $CFG->wwwroot.'/course/view.php?id='.
                        $this->course->id.'&section='.$cm->sectionnum.'#module-'.$cm->id;
            }
            $module->formattedname = $cm->get_formatted_name();
            $this->modules[] = $module;
        }
    }

    protected function set_chapters() {

        $this->chapters = (object) [];

        $this->chapters->listlarge = $this->course->numsections > 9 ? 'list-large' : '';

        $this->chapters->chapters= [];

        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($this->course->id));

        $modinfo = get_fast_modinfo($this->course);

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {

            if ($section > $this->course->numsections) {
                continue;
            }
            // Students - If course hidden sections completely invisible & section is hidden, and you cannot
            // see hidden things, bale out.
            if ($this->course->hiddensections
                && !$thissection->visible
                && !$canviewhidden) {
                continue;
            }

            $conditional = $this->is_section_conditional($thissection);
            $chapter = new course_toc_chapter();
            $chapter->outputlink = true;

            if ($canviewhidden) { // Teachers.
                if ($conditional) {
                    $chapter->availabilityclass = 'text-warning';
                    $chapter->availabilitystatus = get_string('conditional', 'theme_snap');
                }
                if (!$thissection->visible) {
                    $chapter->availabilityclass = 'text-warning';
                    $chapter->availabilitystatus = get_string('notpublished', 'theme_snap');
                }
            } else { // Students.
                if ($conditional && !$thissection->uservisible && !$thissection->availableinfo) {
                    // Conditional section, totally hidden from user so skip.
                    continue;
                }
                if ($conditional && $thissection->availableinfo) {
                    $chapter->availabilityclass = 'text-warning';
                    $chapter->availabilitystatus = get_string('conditional', 'theme_snap');
                }
                if (!$conditional && !$thissection->visible) {
                    // Hidden section collapsed, so show as text in TOC.
                    $chapter->outputlink  = false;
                    $chapter->availabilityclass = 'text-warning';
                    $chapter->availabilitystatus = get_string('notavailable');
                }
            }

            $chapter->title = get_section_name($this->course, $section);
            if ($chapter->title == get_string('general')) {
                $chapter->title = get_string('introduction', 'theme_snap');
            }

            if ($this->format->is_section_current($section)) {
                $chapter->iscurrent = true;
            }

            if ($chapter->outputlink) {
                $singlepage = $this->course->format !== 'folderview';
                if ($singlepage) {
                    $chapter->url = '#section-'.$section;
                } else
                    if ($section > 0) {
                        $chapter->url = course_get_url($this->course, $section, ['navigation' => true, 'sr' => $section]);
                    } else {
                        // We need to create the url for section 0, or a hash will get returned.
                        $chapter->url = new moodle_url('/course/view.php', ['id' => $this->course->id, 'section' => $section]);
                    }
            }

            $chapter->progress = new course_toc_progress($this->course, $thissection);
            $this->chapters->chapters[]=$chapter;
        }
    }

    /**
     * @throws \coding_exception
     */
    protected function set_footer() {
        global $OUTPUT;
        $this->footer = (object) [
            'canaddnewsection' => has_capability('moodle/course:update', context_course::instance($this->course->id)),
            'imgurladdnewsection' => $OUTPUT->pix_url('pencil', 'theme'),
            'imgurltools' => $OUTPUT->pix_url('course_dashboard', 'theme')
        ];
    }

}
