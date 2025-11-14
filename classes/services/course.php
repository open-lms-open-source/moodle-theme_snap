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
     * @param string $croppedimagedata
     */
    public function savecroppedimage($context, $croppedimagedata, $ext = null, $originalimageurl = null) {

        $image_parts = explode(";base64,", $croppedimagedata);
        $image_base64 = base64_decode($image_parts[1]);
        if (empty($ext)) {
            $fs = get_file_storage();
            $originalfile = $fs->get_area_files($context->id, 'theme_snap', 'coverimage', 0, "itemid, filepath, filename", false);
            if ($originalfile) {
                $originalfilename = reset($originalfile)->get_filename();
                $ext = strtolower(pathinfo($originalfilename, PATHINFO_EXTENSION));
            } else if ($originalimageurl !== null) {
                $ext = strtolower(pathinfo($originalimageurl, PATHINFO_EXTENSION));
            }
        }

        if ($context->contextlevel === CONTEXT_COURSE) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea'  => 'croppedimage',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'course-image-cropped.'.$ext,
            ];
        } else if ($context->contextlevel === CONTEXT_COURSECAT) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea'  => 'croppedimage',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'category-image-cropped.'.$ext,
            ];
        } else if ($context->contextlevel === CONTEXT_SYSTEM) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea'  => 'croppedimage',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'site-image-cropped.'.$ext,
            ];
        }

        // Copy file to temp directory.
        $tmpimage = tempnam(sys_get_temp_dir(), 'tmpimg');
        file_put_contents($tmpimage, $image_base64);
        $fs = get_file_storage();
        $fs->create_file_from_pathname($filerecord, $tmpimage);
    }

    /**
     * @param \context $context
     * @param string $data
     * @param string $filename
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function setcoverimage(\context $context, $filename, $fileid, $croppedimagedata) {

        global $CFG, $USER;

        require_capability('moodle/course:changesummary', $context);

        $fs = get_file_storage();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        if (!file_extension_in_typegroup($filename, 'web_image')) {
            return ['success' => false, 'warning' => get_string('unsupportedcoverimagetype', 'theme_snap', $ext)];
        }

        $newfilename = time().'rawcoverimage.'.$ext;

        $usercontext = \context_user::instance($USER->id);

        $filefromdraft = $fs->get_file($usercontext->id, 'user', 'draft', $fileid, '/', $filename);
        if ($filefromdraft->get_filesize() > get_max_upload_file_size($CFG->maxbytes)) {
            throw new \core\exception\moodle_exception('error:coverimageexceedsmaxbytes', 'theme_snap');
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
                'filename' => $newfilename, );

            // Remove any old course summary image files for this context.
            $fs->delete_area_files($context->id, $fileinfo['component'], $fileinfo['filearea']);
            // Purge course image cache in case image has been updated.
            \cache::make('core', 'course_image')->delete($context->instanceid);
        } else if ($context->contextlevel === CONTEXT_SYSTEM || $context->contextlevel === CONTEXT_COURSECAT) {
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea' => 'poster',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename, );

            // Remove everything from poster area for this context.
            $fs->delete_area_files($context->id, 'theme_snap', 'poster');
            // Purge course image cache in case image has been updated.
            \cache::make('core', 'course_image')->delete($context->instanceid);
        } else {
            throw new \core\exception\coding_exception('Unsupported context level '.$context->contextlevel);
        }

        // Create new cover image file and process it.
        $storedfile = $fs->create_file_from_storedfile($fileinfo, $filefromdraft);
        $success = $storedfile instanceof \stored_file;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            set_config('poster', $newfilename, 'theme_snap');
            local::process_coverimage($context);
            $this->savecroppedimage($context, $croppedimagedata, $ext);
            $coverimageurl = local::site_coverimage_url();
            $coverimageurl = "url($coverimageurl);";
        } else if ($context->contextlevel === CONTEXT_COURSE || $context->contextlevel === CONTEXT_COURSECAT) {
            local::process_coverimage($context, $storedfile);
            $this->savecroppedimage($context, $croppedimagedata, $ext);
            if ($context->contextlevel === CONTEXT_COURSE) {
                $coverimageurl = local::course_coverimage_url($context->instanceid);
                $coverimageurl = "url($coverimageurl);";
            } else {
                $coverimageurl = local::course_cat_coverimage_url($context->instanceid);
                $coverimageurl = "url($coverimageurl);";
            }
        }
        return ['success' => $success, 'imageurl'=> $coverimageurl];
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
     * @throws \core\exception\coding_exception
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
}
