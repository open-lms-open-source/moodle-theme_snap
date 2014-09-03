<?php
// This file is part of the custom Moodle Snap theme
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
//
namespace theme_snap;

require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once($CFG->dirroot.'/grade/lib.php');

class local {


    /**
     * Get overall grade for course.
     *
     * @param $course
     * @return float|null
     */
    public static function course_overall_grade($course) {
        global $CFG, $USER;

        // Get course context.
        $coursecontext = \context_course::instance($course->id);

        // See if user can view hidden grades for this course.
        $canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);

        // Get course grade_item.
        $courseitem = \grade_item::fetch_course_item($course->id);

        // Get the stored grade.
        $coursegrade = new \grade_grade(array('itemid' => $courseitem->id, 'userid' => $USER->id));
        $coursegrade->grade_item =& $courseitem;

        // Return null if can't view.
        if ($coursegrade->is_hidden() && !$canviewhidden) {
            return null;
        }

        $finalgrade = grade_format_gradevalue($coursegrade->finalgrade, $coursegrade->grade_item);

        // TODO - we should be putting our HTML in a renderer.
        $gradehtml = '';
        $finalgrade = $finalgrade == '-' ? '' : $finalgrade;
        if (!empty($finalgrade)) {
            $gradehtml = '<a class=coursegrade href="'.$CFG->wwwroot.'/grade/report/user/index.php?id='.$course->id.'">'.
                get_string('grade').': '.$finalgrade.'</a>';
        }

        return ((object) array('grade' => $finalgrade, 'gradehtml' => $gradehtml));
    }

    /**
     * Get course completion progress for specific course.
     * NOTE: It is by design that even teachers get course completion progress, this is so that they see exactly the
     * same as a student would in the personal menu.
     *
     * @param $course
     * @return string
     */
    public static function course_completion_progress($course) {
        if (!isloggedin() || isguestuser()) {
            return ''; // Can't get completion progress for users who aren't logged in.
        }
        $completioninfo = new \completion_info($course);
        $trackcount = 0;
        $compcount = 0;
        if ($completioninfo->is_enabled()) {
            $modinfo = get_fast_modinfo($course);

            foreach ($modinfo->cms as $thismod) {
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

        return ($compobj);
    }

    /**
     * Get information for array of courseids
     *
     * @param $courseids
     * @return bool | array
     */
    public static function courseinfo($courseids) {
        global $DB;
        if (empty($courseids)) {
            return false;
        }
        $courseinfo = array();
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', array('id' => $courseid));
            $courseinfo[$courseid] = (object) array(
                'courseid' => $courseid,
                'progress' => self::course_completion_progress($course),
                'grade' => self::course_overall_grade($course)
            );
        }
        return ($courseinfo);
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

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $messages = self::get_user_messages($USER->id);
        $o = '';
        if (!empty($messages)) {
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

                $unreadclass = '';
                if ($message->unread) {
                    $unreadclass = " snap-unread";
                }
                $o .= "<div class=\"snap-media-object$unreadclass\">";
                $o .= "<a href='$url'>";
                $o .= $frompicture;
                $o .= "<div class=\"snap-media-body\">";
                $o .= "<h3>$fromname</h3>";
                $o .= "<span class=snap-media-meta>";
                $o .= $output->relative_time($message->timecreated);
                if ($message->unread) {
                    $o .= " <span class=snap-unread-marker>".get_string('unread', 'theme_snap')."</span>";
                }
                $o .= "</span>";
                $o .= '<p>'.format_string($message->smallmessage).'</p>';
                $o .= "</div></a></div>";
            }
        } else {
            $o .= get_string('nomessages', 'theme_snap');
        }
        return $o;
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
        $userdaystart = usergetmidnight(time());
        $tomorrowstart = $userdaystart + DAYSECS;
        $userdayend = $tomorrowstart - 1;
        $yearfromnow = $userdaystart + YEARSECS;

        $courses = enrol_get_all_users_courses($userid);
        if (empty($courses)) {
            return '';
        }

        foreach ($courses as $course) {
            $courseids[] = $course->id;
        }

        $events = calendar_get_events($userdaystart, $userdayend, $userid, true, $courseids, false);

        $deadlines = array();
        $skipevent = 'course';
        foreach ($events as $key => $event) {
            if (isset($courses[$event->courseid])) {
                if ($event->eventtype != $skipevent) {
                    $course = $courses[$event->courseid];
                    $event->coursefullname = $course->fullname;
                    $deadlines[] = $event;
                }
            }
        }

        if (count($deadlines) >= $maxdeadlines) {
            return $deadlines;
        }

        $events = calendar_get_events($tomorrowstart, $yearfromnow, $userid, true, $courseids, false);
        foreach ($events as $key => $event) {
            if (isset($courses[$event->courseid])) {
                if ($event->eventtype != $skipevent) {
                    $course = $courses[$event->courseid];
                    $event->coursefullname = $course->fullname;
                    $deadlines[] = $event;
                    if (count($deadlines) >= $maxdeadlines) {
                        return $deadlines;
                    }
                }
            }
        }
        return $deadlines;
    }

    public static function deadlines() {
        global $USER, $PAGE, $CFG;

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $events = self::upcoming_deadlines($USER->id);
        $o = '';
        if (!empty($events)) {
            foreach ($events as $event) {
                if (!empty($event->modulename)) {
                    $cm = get_coursemodule_from_instance($event->modulename, $event->instance, $event->courseid);
                    $url = $CFG->wwwroot.'/mod/'.$event->modulename.'/view.php?id='.$cm->id;

                    $eventcoursename = format_string($event->coursefullname);
                    $eventname = format_string($event->name);
                    $eventtitle = "<small>$eventcoursename / </small> $eventname";

                    $modimageurl = $output->pix_url('icon', $event->modulename);
                    $modname = get_string('modulename', $event->modulename);
                    $modimage = '<img src="'.s($modimageurl).'" alt="'.s($modname).'" />';

                    $o .= "<div class=\"snap-media-object\">";
                    $o .= "<a href='$url'>";
                    $o .= $modimage;
                    $o .= "<div class=\"snap-media-body\">";
                    $o .= "<h3>$eventtitle</h3>";
                    $o .= "<span class=snap-media-meta>";
                    $o .= $output->friendly_datetime($event->timestart);
                    $o .= "</span></div>";
                    $o .= "</a></div>";
                }
            }
        } else {
            $o .= get_string('nodeadlines', 'theme_snap');
        }
        return $o;
    }


    /**
     * get hex color based on hash of course id
     *
     * @return string
     */
    public static function get_course_color($id) {
        return substr(md5($id), 0, 6);
    }
    /**
     * get course image of course
     *
     * @return bool|moodle_url
     */
    public static function get_course_image($courseid) {
        $fs      = get_file_storage();
        $context = \context_course::instance($courseid);
        $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);

        if (count($files) > 0) {
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    return \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        false,
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                }
            }
        } else {
            return false;
        }
    }
}
