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
use theme_snap\user_forums;

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/user/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->dirroot.'/lib/enrollib.php');

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
     * @return boolean
     */
    public static function course_feedback($course) {
        global $USER;
        // Get course context.
        $coursecontext = \context_course::instance($course->id);
        // Security check - should they be allowed to see course grade?
        $onlyactive = true;
        if (!is_enrolled($coursecontext, $USER, 'moodle/grade:view', $onlyactive)) {
            return false;
        }
        // Security check - are they allowed to see the grade report for the course?
        if (!has_capability('gradereport/user:view', $coursecontext)) {
            return false;
        }
        // See if user can view hidden grades for this course.
        $canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);
        // Do not show grade if grade book disabled for students.
        // Note - moodle/grade:viewall is a capability held by teachers and thus used to exclude them from not getting
        // the grade.
        if (empty($course->showgrades) && !has_capability('moodle/grade:viewall', $coursecontext)) {
            return false;
        }
        // Get course grade_item.
        $courseitem = \grade_item::fetch_course_item($course->id);
        // Get the stored grade.
        $coursegrade = new \grade_grade(array('itemid' => $courseitem->id, 'userid' => $USER->id));
        $coursegrade->grade_item =& $courseitem;
        // Return null if can't view.
        if ($coursegrade->is_hidden() && !$canviewhidden) {
            return false;
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

        if ($visiblegradefound) {
            return true;
        }
        return false;
    }

    /**
     * Get course categories for a specific course.
     * Based on code in moodle_page class - functions set_category_by_id and load_category.
     * @param stdClass $course
     * @return array
     * @throws moodle_exception
     */
    public static function get_course_categories($course) {
        global $DB;

        if ($course->id === SITEID) {
            return [];
        }

        $categories = [];
        $category = $DB->get_record('course_categories', array('id' => $course->category));
        if (!$category) {
            throw new \moodle_exception('unknowncategory');
        }
        $categories[$category->id] = $category;
        $parentcategoryids = explode('/', trim($category->path, '/'));
        array_pop($parentcategoryids);
        foreach (array_reverse($parentcategoryids) as $catid) {
            $categories[$catid] = null;
        }

        // Load up all parent categories.
        $idstoload = array_keys($categories);
        array_shift($idstoload);
        $parentcategories = $DB->get_records_list('course_categories', 'id', $idstoload);
        foreach ($idstoload as $catid) {
            $categories[$catid] = $parentcategories[$catid];
        }

        return $categories;
    }

    /**
     * This has been taken directly from the moodle_page class but modified to work independently.
     * It's used by config.php so that hacks can be targetted at just the snap theme.
     * Work out the theme this page should use.
     *
     * This depends on numerous $CFG settings, and the properties of this page.
     *
     * @return string the name of the theme that should be used on this page.
     */
    public static function resolve_theme() {
        global $CFG, $USER, $SESSION, $COURSE;

        if (empty($CFG->themeorder)) {
            $themeorder = array('course', 'category', 'session', 'user', 'site');
        } else {
            $themeorder = $CFG->themeorder;
            // Just in case, make sure we always use the site theme if nothing else matched.
            $themeorder[] = 'site';
        }

        $mnetpeertheme = '';
        if (isloggedin() and isset($CFG->mnet_localhost_id) and $USER->mnethostid != $CFG->mnet_localhost_id) {
            require_once($CFG->dirroot.'/mnet/peer.php');
            $mnetpeer = new \mnet_peer();
            $mnetpeer->set_id($USER->mnethostid);
            if ($mnetpeer->force_theme == 1 && $mnetpeer->theme != '') {
                $mnetpeertheme = $mnetpeer->theme;
            }
        }

        $deviceinuse = \core_useragent::get_device_type();
        $devicetheme = \core_useragent::get_device_type_theme($deviceinuse);

        // The user is using another device than default, and we have a theme for that, we should use it.
        $hascustomdevicetheme = \core_useragent::DEVICETYPE_DEFAULT != $deviceinuse && !empty($devicetheme);

        foreach ($themeorder as $themetype) {
            switch ($themetype) {
                case 'course':
                    if (!empty($CFG->allowcoursethemes) && !empty($COURSE->theme) && !$hascustomdevicetheme) {
                        return $COURSE->theme;
                    }
                    break;

                case 'category':
                    if (!empty($CFG->allowcategorythemes) && !$hascustomdevicetheme) {
                        $categories = self::get_course_categories($COURSE);
                        foreach ($categories as $category) {
                            if (!empty($category->theme)) {
                                return $category->theme;
                            }
                        }
                    }
                    break;

                case 'session':
                    if (!empty($SESSION->theme)) {
                        return $SESSION->theme;
                    }
                    break;

                case 'user':
                    if (!empty($CFG->allowuserthemes) && !empty($USER->theme) && !$hascustomdevicetheme) {
                        if ($mnetpeertheme) {
                            return $mnetpeertheme;
                        } else {
                            return $USER->theme;
                        }
                    }
                    break;

                case 'site':
                    if ($mnetpeertheme) {
                        return $mnetpeertheme;
                    }
                    // First try for the device the user is using.
                    if (!empty($devicetheme)) {
                        return $devicetheme;
                    }
                    // Next try for the default device (as a fallback).
                    $devicetheme = \core_useragent::get_device_type_theme(\core_useragent::DEVICETYPE_DEFAULT);
                    if (!empty($devicetheme)) {
                        return $devicetheme;
                    }
                    // The default device theme isn't set up - use the overall default theme.
                    return \theme_config::DEFAULT_THEME;
            }
        }

        // We should most certainly have resolved a theme by now. Something has gone wrong.
        debugging('Error resolving the theme to use for this page.', DEBUG_DEVELOPER);
        return \theme_config::DEFAULT_THEME;
    }

    /**
     * Generate or get course completion cache stamp for key.
     * @param string $key
     * @param bool $new
     */
    protected static function gen_course_completion_cachestamp($key, $new = false) {
        $key = strval($key);
        $muc = \cache::make('theme_snap', 'course_completion_progress_ts');
        $cachestamp = $muc->get($key);
        if (!$cachestamp || $new) {
            if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                // This is here to ensure cache stamp is fresh where test code calls this function multiple times
                // within one test function.
                usleep(1);
            }
            $ts = microtime(true);
            $muc->set($key, $ts);
            return $ts;
        }
        return $cachestamp;
    }

    /**
     * Get / create completion cache stamp for specific course id.
     *
     * @param int $courseid
     * @param bool $new
     * @return float
     */
    public static function course_completion_cachestamp($courseid, $new = false) {
        return self::gen_course_completion_cachestamp(strval($courseid), $new);
    }

    /**
     * @param int $courseid
     * @param int $userid
     * @param bool $new
     * @return false|mixed
     */
    public static function course_user_completion_cachestamp($courseid, $userid, $new = false) {
        return self::gen_course_completion_cachestamp($courseid.'_'.$userid, $new);
    }

    /**
     * Get course completion progress for specific course.
     * NOTE: It is by design that even teachers get course completion progress, this is so that they see exactly the
     * same as a student would in the personal menu.
     *
     * @param $course - a course current user is enrolled on (enrollment check should be done outside of this function
     * for performance reasons).
     * @return stdClass
     */
    public static function course_completion_progress($course) {
        global $USER, $CFG;

        // Default completion object.
        $compobj = (object) [
            'complete' => null,
            'total' => null,
            'progress' => null,
            'fromcache' => false, // Useful for debugging and unit testing.
            'render' => false // Template flag.
        ];

        if (!isloggedin() || isguestuser() || !$CFG->enablecompletion || !$course->enablecompletion) {
            // Can't get completion progress for users who aren't logged in.
            // Or if completion tracking is not enabled at site / course level.
            // Don't even bother with the cache, just return empty object.
            return $compobj;
        }

        // Course cache stamp is used to invalidate user session caches if an application level event occurs -
        // e.g. course completion settings updated, new module added, module deleted, etc.
        $coursestamp = self::course_completion_cachestamp($course->id);

        // Course user cache stamp is used to invalidate user session caches if an event occurs which affects this
        // user - e.g. A teacher grades this users assignment and that triggers completion.
        $courseuserstamp = self::course_user_completion_cachestamp($course->id, $USER->id);

        /** @var \cache_session $muc */
        $muc = \cache::make('theme_snap', 'course_completion_progress');
        $cached = $muc->get($course->id.'_'.$USER->id);
        if ($cached && $cached->timestamp >= $coursestamp && $cached->timestamp >= $courseuserstamp) {
            $cached->fromcache = true; // Useful for debugging and unit testing.
            return $cached;
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

        if ($trackcount > 0) {
            $progresspercent = ceil(($compcount / $trackcount) * 100);
            $compobj = (object) [
                'complete' => $compcount,
                'total' => $trackcount,
                'progress' => $progresspercent,
                'timestamp' => microtime(true),
                'fromcache' => false,
                'render' => true
            ];
        } else {
            // Everything except timestamp is null because nothing is trackable at the moment.
            // We still want to cache this though to avoid repeated unnecessary db calls.
            $compobj->timestamp = microtime(true);
        }

        // There wasn't anything in the cache we could use, so lets add an entry to the cache that we can use later.
        $muc->set($course->id.'_'.$USER->id, $compobj);

        return $compobj;
    }

    /**
     * Return conditionally unavailable elements.
     * @param $course
     * @return array
     * @throws \coding_exception
     */
    public static function conditionally_unavailable_elements($course) {
        $cancomplete = isloggedin() && !isguestuser();
        $unavailablesections = [];
        $unavailablemods = [];
        $information = '';
        if ($cancomplete) {
            $completioninfo = new \completion_info($course);
            if ($completioninfo->is_enabled()) {
                $modinfo = get_fast_modinfo($course);
                $sections= $modinfo->get_section_info_all();
                foreach ($sections as $number => $section) {
                    $ci = new \core_availability\info_section($section);
                    if (!$ci->is_available($information, true)) {
                        $unavailablesections[] = $number;
                    }
                }
                foreach ($modinfo->get_cms() as $mod) {
                    $ci = new \core_availability\info_module($mod);
                    if (!$ci->is_available($information, true)) {
                        $unavailablemods[] = $mod->id;
                    }
                }
            }
        }
        return [$unavailablesections, $unavailablemods];
    }

    /**
     * Get information for array of courseids
     *
     * @param $courseids
     * @return bool | array
     */
    public static function courseinfo($courseids) {
        $courseinfo = array();

        $courses = enrol_get_my_courses(['enablecompletion', 'showgrades']);

        foreach ($courseids as $courseid) {
            if (!isset($courses[$courseid])) {
                // Don't throw an error, just carry on.
                continue;
            }
            $course = $courses[$courseid];

            $feedbackurl = new \moodle_url('/grade/report/user/index.php', array('id' => $course->id));

            $courseinfo[$courseid] = (object) array(
                'course' => $courseid,
                'completion' => self::course_completion_progress($course),
                'feedback' => self::course_feedback($course),
                'feedbackurl' => $feedbackurl->out()
            );
        }
        return $courseinfo;
    }

    /**
     * Get total participant count for specific courseid.
     *
     * @param $courseid
     * @param $modname the name of the module, used to build a capability check
     * @return int
     */
    public static function course_participant_count($courseid, $modname = null) {
        static $participantcount = array();

        // Incorporate the modname in the static cache index.
        $idx = $courseid . $modname;

        if (!isset($participantcount[$idx])) {
            // Use the modname to determine the best capability.
            switch ($modname) {
                case 'assign':
                    $capability = 'mod/assign:submit';
                    break;
                case 'quiz':
                    $capability = 'mod/quiz:attempt';
                    break;
                case 'choice':
                    $capability = 'mod/choice:choose';
                    break;
                case 'feedback':
                    $capability = 'mod/feedback:complete';
                    break;
                default:
                    // If no modname is specified, assume a count of all users is required.
                    $capability = '';
            }

            $context = \context_course::instance($courseid);
            $onlyactive = true;
            $enrolled = count_enrolled_users($context, $capability, null, $onlyactive);
            $participantcount[$idx] = $enrolled;
        }

        return $participantcount[$idx];
    }

    /**
     * Get a user's messages read and unread.
     *
     * @param int $userid
     * @param int $since optional timestamp, only return newer messages
     * @return message[]
     */

    public static function get_user_messages($userid, $since = null) {
        global $DB;

        if ($since === null) {
            $since = time() - (12 * WEEKSECS);
        }

        $select  = 'm.id, m.useridfrom, m.useridto, m.subject, m.fullmessage, m.fullmessageformat, m.fullmessagehtml, '.
                   'm.smallmessage, m.timecreated, m.notification, m.contexturl, m.contexturlname, '.
                   \user_picture::fields('u', null, 'useridfrom', 'fromuser');

        $sql  = "
        (
                SELECT $select, 1 unread
                  FROM {message} m
            INNER JOIN {user} u ON u.id = m.useridfrom AND u.deleted = 0
                 WHERE m.useridto = :userid1
                       AND contexturl IS NULL
                       AND m.timecreated > :fromdate1
                       AND m.timeusertodeleted = 0
        ) UNION ALL (
                SELECT $select, 0 unread
                  FROM {message_read} m
            INNER JOIN {user} u ON u.id = m.useridfrom AND u.deleted = 0
                 WHERE m.useridto = :userid2
                       AND contexturl IS NULL
                       AND m.timecreated > :fromdate2
                       AND m.timeusertodeleted = 0
        )
          ORDER BY timecreated DESC";

        $params = array(
            'userid1' => $userid,
            'userid2' => $userid,
            'fromdate1' => $since,
            'fromdate2' => $since,
        );

        $records = $DB->get_records_sql($sql, $params, 0, 5);

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
     * @param \stdClass|integer $userorid
     * @param integer $maxdeadlines
     * @return array
     */
    public static function upcoming_deadlines($userorid, $maxdeadlines = 5) {

        $user = self::get_user($userorid);
        if (!$user) {
            return [];
        }

        $courses = enrol_get_users_courses($user->id, true);

        if (empty($courses)) {
            return [];
        }

        $courseids = array_keys($courses);

        $events = self::get_todays_deadlines($user, $courseids);

        if (count($events) < $maxdeadlines) {
            $maxaftercurrentday = $maxdeadlines - count($events);
            $moreevents = self::get_upcoming_deadlines($user, $courseids, $maxaftercurrentday);
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
     * @param \stdClass|int $userorid
     * @param array $courses ids of all user's courses.
     * @return array
     */
    private static function get_todays_deadlines($userorid, $courses) {
        // Get all deadlines for today, assume that will never be higher than 100.
        return self::get_upcoming_deadlines($userorid, $courses, 100, true);
    }

    /**
     * Return user's deadlines from the calendar.
     *
     * Usually called twice, once for all deadlines from today, then any from the next 12 months up to the
     * max requested.
     *
     * Based on the calender function calendar_get_upcoming.
     *
     * @param \stdClass|int $userorid
     * @param array $courses ids of all user's courses.
     * @param int $maxevents to return
     * @param bool $todayonly true if only the next 24 hours to be returned
     * @return array
     */
    private static function get_upcoming_deadlines($userorid, $courses, $maxevents, $todayonly=false) {

        $user = self::get_user($userorid);
        if (!$user) {
            return [];
        }

        // We need to do this so that we can calendar events and mod visibility for a specific user.
        self::swap_global_user($user);

        $tz = new \DateTimeZone(\core_date::get_user_timezone($user));
        $today = new \DateTime('today', $tz);
        $tomorrow = new \DateTime('tomorrow', $tz);

        if ($todayonly === true) {
            $starttime = $today->getTimestamp();
            $endtime = $tomorrow->getTimestamp()-1;
        } else {
            $starttime = $tomorrow->getTimestamp();
            $endtime = $starttime + (365 * DAYSECS) - 1;
        }

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
            if ($event->eventtype === 'open' && $event->timeduration == 0) {
                // Only the opening of multi-day event, not a deadline.
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
                    if ($event->eventtype === 'close') {
                        // Revert the addition of e.g. "(Quiz closes)" to the event name.
                        $event->name = $cminfo->name;
                    }
                }
            }

            $output[$event->id] = $event;
            ++$processed;

            if ($processed >= $maxevents) {
                break;
            }
        }

        self::swap_global_user(false);

        return $output;
    }

    /**
     * Get deadlines string.
     * @return string
     */
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

                $eventtitle = $event->name .'<small><br>' .$event->coursefullname. '</small>';

                $modimageurl = $output->pix_url('icon', $event->modulename);
                $modname = get_string('modulename', $event->modulename);
                $modimage = \html_writer::img($modimageurl, $modname);
                $deadline = $event->timestart + $event->timeduration;
                if ($event->modulename === 'quiz' || $event->modulename === 'lesson') {
                    $override = \theme_snap\activity::instance_activity_dates($event->courseid, $cm);
                    $deadline = $override->timeclose;
                }
                $meta = $output->friendly_datetime($deadline);
                // Add completion meta data for students (exclude anyone who can grade them).
                if (!has_capability('mod/assign:grade', $cm->context)) {
                    /** @var \theme_snap_core_course_renderer $courserenderer */
                    $courserenderer = $PAGE->get_renderer('core', 'course', RENDERER_TARGET_GENERAL);
                    $activitymeta = activity::module_meta($cm);
                    $meta .= '<div class="snap-completion-meta">' .
                            $courserenderer->submission_cta($cm, $activitymeta) .
                            '</div>';
                }
                $o .= $output->snap_media_object($cm->url, $modimage, $eventtitle, $meta, '');
            }
        }
        return $o;
    }

    /**
     * Get items which have been graded.
     *
     * @param bool $onlyactive - only show grades in courses actively enrolled on if true.
     * @return string
     * @throws \coding_exception
     */
    public static function graded($onlyactive = true) {
        global $USER, $PAGE;

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $grades = activity::events_graded($onlyactive);

        $o = '';
        $enabledmods = \core_plugin_manager::instance()->get_enabled_plugins('mod');
        $enabledmods = array_keys($enabledmods);
        foreach ($grades as $grade) {

            $modinfo = get_fast_modinfo($grade->courseid);
            $course = $modinfo->get_course();

            $modtype = $grade->itemmodule;
            if (!in_array($modtype, $enabledmods)) {
                continue;
            }

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

            $gradetitle = $cm->name. '<small><br>' .$course->fullname. '</small>';

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

            $ungradedtitle = $cm->name. '<small><br>' .$course->fullname. '</small>';

            $xungraded = get_string('xungraded', 'theme_snap', $ungraded->ungraded);

            $function = '\theme_snap\activity::'.$cm->modname.'_num_submissions';

            $a['completed'] = call_user_func($function, $ungraded->course, $ungraded->instanceid);
            $a['participants'] = (self::course_participant_count($ungraded->course, $cm->modname));
            $xofysubmitted = get_string('xofysubmitted', 'theme_snap', $a);
            $meta = $xofysubmitted.', '.$xungraded.'<br>';

            if (!empty($ungraded->closetime)) {
                $meta .= $output->friendly_datetime($ungraded->closetime);
            }

            $out .= $output->snap_media_object($cm->url, $modimage, $ungradedtitle, $meta, '');
        }

        return $out;
    }

    /**
     * Get courses where user has the ability to view the gradebook.
     *
     * @param int $userid
     * @return array
     * @throws \coding_exception
     */
    public static function gradeable_courseids($userid) {
        $courses = enrol_get_all_users_courses($userid);
        $courseids = [];
        $capability = 'gradereport/grader:view';
        foreach ($courses as $course) {
            if (has_capability($capability, \context_course::instance($course->id), $userid)) {
                $courseids[] = $course->id;
            }
        }
        return $courseids;
    }

    /**
     * Get all ungraded items.
     * @param int $userid
     * @param null|int $since
     * @return array
     */
    public static function all_ungraded($userid, $since = null) {
        $courseids = self::gradeable_courseids($userid);

        if (empty($courseids)) {
            return array();
        }

        if ($since === null) {
            $since = time() - (12 * WEEKSECS);
        }

        $mods = \core_plugin_manager::instance()->get_enabled_plugins('mod');
        $mods = array_keys($mods);

        $grading = [];
        foreach ($mods as $mod) {
            $class = '\theme_snap\activity';
            $method = $mod.'_ungraded';
            if (method_exists($class, $method)) {
                $grading = array_merge($grading, call_user_func([$class, $method], $courseids, $since));
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
     * Get supported cover image types.
     * @return array
     */
    public static function supported_coverimage_types() {
        global $CFG;
        $extsstr = strtolower($CFG->courseoverviewfilesext);

        // Supported file extensions.
        $extensions = explode(',', str_replace('.', '', $extsstr));
        array_walk($extensions, function($s) {trim($s); });
        // Filter out any extensions that might be in the config but not image extensions.
        $imgextensions = ['jpg', 'png', 'gif', 'svg', 'webp'];
        return array_intersect ($extensions, $imgextensions);
    }

    /**
     * Get supported cover image types as a string.
     * @return array
     */
    public static function supported_coverimage_typesstr() {
        $supportedexts = self::supported_coverimage_types();
        $extsstr = '';
        $typemaps = [
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'png'  => 'image/png',
            'svg'  => 'image/svg'
        ];
        foreach ($supportedexts as $ext) {
            if (in_array($ext, $supportedexts) && isset($typemaps[$ext])) {
                $extsstr .= $extsstr == '' ? '' : ',';
                $extsstr .= $typemaps[$ext];
            }
        }
        return $extsstr;
    }

    /**
     * Deletes all previous course card images.
     * @param int $context
     * @return void
     */
    public static function course_card_clean_up($context) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'theme_snap', 'coursecard');
    }

    /**
     * Creates a resized course card image when the cover image is too large, otherwise returns the original.
     * @param int $context
     * @param stored_file|bool $originalfile
     * @return bool|stored_file
     */
    public static function set_course_card_image($context, $originalfile) {
        if ($originalfile) {
            $finfo = $originalfile->get_imageinfo();
            $coursecardmaxwidth = 1000;
            $coursecardwidth = 720;
            if ($finfo['mimetype'] != 'image/jpeg' || $finfo['width'] <= $coursecardmaxwidth) {
                // We use the same cover image that loads up in the course home page.
                $originalfile = self::coverimage($context);
                return $originalfile;
            }
            $filename = $originalfile->get_filename();
            $id = $originalfile->get_id();
            $fs = get_file_storage();
            $cardimage = $fs->get_file($context->id, 'theme_snap', 'coursecard', 0, '/', 'course-card-'.$id.'-'.$filename);
            if ($cardimage) {
                return $cardimage;
            }
            $filespec = array(
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea' => 'coursecard',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'course-card-'.$id.'-'.$filename,
            );
            $coursecardimage = $fs->create_file_from_storedfile($filespec, $originalfile);
            $coursecardimage = image::resize($coursecardimage, false, $coursecardwidth);
            return $coursecardimage;
        }
        return false;
    }

    /**
     * Get the cover image url for the course card.
     *
     * @param int $courseid
     * @return bool|moodle_url
     */
    public static function course_card_image_url($courseid) {
        $context = \context_course::instance($courseid);
        $fs = get_file_storage();
        $cardimages = $fs->get_area_files($context->id, 'theme_snap', 'coursecard', 0, "itemid, filepath, filename", false);
        if ($cardimages) {
            return self::snap_pluginfile_url(end($cardimages));
        } else {
            $originalfile = self::get_course_firstimage($courseid);
            $cardimage = self::set_course_card_image($context, $originalfile);
            return self::snap_pluginfile_url($cardimage);
        }
    }

    /**
     * Get cover image for context
     *
     * @param \context $context
     * @return bool|stored_file
     * @throws \coding_exception
     */
    public static function coverimage($context) {
        $contextid = $context->id;
        $fs = get_file_storage();

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            if (!self::site_coverimage_original()) {
                return false;
            }
        }

        $files = $fs->get_area_files($contextid, 'theme_snap', 'coverimage', 0, "itemid, filepath, filename", false);
        if (!$files) {
            return false;
        }
        if (count($files) > 1) {
            // Note this is a coding exception and not a moodle exception because there should never be more than one
            // file in this area, where as the course summary files area can in some circumstances have more than on file.
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
            if (substr($filename, 0, 1) != '/') {
                $filename = '/'.$filename;
            }
            $syscontextid = \context_system::instance()->id;
            $fullpath = '/'.$syscontextid.'/theme_snap/poster/0'.$filename;
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
     * @return string cover image CSS
     */
    public static function site_coverimage_css() {
        $coverurl = self::site_coverimage_url();
        if (!$coverurl) {
            return '';
        }
        return "#page-site-index #page-header, #page-login-index #page {background-image: url($coverurl);}";
    }

    /**
     * Copy coverimage file to standard location and name.
     *
     * @param context $context
     * @param stored_file $originalfile
     * @return stored_file|bool
     */
    public static function process_coverimage($context, $originalfile = false) {

        $contextlevel = $context->contextlevel;
        if ($contextlevel != CONTEXT_SYSTEM && $contextlevel != CONTEXT_COURSE) {
            throw new \coding_exception('Invalid context passed to process_coverimage');
        }
        $newfilename = $contextlevel == CONTEXT_SYSTEM ? 'site-image' : 'course-image';

        if (!$originalfile) {
            if ($contextlevel == CONTEXT_SYSTEM) {
                $originalfile = self::site_coverimage_original($context);
            } else {
                $originalfile = self::get_course_firstimage($context->instanceid);
            }
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
        self::course_card_clean_up($context);
        self::set_course_card_image($context, $originalfile);
        if ($finfo['mimetype'] == 'image/jpeg' && $finfo['width'] > 1380) {
            return image::resize($newfile, false, 1280);
        } else {
            return $newfile;
        }

    }

    /**
     * Get page module instance and create a summary property.
     *
     * @param $mod
     * @return mixed
     * @throws \dml_missing_record_exception
     * @throws \dml_multiple_records_exception
     */
    public static function get_page_mod($mod) {
        global $DB;

        $sql = "SELECT * FROM {course_modules} cm
                  JOIN {page} p ON p.id = cm.instance
                WHERE cm.id = ?";
        $page = $DB->get_record_sql($sql, array($mod->id));
        $page->cmid = $mod->id;

        $context = \context_module::instance($mod->id);
        $formatoptions = new \stdClass;
        $formatoptions->noclean = true;
        $formatoptions->context = $context;

        // Make sure we have some summary/extract text for the course page.
        if (!empty($page->intro)) {
            $page->summary = file_rewrite_pluginfile_urls($page->intro,
                'pluginfile.php', $context->id, 'mod_page', 'intro', null);
            $page->summary = format_text($page->summary, $page->introformat, $formatoptions);
        } else {
            $preview = strip_tags($page->content);
            $page->summary = shorten_text($preview, 200);
        }

        // Process content.
        $page->content = file_rewrite_pluginfile_urls($page->content,
            'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
        $page->content = format_text($page->content, $page->contentformat, $formatoptions);

        return ($page);
    }

    /**
     * Moodle does not provide a helper function to generate limit sql (it's baked into get_records_sql).
     * This function is useful - e.g. improving performance of UNION statements.
     * Note, it will return empty strings for unsupported databases.
     *
     * @param int $from
     * @param int $to
     *
     * @return string
     */
    public static function limit_sql($from, $num) {
        global $DB;
        switch ($DB->get_dbfamily()) {
            case 'mysql' :
                $sql = "LIMIT $from, $num";
                break;
            case 'postgres' :
                $sql = "LIMIT $num OFFSET $from";
                break;
            case 'mssql' :
            case 'oracle' :
            default :
                // Not supported.
                $sql = '';
        }
        return $sql;
    }

    /**
     * Get user by id.
     * @param $userorid
     * @return bool|stdClass|int
     */
    public static function get_user($userorid = false) {
        global $USER, $DB;

        if ($userorid === false) {
            return $USER;
        }

        if (is_object($userorid)) {
            return $userorid;
        } else if (is_number($userorid)) {
            if (intval($userorid) === $USER->id) {
                $user = $USER;
            } else {
                $user = $DB->get_record('user', ['id' => $userorid]);
            }
        } else {
            throw new \coding_exception('paramater $userorid must be an object or an integer or a numeric string');
        }

        return $user;
    }

    /**
     * Some moodle functions don't work correctly with specific userids and this provides a hacky workaround.
     *
     * Temporarily swaps global USER variable.
     * @param bool|stdClass|int $userorid
     */
    public static function swap_global_user($userorid = false) {
        global $USER;
        static $origuser = [];
        $user = self::get_user($userorid);
        if ($userorid !== false) {
            $origuser[] = $USER;
            $USER = $user;
        } else {
            $USER = array_pop($origuser);
        }
    }

    /**
     * Get recent forum activity for all accessible forums across all courses.
     * @param bool|int|stdclass $userorid
     * @param int $limit
     * @param int|null $since timestamp, only return posts from after this
     * @return array
     * @throws \coding_exception
     */
    public static function recent_forum_activity($userorid = false, $limit = 10, $since = null) {
        global $CFG, $DB;

        if (file_exists($CFG->dirroot.'/mod/hsuforum')) {
            require_once($CFG->dirroot.'/mod/hsuforum/lib.php');
        }

        $user = self::get_user($userorid);
        if (!$user) {
            return [];
        }

        if ($since === null) {
            $since = time() - (12 * WEEKSECS);
        }

        // Get all relevant forum ids for SQL in statement.
        // We use the post limit for the number of forums we are interested in too -
        // as they are ordered by most recent post.
        $userforums = new user_forums($user, $limit);
        $forumids = $userforums->forumids();
        $forumidsallgroups = $userforums->forumidsallgroups();
        $hsuforumids = $userforums->hsuforumids();
        $hsuforumidsallgroups = $userforums->hsuforumidsallgroups();

        if (empty($forumids) && empty($hsuforumids)) {
            return [];
        }

        $sqls = [];
        $params = [];

        if ($limit > 0) {
            $limitsql = self::limit_sql(0, $limit); // Note, this is here for performance optimisations only.
        } else {
            $limitsql = '';
        }

        if (!empty($forumids)) {
            list($finsql, $finparams) = $DB->get_in_or_equal($forumids, SQL_PARAMS_NAMED, 'fina');
            $params = $finparams;
            $params = array_merge($params,
                                 [
                                     'sepgps1a' => SEPARATEGROUPS,
                                     'sepgps2a' => SEPARATEGROUPS,
                                     'user1a'   => $user->id,
                                     'user2a'   => $user->id

                                 ]
            );

            $fgpsql = '';
            if (!empty($forumidsallgroups)) {
                // Where a forum has a group mode of SEPARATEGROUPS we need a list of those forums where the current
                // user has the ability to access all groups.
                // This will be used in SQL later on to ensure they can see things in any groups.
                list($fgpsql, $fgpparams) = $DB->get_in_or_equal($forumidsallgroups, SQL_PARAMS_NAMED, 'allgpsa');
                $fgpsql = ' OR f1.id '.$fgpsql;
                $params = array_merge($params, $fgpparams);
            }

            $params['user2a'] = $user->id;

            $sqls[] = "(SELECT ".$DB->sql_concat("'F'", 'fp1.id')." AS id, 'forum' AS type, fp1.id AS postid,
                               fd1.forum, fp1.discussion, fp1.parent, fp1.userid, fp1.modified, fp1.subject,
                               fp1.message, 0 AS reveal, cm1.id AS cmid,
                               0 AS forumanonymous, f1.course, f1.name AS forumname,
                               u1.firstnamephonetic, u1.lastnamephonetic, u1.middlename, u1.alternatename, u1.firstname,
                               u1.lastname, u1.picture, u1.imagealt, u1.email,
                               c.shortname AS courseshortname, c.fullname AS coursefullname
	                      FROM {forum_posts} fp1
	                      JOIN {user} u1 ON u1.id = fp1.userid
                          JOIN {forum_discussions} fd1 ON fd1.id = fp1.discussion
	                      JOIN {forum} f1 ON f1.id = fd1.forum AND f1.id $finsql
	                      JOIN {course_modules} cm1 ON cm1.instance = f1.id
	                      JOIN {modules} m1 ON m1.name = 'forum' AND cm1.module = m1.id
	                      JOIN {course} c ON c.id = f1.course
	                      LEFT JOIN {groups_members} gm1
                            ON cm1.groupmode = :sepgps1a
                           AND gm1.groupid = fd1.groupid
                           AND gm1.userid = :user1a
	                     WHERE (cm1.groupmode <> :sepgps2a OR (gm1.userid IS NOT NULL $fgpsql))
	                       AND fp1.userid <> :user2a
                           AND fp1.modified > $since
                      ORDER BY fp1.modified DESC
                               $limitsql
                        )
	                     ";
            // TODO - when moodle gets private reply (anonymous) forums, we need to handle this here.
        }

        if (!empty($hsuforumids)) {
            list($afinsql, $afinparams) = $DB->get_in_or_equal($hsuforumids, SQL_PARAMS_NAMED, 'finb');
            $params = array_merge($params, $afinparams);
            $params = array_merge($params,
                                  [
                                      'sepgps1b' => SEPARATEGROUPS,
                                      'sepgps2b' => SEPARATEGROUPS,
                                      'user1b'   => $user->id,
                                      'user2b'   => $user->id,
                                      'user3b'   => $user->id,
                                      'user4b'   => $user->id
                                  ]
            );

            $afgpsql = '';
            if (!empty($hsuforumidsallgroups)) {
                // Where a forum has a group mode of SEPARATEGROUPS we need a list of those forums where the current
                // user has the ability to access all groups.
                // This will be used in SQL later on to ensure they can see things in any groups.
                list($afgpsql, $afgpparams) = $DB->get_in_or_equal($hsuforumidsallgroups, SQL_PARAMS_NAMED, 'allgpsb');
                $afgpsql = ' OR f2.id '.$afgpsql;
                $params = array_merge($params, $afgpparams);
            }

            $sqls[] = "(SELECT ".$DB->sql_concat("'A'", 'fp2.id')." AS id, 'hsuforum' AS type, fp2.id AS postid,
                               fd2.forum, fp2.discussion, fp2.parent, fp2.userid, fp2.modified, fp2.subject,
                               fp2.message, fp2.reveal, cm2.id AS cmid,
                               f2.anonymous AS forumanonymous, f2.course, f2.name AS forumname,
                               u2.firstnamephonetic, u2.lastnamephonetic, u2.middlename, u2.alternatename, u2.firstname,
                               u2.lastname, u2.picture, u2.imagealt, u2.email,
                               c.shortname AS courseshortname, c.fullname AS coursefullname
                          FROM {hsuforum_posts} fp2
                          JOIN {user} u2 ON u2.id = fp2.userid
                          JOIN {hsuforum_discussions} fd2 ON fd2.id = fp2.discussion
                          JOIN {hsuforum} f2 ON f2.id = fd2.forum AND f2.id $afinsql
	                      JOIN {course_modules} cm2 ON cm2.instance = f2.id
	                      JOIN {modules} m2 ON m2.name = 'hsuforum' AND cm2.module = m2.id
	                      JOIN {course} c ON c.id = f2.course
	                      LEFT JOIN {groups_members} gm2
	                        ON cm2.groupmode = :sepgps1b
	                       AND gm2.groupid = fd2.groupid
	                       AND gm2.userid = :user1b
                         WHERE (cm2.groupmode <> :sepgps2b OR (gm2.userid IS NOT NULL $afgpsql))
                           AND (fp2.privatereply = 0 OR fp2.privatereply = :user2b OR fp2.userid = :user3b)
                           AND fp2.userid <> :user4b
                           AND fp2.modified > $since
                      ORDER BY fp2.modified DESC
                               $limitsql
                        )
                         ";
        }

        $sql = implode("\n".' UNION ALL '."\n", $sqls);
        if (count($sqls) > 1) {
            $sql .= "\n".' ORDER BY modified DESC';
        }
        $sql = '-- Snap sql'."\n"."SELECT * FROM ($sql) x";
        $posts = $DB->get_records_sql($sql, $params, 0, $limit);

        $activities = [];

        if (!empty($posts)) {
            foreach ($posts as $post) {
                $postuser = (object)[
                    'id' => $post->userid,
                    'firstnamephonetic' => $post->firstnamephonetic,
                    'lastnamephonetic' => $post->lastnamephonetic,
                    'middlename' => $post->middlename,
                    'alternatename' => $post->alternatename,
                    'firstname' => $post->firstname,
                    'lastname' => $post->lastname,
                    'picture' => $post->picture,
                    'imagealt' => $post->imagealt,
                    'email' => $post->email
                ];

                if ($post->type === 'hsuforum') {
                    $postuser = hsuforum_anonymize_user($postuser, (object)array(
                        'id' => $post->forum,
                        'course' => $post->course,
                        'anonymous' => $post->forumanonymous
                    ), $post);
                }

                $activities[] = (object)[
                    'type' => $post->type,
                    'cmid' => $post->cmid,
                    'name' => $post->subject,
                    'courseshortname' => $post->courseshortname,
                    'coursefullname' => $post->coursefullname,
                    'forumname' => $post->forumname,
                    'sectionnum' => null,
                    'timestamp' => $post->modified,
                    'content' => (object)[
                        'id' => $post->postid,
                        'discussion' => $post->discussion,
                        'subject' => $post->subject,
                        'parent' => $post->parent
                    ],
                    'user' => $postuser
                ];
            }
        }

        return $activities;
    }

    /**
     * Render recent forum activity.
     * @return string
     */
    public static function render_recent_forum_activity() {
        global $PAGE;
        $activities = self::recent_forum_activity();
        if (empty($activities)) {
            return '<p>' . get_string('noforumposts', 'theme_snap') . '</p>';
        }
        $activities = array_slice($activities, 0, 10);
        $renderer = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        return $renderer->recent_forum_activity($activities);
    }

    /**
     * Get the local url path for current page.
     * NOTE: This is not a duplciate of $PAGE->get_path();
     * $PAGE->get_path() includes the moodle subpath if accessed via sub path of url, which is not what we want.
     * e.g. - $PAGE->get_path on http://testing.local/apps/moodle/user/profile.php would return
     * apps/moodle/user/profile.php but we just want /user/profile.php
     * @return mixed
     * @throws \coding_exception
     */
    public static function current_url_path() {
        global $PAGE;
        return parse_url($PAGE->url->out_as_local_url())['path'];
    }
}
