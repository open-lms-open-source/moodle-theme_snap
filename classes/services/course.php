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

namespace theme_snap\services;

defined('MOODLE_INTERNAL') || die();

use theme_snap\renderables\course_card;
use theme_snap\local;
use theme_snap\renderables\course_toc;
use theme_snap\color_contrast;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Course service class.
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {

    private function __construct() {
    }

    /**
     * Return singleton.
     *
     * @return course service
     */
    public static function service() {
        static $instance = null;
        if ($instance === null) {
            $instance = new course();
        }
        return $instance;
    }

    /**
     * Return false if the summary files are is not suitable for course cover images.
     * @param $context
     * @return bool
     */
    protected function check_summary_files_for_image_suitability($context) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        $tmparr = [];
        // Remove '.' file from files array.
        foreach ($files as $file) {
            if ($file->get_filename() !== '.') {
                $tmparr[] = $file;
            }
        }
        $files = $tmparr;

        if (empty($files)) {
            // If the course summary files area is empty then its fine to upload an image.
            return true;
        }

        if (count($files) > 1) {
            // We have more than one file in the course summary files area, which is bad.
            return false;
        }

        // @codingStandardsIgnoreLine
        /* @var \stored_file $file*/
        $file = end($files);
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!in_array($ext, local::supported_coverimage_types())) {
            // Unsupported file type.
            return false;
        }

        return true;
    }

    /**
     * @param \context $context
     * @param string $data
     * @param string $filename
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function setcoverimage(\context $context, $data, $filename) {

        global $CFG;

        require_capability('moodle/course:changesummary', $context);

        $fs = get_file_storage();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        if (!file_extension_in_typegroup($filename, 'web_image')) {
            return ['success' => false, 'warning' => get_string('unsupportedcoverimagetype', 'theme_snap', $ext)];
        }

        $newfilename = 'rawcoverimage.'.$ext;

        $binary = base64_decode($data);
        if (strlen($binary) > get_max_upload_file_size($CFG->maxbytes)) {
            throw new \moodle_exception('error:coverimageexceedsmaxbytes', 'theme_snap');
        }

        if ($context->contextlevel === CONTEXT_COURSE) {
            // Course cover images.
            // Check suitability of course summary files area for use with cover images.
            if (!$this->check_summary_files_for_image_suitability($context)) {
                return ['success' => false, 'warning' => get_string('coursesummaryfilesunsuitable', 'theme_snap')];
            }

            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'overviewfiles',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename);

            // Remove any old course summary image files for this context.
            $fs->delete_area_files($context->id, $fileinfo['component'], $fileinfo['filearea']);
        } else if ($context->contextlevel === CONTEXT_SYSTEM || $context->contextlevel === CONTEXT_COURSECAT) {
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea' => 'poster',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename);

            // Remove everything from poster area for this context.
            $fs->delete_area_files($context->id, 'theme_snap', 'poster');
        } else {
            throw new coding_exception('Unsupported context level '.$context->contextlevel);
        }

        // Create new cover image file and process it.
        $storedfile = $fs->create_file_from_string($fileinfo, $binary);
        $success = $storedfile instanceof \stored_file;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            set_config('poster', $newfilename, 'theme_snap');
            local::process_coverimage($context);
        } else if ($context->contextlevel === CONTEXT_COURSE || $context->contextlevel === CONTEXT_COURSECAT) {
            local::process_coverimage($context, $storedfile);

            $finfo = $storedfile->get_imageinfo();
            $imagemaincolor = color_contrast::calculate_image_main_color($storedfile, $finfo);
            $contrast = color_contrast::evaluate_color_contrast($imagemaincolor, "#FFFFFF");

            if ($context->contextlevel === CONTEXT_COURSECAT) {
                $themecolor = get_config('theme_snap', 'themecolor');
                $catconfig = get_config('theme_snap', 'category_color');
                $catscolor = [];
                $catid = $context->instanceid;
                if (!empty($catconfig)) {
                    $catscolor = json_decode($catconfig);
                }
                if (!empty($catscolor) && property_exists($catscolor, $catid)) {
                    $themecolor = $catscolor->$catid;
                }
                $catcontrast = color_contrast::evaluate_color_contrast($imagemaincolor, $themecolor);
                if ($catcontrast < 4.5) {
                    return ['success' => true, 'contrast' => get_string('imageinvalidratiocategory',
                        'theme_snap', number_format((float)$catcontrast, 2))];
                }
            }
            if ($contrast < 4.5) {
                return ['success' => true, 'contrast' => get_string('imageinvalidratio',
                    'theme_snap', number_format((float)$contrast, 2))];
            }
        }
        return ['success' => $success];
    }

    /**
     * Is a specific course favorited or not for the specified or current user.
     *
     * @param int $courseid
     * @param null | int $userid
     * @param bool $fromcache
     * @return bool
     */
    public function favorited($courseid, $userid = null, $fromcache = true) {
        global $USER;

        $userid = $userid !== null ? $userid : $USER->id;

        $favorites = $this->favorites($userid, $fromcache);
        return !empty($favorites) && !empty($favorites[$courseid]);
    }

    /**
     * Get course favorites for specific userid.
     * @param null $userid
     * @param bool $fromcache
     * @return array
     */
    public function favorites($userid = null, $fromcache = true) {
        global $USER, $DB;

        $userid = $userid !== null ? $userid : $USER->id;

        static $favorites = [];

        if (!$fromcache) {
            unset($favorites[$userid]);
        }

        if (!isset($favorites[$userid])) {
            $favorites[$userid] = $DB->get_records('favourite',
                ['userid' => $userid, 'itemtype' => 'courses'],
                'itemid ASC',
                'itemid'
            );
        }

        return $favorites[$userid];
    }

    /**
     * Get courses for current user split by favorite status.
     *
     * @return array
     * @throws \coding_exception
     */
    public function my_courses_split_by_favorites() {
        $courses = enrol_get_my_courses('enddate', 'fullname ASC, id DESC');
        $favorites = $this->favorites();
        $favorited = [];
        $notfavorited = [];
        $past = [];
        foreach ($courses as $course) {
            $today = time();
            if (!empty($course->enddate) && $course->enddate < $today) {
                $course->endyear = userdate($course->enddate, '%Y');
                $past[$course->endyear][$course->id] = $course;
            } else if (isset($favorites[$course->id])) {
                $favorited[$course->id] = $course;
            } else {
                $notfavorited[$course->id] = $course;
            }
        }
        krsort($past); // Reorder list by year.
        return [$past, $favorited, $notfavorited];
    }

    /**
     * Set favorite status on or off.
     *
     * @param string $courseshortname
     * @param bool $on
     * @param null | int $userid
     * @return bool
     */
    public function setfavorite($courseshortname, $on = true, $userid = null) {
        global $USER, $DB;

        $course = $this->coursebyshortname($courseshortname);
        $coursecontext = \context_course::instance($course->id);
        $userid = $userid !== null ? $userid : $USER->id;
        $usercontext = \context_user::instance($userid);

        $favorited = $this->favorited($course->id, $userid, false);
        $ufservice = \core_favourites\service_factory::get_service_for_user_context($usercontext);
        if ($on) {
            if (!$favorited) {
                $ufservice->create_favourite('core_course', 'courses', $course->id, $coursecontext);
            }
        } else {
            if ($favorited) {
                $ufservice->delete_favourite('core_course', 'courses', $course->id, $coursecontext);
            }
        }
        // Kill favorited cache and return if favorited.
        return $this->favorited($course->id, $userid, false);
    }

    /**
     * Get course by shortname.
     * @param string $shortname
     * @return mixed
     */
    public function coursebyshortname($shortname, $fields = '*') {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], $fields, MUST_EXIST);
        return $course;
    }

    /**
     * Get a card renderable by course shortname.
     * @param string $shortname
     * @return course_card (renderable)
     */
    public function cardbyshortname($shortname) {
        $course = $this->coursebyshortname($shortname);
        return new course_card($course);
    }

    /**
     * Get coursecompletion data by course shortname.
     * @param string $shortname
     * @param array $previouslyunavailablesections
     * @param array $previouslyunavailablemods
     * @return array
     */
    public function course_completion($shortname, $previouslyunavailablesections, $previouslyunavailablemods) {
        global $PAGE, $OUTPUT;

        $course = $this->coursebyshortname($shortname);
        if (!isset($PAGE->context) && AJAX_SCRIPT) {
            $PAGE->set_context(\context_course::instance($course->id));
        }

        [$unavailablesections, $unavailablemods] = local::conditionally_unavailable_elements($course);

        $newlyavailablesections = array_diff($previouslyunavailablesections, $unavailablesections);
        $intersectunavailable = array_intersect($previouslyunavailablesections, $unavailablesections);
        $newlyunavailablesections = array_diff($unavailablesections, $intersectunavailable);

        $newlyavailablemods = array_diff($previouslyunavailablemods, $unavailablemods);
        $intersectunavailable = array_intersect($previouslyunavailablemods, $unavailablemods);
        $newlyunavailablemods = array_diff($unavailablemods, $intersectunavailable);

        /** @var \theme_snap_core_course_renderer $courserenderer */
        $courserenderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
        $modinfo = get_fast_modinfo($course);

        $changedsectionhtml = [];
        $changedsections = array_merge($newlyavailablesections, $newlyunavailablesections);
        $format = course_get_format($course);
        $course = $format->get_course();
        if (!empty($changedsections)) {
            $formatrenderer = $format->get_renderer($PAGE);
            foreach ($changedsections as $sectionnumber) {
                $section = $modinfo->get_section_info($sectionnumber);
                $html = $formatrenderer->course_section($course, $section, $modinfo);
                $changedsectionhtml[$sectionnumber] = (object) [
                    'number' => $sectionnumber,
                    'html'   => $html
                ];
            }
        }

        $changedmodhtml = [];
        $changedmods = array_merge($newlyavailablemods, $newlyunavailablemods);
        if (!empty($changedmods)) {
            $modinfo = get_fast_modinfo($course);
            foreach ($changedmods as $modid) {
                $completioninfo = new \completion_info($course);
                $cm = $modinfo->get_cm($modid);
                if (isset($changedsectionhtml[$cm->sectionnum])) {
                    // This module's html has already been included in a changed section html.
                    continue;
                }
                $html = $courserenderer->course_section_cm_list_item($course, $completioninfo, $cm, $cm->sectionnum);
                $changedmodhtml[$modid] = (object) [
                    'id'   => $modid,
                    'html' => $html
                ];
            }
        }

        $unavailablesections = implode(',', $unavailablesections);
        $unavailablemods = implode(',', $unavailablemods);

        $toc = new course_toc($course, $format);

        // If the course format is different from topics or weeks then the $toc would have some empty values.
        $validformats = ['weeks', 'topics'];
        if (!in_array($course->format, $validformats)) {
            $toc->chapters = array('chapters' => []);
            $toc->footer = array('footer' => []);
        }

        return [
            'unavailablesections' => $unavailablesections,
            'unavailablemods' => $unavailablemods,
            'changedmodhtml' => $changedmodhtml,
            'changedsectionhtml' => $changedsectionhtml,
            'toc' => $toc->export_for_template($OUTPUT)
        ];
    }

    /**
     * @param string $shortname
     * @return object
     */
    public function course_toc($shortname) {
        global $OUTPUT;
        $course = $this->coursebyshortname($shortname);
        $toc = new course_toc($course);
        return $toc->export_for_template($OUTPUT);
    }

    /**
     * @param string $shortname
     * @return object
     */
    public function course_toc_chapters($shortname) {
        $course = $this->coursebyshortname($shortname);
        $toc = new course_toc($course);
        return $toc->convert_object_for_export($toc->chapters);
    }

    /**
     * @param string $shortname
     * @param int $sectionnumber
     * @param boolean $highlight
     * @throws \required_capability_exception
     * @return array
     */
    public function highlight_section($shortname, $sectionnumber, $highlight) {
        global $OUTPUT;
        $course = $this->coursebyshortname($shortname);
        $context = \context_course::instance($course->id);
        require_capability('moodle/course:setcurrentsection', $context);

        $setsectionnumber = empty($highlight) ? 0 : $sectionnumber;

        course_set_marker($course->id, $setsectionnumber);
        $course->marker = $setsectionnumber;
        $modinfo = get_fast_modinfo($course);

        if ($highlight) {
            $section = $modinfo->get_section_info(0);
        } else {
            $section = $modinfo->get_section_info($sectionnumber);
        }

        $actionmodel = new \theme_snap\renderables\course_action_section_highlight($course, $section);
        $toc = new \theme_snap\renderables\course_toc($course);
        return [
            'actionmodel' => $actionmodel->export_for_template($OUTPUT),
            'toc' => $toc->export_for_template($OUTPUT)
        ];
    }

    /**
     * Set the visibility of a section.
     * @param string $shortname
     * @param int $sectionnumber
     * @param boolean $visible
     * @param bool $loadmodules Should modules be loaded.
     * @return array
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function set_section_visibility($shortname, $sectionnumber, $visible, $loadmodules = true) {
        global $OUTPUT;
        $course = $this->coursebyshortname($shortname);
        $context = \context_course::instance($course->id);
        require_capability('moodle/course:sectionvisibility', $context);
        // Note, we do not use the return value of set_section_visible (resourcestotoggle) as nested resource visibility
        // is handled via CSS.
        set_section_visible($course->id, $sectionnumber, $visible);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info($sectionnumber);
        $actionmodel = new \theme_snap\renderables\course_action_section_visibility($course, $section);

        $nullformat = null;
        $toc = new \theme_snap\renderables\course_toc($course, $nullformat, $loadmodules);

        return [
            'actionmodel' => $actionmodel->export_for_template($OUTPUT),
            'toc' => $toc->export_for_template($OUTPUT)
        ];
    }

    /**
     * Delete a section.
     * @param string $shortname
     * @param int $sectionnumber
     */
    public function delete_section($shortname, $sectionnumber) {
        global $OUTPUT;
        $course = $this->coursebyshortname($shortname);
        $context = \context_course::instance($course->id);
        require_capability('moodle/course:sectionvisibility', $context);
        // Note, we do not use the return value of set_section_visible (resourcestotoggle) as nested resource visibility
        // is handled via CSS.
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $modinfo->get_section_info($sectionnumber);

        if (course_can_delete_section($course, $sectioninfo)) {
            course_delete_section($course, $sectioninfo, true, true);
        }
        $toc = new \theme_snap\renderables\course_toc($course);
        return [
            'toc' => $toc->export_for_template($OUTPUT)
        ];
    }

    /**
     * Get course TOC.
     * @param string $shortname Course short name
     * @return array
     * @throws \coding_exception
     */
    public function toc($shortname) {
        global $OUTPUT;
        $course = $this->coursebyshortname($shortname);

        $nullformat = null;
        $loadmodules = true;
        $toc = new \theme_snap\renderables\course_toc($course, $nullformat, $loadmodules);

        return [
            'toc' => $toc->export_for_template($OUTPUT)
        ];
    }


    /**
     * Toggle module completion state.
     * @param int $id (cmid)
     * @param int $completionstate
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws moodle_exception
     * @return string
     */
    public function module_toggle_completion($id, $completionstate) {
        global $DB, $PAGE;

        // Get course-modules entry.
        [$course, $cminfo] = get_course_and_cm_from_cmid($id);

        // Get renderer for completion HTML.
        $context = \context_module::instance($id);
        $PAGE->set_context($context);
        $renderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);

        // Set up completion object and check it is enabled.
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            throw new \moodle_exception('completionnotenabled', 'completion');
        }

        // Check completion state is manual.
        if ($cminfo->completion != COMPLETION_TRACKING_MANUAL) {
            throw new \moodle_exception('cannotmanualctrack', $cminfo->modname);
        }

        $completion->update_state($cminfo, $completionstate);

        return $renderer->course_section_cm_completion($course, $completion, $cminfo);
    }
}
