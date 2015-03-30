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


namespace theme_snap;

use html_writer;

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/user/lib.php');

/**
 * General local snap functions.
 *
 * Added to a class purely for the convenience of auto loading.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local {


    /**
     * If debugging enabled in config then return reason for no grade (useful for json output).
     *
     * @param $warning
     * @return null|object
     */
    public static function skipgradewarning($warning) {
        global $CFG;
        if (!empty($CFG->debugdisplay)) {
            return (object) array ('skipgrade' => $warning);
        } else {
            return null;
        }
    }

    /**
     * Is there a valid grade or feedback inside this grader report table item?
     *
     * @param $item
     * @return bool
     */
    public static function item_has_grade_or_feedback($item) {
        $typekeys = array ('grade', 'feedback');
        foreach ($typekeys as $typekey) {
            if (!empty($item[$typekey]['content'])) {
                // Set grade content to null string if it contents - or a blank space.
                $item[$typekey]['content'] = str_ireplace(array('-', '&nbsp;'), '', $item[$typekey]['content']);
            }
            // Is there an error message in the content (can't check on message as it is localized,
            // so check on the class for gradingerror.
            if (!empty($item[$typekey]['content'])
                && stripos($item[$typekey]['class'], 'gradingerror') === false
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does this course have any visible feedback for current user?.
     *
     * @param $course
     * @return stdClass | null
     */
    public static function course_feedback($course) {
        global $USER;
        // Get course context.
        $coursecontext = \context_course::instance($course->id);
        // Security check - should they be allowed to see course grade?
        $onlyactive = true;
        if (!is_enrolled($coursecontext, $USER, 'moodle/grade:view', $onlyactive)) {
            return self::skipgradewarning('User not enrolled on course with capability moodle/grade:view');
        }
        // Security check - are they allowed to see the grade report for the course?
        if (!has_capability('gradereport/user:view', $coursecontext)) {
            return self::skipgradewarning('User does not have required course capability gradereport/user:view');
        }
        // See if user can view hidden grades for this course.
        $canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);
        // Do not show grade if grade book disabled for students.
        // Note - moodle/grade:viewall is a capability held by teachers and thus used to exclude them from not getting
        // the grade.
        if (empty($course->showgrades) && !has_capability('moodle/grade:viewall', $coursecontext)) {
            return self::skipgradewarning('Course set up to not show gradebook to students');
        }
        // Get course grade_item.
        $courseitem = \grade_item::fetch_course_item($course->id);
        // Get the stored grade.
        $coursegrade = new \grade_grade(array('itemid' => $courseitem->id, 'userid' => $USER->id));
        $coursegrade->grade_item =& $courseitem;
        // Return null if can't view.
        if ($coursegrade->is_hidden() && !$canviewhidden) {
            return self::skipgradewarning('Course grade is hidden from students');
        }
        // Use user grade report to get course total - this is to take hidden grade settings into account.
        $gpr = new \grade_plugin_return(array(
                'type' => 'report',
                'plugin' => 'user',
                'courseid' => $course->id,
                'userid' => $USER->id)
        );
        $report = new \grade_report_user($course->id, $gpr, $coursecontext, $USER->id);
        $report->fill_table();
        $visiblegradefound = false;
        foreach ($report->tabledata as $item) {
            if (self::item_has_grade_or_feedback($item)) {
                $visiblegradefound = true;
                break;
            }
        }
        $feedbackhtml = '';
        if ($visiblegradefound) {
            // Just output - feedback available.
            $url = new \moodle_url('/grade/report/user/index.php', array('id' => $course->id));
            $feedbackhtml = \html_writer::link($url,
                get_string('feedbackavailable', 'theme_snap'),
                array('class' => 'coursegrade')
            );
        }
        return (object) array('feedbackhtml' => $feedbackhtml);
    }

    /**
     * Get course completion progress for specific course.
     * NOTE: It is by design that even teachers get course completion progress, this is so that they see exactly the
     * same as a student would in the personal menu.
     *
     * @param $course
     * @return stdClass | null
     */
    public static function course_completion_progress($course) {
        if (!isloggedin() || isguestuser()) {
            return null; // Can't get completion progress for users who aren't logged in.
        }

        // Security check - are they enrolled on course.
        $context = \context_course::instance($course->id);
        if (!is_enrolled($context, null, '', true)) {
            return null;
        }
        $completioninfo = new \completion_info($course);
        $trackcount = 0;
        $compcount = 0;
        if ($completioninfo->is_enabled()) {
            $modinfo = get_fast_modinfo($course);

            foreach ($modinfo->cms as $thismod) {
                if (!$thismod->uservisible) {
                    // Skip when mod is not user visible.
                    continue;
                }
                $completioninfo->get_data($thismod, true);

                if ($completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $trackcount++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $compcount++;
                    }
                }
            }
        }

        $compobj = (object) array('complete' => $compcount, 'total' => $trackcount, 'progresshtml' => '');
        if ($trackcount > 0) {
            $progress = get_string('progresstotal', 'completion', $compobj);
            // TODO - we should be putting our HTML in a renderer.
            $progressinfo = '<div class="completionstatus outoftotal">'.$progress.'</div>';
            $compobj->progresshtml = $progressinfo;
        }

        return $compobj;
    }

    /**
     * Get information for array of courseids
     *
     * @param $courseids
     * @return bool | array
     */
    public static function courseinfo($courseids) {
        global $DB;
        $courseinfo = array();
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', array('id' => $courseid));

            $context = \context_course::instance($courseid);
            if (!is_enrolled($context, null, '', true)) {
                // Skip this course, don't have permission to view.
                continue;
            }

            $courseinfo[$courseid] = (object) array(
                'courseid' => $courseid,
                'progress' => self::course_completion_progress($course),
                'feedback' => self::course_feedback($course)
            );
        }
        return $courseinfo;
    }

    /**
     * Get total participant count for specific courseid.
     *
     * @param $courseid
     * @return int
     */
    public static function course_participant_count($courseid) {
        static $participantcount = array();

        if (!isset($participantcount[$courseid])) {
            $context = \context_course::instance($courseid);
            $onlyactive = true;
            $capability = 'mod/assign:submit';
            $enrolled = count_enrolled_users($context, $capability, null, $onlyactive);
            $participantcount[$courseid] = $enrolled;
        }

        return $participantcount[$courseid];

    }

    /**
     * Get a user's messages read and unread.
     *
     * @param int $userid
     * @return message[]
     */

    public static function get_user_messages($userid) {
        global $DB;

        $select  = 'm.id, m.useridfrom, m.useridto, m.subject, m.fullmessage, m.fullmessageformat, m.fullmessagehtml, '.
                   'm.smallmessage, m.timecreated, m.notification, m.contexturl, m.contexturlname, '.
                   \user_picture::fields('u', null, 'useridfrom', 'fromuser');

        $records = $DB->get_records_sql("
        (
                SELECT $select, 1 as 'unread'
                  FROM {message} m
            INNER JOIN {user} u ON u.id = m.useridfrom
                 WHERE m.useridto = ?
                       AND contexturl IS NULL
        ) UNION ALL (
                SELECT $select, 0 as 'unread'
                  FROM {message_read} m
            INNER JOIN {user} u ON u.id = m.useridfrom
                 WHERE m.useridto = ?
                       AND contexturl IS NULL
        )
          ORDER BY timecreated DESC
        ", array($userid, $userid), 0, 5);

        $messages = array();
        foreach ($records as $record) {
            $message = new message($record);
            $message->set_fromuser(\user_picture::unalias($record, null, 'useridfrom', 'fromuser'));

            $messages[] = $message;
        }
        return $messages;
    }

    /**
     * Get message html for current user
     * TODO: This should not be in here - HTML does not belong in this file!
     *
     * @return string
     */
    public static function messages() {
        global $USER, $PAGE;

        $messages = self::get_user_messages($USER->id);
        if (empty($messages)) {
            return '<p>' . get_string('nomessages', 'theme_snap') . '</p>';
        }

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $o = '';
        foreach ($messages as $message) {
            $url = new \moodle_url('/message/index.php', array(
                'history' => 0,
                'user1' => $message->useridto,
                'user2' => $message->useridfrom,
            ));

            $fromuser = $message->get_fromuser();
            $userpicture = new \user_picture($fromuser);
            $userpicture->link = false;
            $userpicture->alttext = false;
            $userpicture->size = 100;
            $frompicture = $output->render($userpicture);

            $fromname = format_string(fullname($fromuser));

            $meta = self::relative_time($message->timecreated);
            $unreadclass = '';
            if ($message->unread) {
                $unreadclass = ' snap-unread';
                $meta .= " <span class=snap-unread-marker>".get_string('unread', 'theme_snap')."</span>";
            }

            $info = '<p>'.format_string($message->smallmessage).'</p>';

            $o .= $output->snap_media_object($url, $frompicture, $fromname, $meta, $info, $unreadclass);
        }
        return $o;
    }

    /**
     * Return friendly relative time (e.g. "1 min ago", "1 year ago") in a <time> tag
     * @return string
     */
    public static function relative_time($timeinpast, $relativeto = null) {
        if ($relativeto === null) {
            $relativeto = time();
        }
        $secondsago = $relativeto - $timeinpast;
        $secondsago = self::simpler_time($secondsago);

        $relativetext = format_time($secondsago);
        if ($secondsago != 0) {
            $relativetext = get_string('ago', 'message', $relativetext);
        }
        $datetime = date(\DateTime::W3C, $timeinpast);
        return html_writer::tag('time', $relativetext, array(
            'is' => 'relative-time',
            'datetime' => $datetime)
        );
    }

    /**
     * Reduce the precision of the time e.g. 1 min 10 secs ago -> 1 min ago
     * @return int
     */
    public static function simpler_time($seconds) {
        if ($seconds > 59) {
            return intval(round($seconds / 60)) * 60;
        } else {
            return $seconds;
        }
    }


    /**
     * Return user's upcoming deadlines from the calendar.
     *
     * All deadlines from today, then any from the next 12 months up to the
     * max requested.
     * @param integer $userid
     * @param integer $maxdeadlines
     * @return array
     */
    public static function upcoming_deadlines($userid, $maxdeadlines = 5) {

        $courses = enrol_get_all_users_courses($userid);

        if (empty($courses)) {
            return array();
        }

        $courseids = array_keys($courses);

        $events = self::get_todays_deadlines($courseids);

        if (count($events) < $maxdeadlines) {
            $maxaftercurrentday = $maxdeadlines - count($events);
            $moreevents = self::get_upcoming_deadlines($courseids, $maxaftercurrentday);
            $events = $events + $moreevents;
        }
        foreach ($events as $event) {
            if (isset($courses[$event->courseid])) {
                $course = $courses[$event->courseid];
                $event->coursefullname = $course->fullname;
            }
        }
        return $events;
    }

    /**
     * Return user's deadlines for today from the calendar.
     *
     * @param array $courses ids of all user's courses.
     * @return array
     */
    private static function get_todays_deadlines($courses) {
        // Get all deadlines for today, assume that will never be higher than 100.
        return self::get_upcoming_deadlines($courses, 100, true);
    }

    /**
     * Return user's deadlines from the calendar.
     *
     * Usually called twice, once for all deadlines from today, then any from the next 12 months up to the
     * max requested.
     *
     * Based on the calender function calendar_get_upcoming.
     *
     * @param array $courses ids of all user's courses.
     * @param int $maxevents to return
     * @param bool $todayonly true if only the next 24 hours to be returned
     * @return array
     */
    private static function get_upcoming_deadlines($courses, $maxevents, $todayonly=false) {

        $now = time();

        if ($todayonly === true) {
            $starttime = usergetmidnight($now);
            $daysinfuture = 1;
        } else {
            $starttime = usergetmidnight($now + DAYSECS + 3 * HOURSECS); // Avoid rare DST change issues.
            $daysinfuture = 365;
        }

        $endtime = $starttime + ($daysinfuture * DAYSECS) - 1;

        $userevents = false;
        $groupevents = false;
        $events = calendar_get_events($starttime, $endtime, $userevents, $groupevents, $courses);

        $processed = 0;
        $output = array();
        foreach ($events as $event) {
            if ($event->eventtype === 'course') {
                // Not an activity deadline.
                continue;
            }
            if (!empty($event->modulename)) {
                $modinfo = get_fast_modinfo($event->courseid);
                $mods = $modinfo->get_instances_of($event->modulename);
                if (isset($mods[$event->instance])) {
                    $cminfo = $mods[$event->instance];
                    if (!$cminfo->uservisible) {
                        continue;
                    }
                }
            }

            $output[$event->id] = $event;
            ++$processed;

            if ($processed >= $maxevents) {
                break;
            }
        }

        return $output;
    }




    public static function deadlines() {
        global $USER, $PAGE;

        $events = self::upcoming_deadlines($USER->id);
        if (empty($events)) {
            return '<p>' . get_string('nodeadlines', 'theme_snap') . '</p>';
        }

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $o = '';
        foreach ($events as $event) {
            if (!empty($event->modulename)) {
                $modinfo = get_fast_modinfo($event->courseid);
                $cm = $modinfo->instances[$event->modulename][$event->instance];

                $eventtitle = "<small>$event->coursefullname / </small> $event->name";

                $modimageurl = $output->pix_url('icon', $event->modulename);
                $modname = get_string('modulename', $event->modulename);
                $modimage = \html_writer::img($modimageurl, $modname);

                $meta = $output->friendly_datetime($event->timestart);

                $o .= $output->snap_media_object($cm->url, $modimage, $eventtitle, $meta, '');
            }
        }
        return $o;
    }

    public static function graded() {
        global $USER, $PAGE;

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $grades = activity::events_graded();

        $o = '';
        foreach ($grades as $grade) {

            $modinfo = get_fast_modinfo($grade->courseid);
            $course = $modinfo->get_course();

            $modtype = $grade->itemmodule;
            $cm = $modinfo->instances[$modtype][$grade->iteminstance];

            $coursecontext = \context_course::instance($grade->courseid);
            $canviewhiddengrade = has_capability('moodle/grade:viewhidden', $coursecontext);

            $url = new \moodle_url('/grade/report/user/index.php', ['id' => $grade->courseid]);
            if (in_array($modtype, ['quiz', 'assign'])
                && (!empty($grade->rawgrade) || !empty($grade->feedback))
            ) {
                // Only use the course module url if the activity was graded in the module, not in the gradebook, etc.
                $url = $cm->url;
            }

            $modimageurl = $output->pix_url('icon', $cm->modname);
            $modname = get_string('modulename', 'mod_'.$cm->modname);
            $modimage = \html_writer::img($modimageurl, $modname);

            $gradetitle = "<small>$course->fullname / </small> $cm->name";

            $releasedon = isset($grade->timemodified) ? $grade->timemodified : $grade->timecreated;
            $meta = get_string('released', 'theme_snap', $output->friendly_datetime($releasedon));

            $grade = new \grade_grade(array('itemid' => $grade->itemid, 'userid' => $USER->id));
            if (!$grade->is_hidden() || $canviewhiddengrade) {
                $o .= $output->snap_media_object($url, $modimage, $gradetitle, $meta, '');
            }
        }

        if (empty($o)) {
            return '<p>'. get_string('nograded', 'theme_snap') . '</p>';
        }
        return $o;
    }

    public static function grading() {
        global $USER, $PAGE;

        $grading = self::all_ungraded($USER->id);

        if (empty($grading)) {
            return '<p>' . get_string('nograding', 'theme_snap') . '</p>';
        }

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $out = '';
        foreach ($grading as $ungraded) {
            $modinfo = get_fast_modinfo($ungraded->course);
            $course = $modinfo->get_course();
            $cm = $modinfo->get_cm($ungraded->coursemoduleid);

            $modimageurl = $output->pix_url('icon', $cm->modname);
            $modname = get_string('modulename', 'mod_'.$cm->modname);
            $modimage = \html_writer::img($modimageurl, $modname);

            $ungradedtitle = "<small>$course->fullname / </small> $cm->name";

            $xungraded = get_string('xungraded', 'theme_snap', $ungraded->ungraded);

            $function = '\theme_snap\activity::'.$cm->modname.'_num_submissions';

            $a['completed'] = call_user_func($function, $ungraded->course, $ungraded->instanceid);
            $a['participants'] = (self::course_participant_count($ungraded->course));
            $xofysubmitted = get_string('xofysubmitted', 'theme_snap', $a);
            $info = '<span class="label label-info">'.$xofysubmitted.', '.$xungraded.'</span>';

            $meta = '';
            if (!empty($ungraded->closetime)) {
                $meta = $output->friendly_datetime($ungraded->closetime);
            }

            $out .= $output->snap_media_object($cm->url, $modimage, $ungradedtitle, $meta, $info);
        }

        return $out;
    }

    public static function all_ungraded($userid) {

        $courses = enrol_get_all_users_courses($userid);

        $capability = 'gradereport/grader:view';
        foreach ($courses as $course) {
            if (has_capability($capability, \context_course::instance($course->id), $userid)) {
                $courseids[] = $course->id;
            }
        }
        if (empty($courseids)) {
            return array();
        }

        $mods = \core_plugin_manager::instance()->get_installed_plugins('mod');
        $mods = array_keys($mods);

        $grading = [];
        foreach ($mods as $mod) {
            $class = '\theme_snap\activity';
            $method = $mod.'_ungraded';
            if (method_exists($class, $method)) {
                $grading = array_merge($grading, call_user_func([$class, $method], $courseids));
            }
        }

        usort($grading, array('self', 'sort_graded'));

        return $grading;
    }

    /**
     * Sort function for ungraded items in the teachers personal menu.
     *
     * Compare on closetime, but fall back to openening time if not present.
     * Finally, sort by unique coursemodule id when the dates match.
     *
     * @return int
     */
    public static function sort_graded($left, $right) {
        if (empty($left->closetime)) {
            $lefttime = $left->opentime;
        } else {
            $lefttime = $left->closetime;
        }

        if (empty($right->closetime)) {
            $righttime = $right->opentime;
        } else {
            $righttime = $right->closetime;
        }

        if ($lefttime === $righttime) {
            if ($left->coursemoduleid === $right->coursemoduleid) {
                return 0;
            } else if ($left->coursemoduleid < $right->coursemoduleid) {
                return -1;
            } else {
                return 1;
            }
        } else if ($lefttime < $righttime) {
            return  -1;
        } else {
            return 1;
        }
    }

    /**
     * get hex color based on hash of course id
     *
     * @return string
     */
    public static function get_course_color($id) {
        return substr(md5($id), 0, 6);
    }

    public static function get_course_firstimage($courseid) {
        $fs      = get_file_storage();
        $context = \context_course::instance($courseid);
        $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);

        if (count($files) > 0) {
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    return $file;
                }
            }
        }

        return false;
    }



    /**
     * Extract first image from html
     *
     * @param string $html (must be well formed)
     * @return array | bool (false)
     */
    public static function extract_first_image($html) {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true); // Required for HTML5.
        $doc->loadHTML($html);
        libxml_clear_errors(); // Required for HTML5.
        $imagetags = $doc->getElementsByTagName('img');
        if ($imagetags->item(0)) {
            $src = $imagetags->item(0)->getAttribute('src');
            $alt = $imagetags->item(0)->getAttribute('alt');
            return array('src' => $src, 'alt' => $alt);
        } else {
            return false;
        }
    }


    /**
     * Make url based on file for theme_snap components only.
     *
     * @param stored_file $file
     * @return \moodle_url | bool
     */
    private static function snap_pluginfile_url($file) {
        if (!$file) {
            return false;
        } else {
            return \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_timemodified(), // Used as a cache buster.
                $file->get_filepath(),
                $file->get_filename()
            );
        }
    }

    /**
     * Get cover image for context
     *
     * @param $context
     * @return bool|stored_file
     * @throws \coding_exception
     */
    public static function coverimage($context) {
        $contextid = $context->id;
        $fs = get_file_storage();

        $files = $fs->get_area_files($contextid, 'theme_snap', 'coverimage', 0, "itemid, filepath, filename", false);
        if (!$files) {
            return false;
        }
        if (count($files) > 1) {
            throw new \coding_exception('Multiple files found in course coverimage area (context '.$contextid.')');
        }
        return (end($files));
    }

    /**
     * Get processed course cover image.
     *
     * @param $courseid
     * @return stored_file|bool
     */
    public static function course_coverimage($courseid) {
        $context = \context_course::instance($courseid);
        return (self::coverimage($context));
    }

    /**
     * Get cover image url for course.
     *
     * @return bool|moodle_url
     */
    public static function course_coverimage_url($courseid) {
        $file = self::course_coverimage($courseid);
        if (!$file) {
            $file = self::process_coverimage(\context_course::instance($courseid));
        }
        return self::snap_pluginfile_url($file);
    }

    /**
     * Get processed site cover image.
     *
     * @return stored_file|bool
     */
    public static function site_coverimage() {
        $context = \context_system::instance();
        return (self::coverimage($context));
    }

    /**
     * Get cover image url for front page.
     *
     * @return bool|moodle_url
     */
    public static function site_coverimage_url() {
        $file = self::site_coverimage();
        return self::snap_pluginfile_url($file);
    }

    /**
     * Get original site cover image file.
     *
     * @return stored_file | bool (false)
     */
    public static function site_coverimage_original() {
        $theme = \theme_config::load('snap');
        $filename = $theme->settings->poster;
        if ($filename) {
            $syscontextid = \context_system::instance()->id;
            $fullpath = "/$syscontextid/theme_snap/poster/0$filename";
            $fs = get_file_storage();
            return $fs->get_file_by_hash(sha1($fullpath));
        } else {
            return false;
        }
    }


    /**
     * Adds the course cover image to CSS.
     *
     * @param int $courseid
     * @return string The parsed CSS
     */
    public static function course_coverimage_css($courseid) {
        $css = '';
        $coverurl = self::course_coverimage_url($courseid);
        if ($coverurl) {
            $css = "#page-header {background-image: url($coverurl);}";
        }
        return $css;
    }

    /**
     * Adds the site cover image to CSS.
     *
     * @param string $css The CSS to process.
     * @return string The parsed CSS
     */
    public static function site_coverimage_css($css) {
        $tag = '[[setting:poster]]';
        $replacement = '';

        $coverurl = self::site_coverimage_url();
        if ($coverurl) {
            $replacement = "#page-site-index #page-header {background-image: url($coverurl);}";
        }

        $css = str_replace($tag, $replacement, $css);
        return $css;
    }

    /**
     * Copy coverimage file to standard location and name.
     *
     * @param stored_file $file
     * @return stored_file|bool
     */
    public static function process_coverimage($context) {
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $originalfile = self::site_coverimage_original($context);
            $newfilename = "site-image";
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $originalfile = self::get_course_firstimage($context->instanceid);
            $newfilename = "course-image";
        } else {
            throw new \coding_exception('Invalid context passed to process_coverimage');
        }

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'theme_snap', 'coverimage');

        if (!$originalfile) {
            return false;
        }

        $filename = $originalfile->get_filename();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $newfilename .= '.'.$extension;

        $filespec = array(
            'contextid' => $context->id,
            'component' => 'theme_snap',
            'filearea' => 'coverimage',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $newfilename,
        );

        $newfile = $fs->create_file_from_storedfile($filespec, $originalfile);
        $finfo = $newfile->get_imageinfo();

        if ($finfo['mimetype'] == 'image/jpeg' && $finfo['width'] > 1380) {
            return image::resize($newfile, false, 1280);
        } else {
            return $newfile;
        }
    }
}
