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
require_once($CFG->dirroot.'/grade/report/user/lib.php');

class local {


    /**
     * If debugging enabled in config then return reason for no grade (useful for json output).
     *
     * @param $warning
     * @return null|object
     */
    protected static function skipgradewarning($warning) {
        global $CFG;
        if (!empty($CFG->debugdisplay)) {
            return (object) array ('skipgrade' => $warning);
        } else {
            return null;
        }
    }

    /**
     * Get overall grade for course.
     *
     * @param $course
     * @return stdClass | null
     */
    public static function course_overall_grade($course) {
        global $USER;

        // Get course context.
        $coursecontext = \context_course::instance($course->id);

        // Security check - should they be allowed to see course grade?
        if (!is_enrolled($coursecontext, $USER, 'moodle/grade:view')) {
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
        foreach ($report->tabledata as $item){
            if (!empty($item['grade']['content'])){
                // Set grade content to null string if it contents - or a blank space.
                $item['grade']['content'] = str_ireplace(array('-','&nbsp;'),'',$item['grade']['content']);
            }
            if (!empty($item['grade']['content'])
                && stripos($item['grade']['class'], 'gradingerror') === false
            ) {
                $visiblegradefound = true;
            }
        }

        $gradehtml='';
        if ($visiblegradefound){
            // Just output - feedback available.
            $url = new \moodle_url('/grade/report/user/index.php', array('id' => $course->id));
            $gradehtml = \html_writer::link($url,
                get_string('feedbackavailable', 'theme_snap'),
                array('class' => 'coursegrade')
            );
        }

        return (object) array('gradehtml' => $gradehtml);
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
        if (!is_enrolled($context)) {
            return null;
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

        return $compobj;
    }


    /**
     * Is a user a teacher?
     *
     * @param null|stdClass $user
     * @param null|stdClass $course
     */
    public static function is_teacher($user = null, $course = null) {
        global $USER, $COURSE;
        $user = empty($user) ? $USER : $user;
        $course = empty($course) ? $COURSE : $course;
        return has_capability('moodle/course:manageactivities', \context_course::instance($course->id), $user);
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

            $context = \context_course::instance($courseid);
            if (!is_enrolled($context)) {
                // Skip this course, don't have permission to view.
                continue;
            }

            $courseinfo[$courseid] = (object) array(
                'courseid' => $courseid,
                'progress' => self::course_completion_progress($course),
                'grade' => self::course_overall_grade($course)
            );
        }
        return $courseinfo;
    }

    /**
     * Get module table row for module id
     */
    public static function moduletabrow($mod) {
        global $DB;
        $sql = "SELECT a.*
                  FROM {course_modules} cm
                  JOIN {".$mod->modname."} a ON a.id=cm.instance
                 WHERE cm.id=?";
        return $DB->get_record_sql($sql, array($mod->id));
    }

    /**
     * Get total particpiant count for specific courseid.
     *
     * @param $courseid
     * @return int
     */
    public static function course_participant_count($courseid) {
        global $DB, $CFG;

        static $participantcount = null;

        if (!is_null($participantcount)) {
            return $participantcount;
        }
        if (empty($CFG->gradebookroles)) {
            $participantcount = 0;
            return $partipantcount;
        }
        $studentroles = explode(',', $CFG->gradebookroles);
        list($instudentroles, $params) = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);

        $context = \context_course::instance($courseid);
        $params['contextid'] = $context->id;

        $sql = "SELECT count(*) AS total
                  FROM {role_assignments} a
             LEFT JOIN {role} r ON r.id = a.roleid
                 WHERE r.id $instudentroles
                       AND a.contextid = :contextid
                ";
        $row = $DB->get_record_sql($sql, $params);
        if (!($row)) {
            $participantcount = 0;
        } else {
            $participantcount = $row->total;
        }
        return $participantcount;
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
        if (empty($messages)) {
            return '<p>' . get_string('nomessages', 'theme_snap') . '</p>';
        }
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

            $meta = $output->relative_time($message->timecreated);
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
        global $USER, $PAGE;

        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $events = self::upcoming_deadlines($USER->id);
        if (empty($events)) {
            return '<p>' . get_string('nodeadlines', 'theme_snap') . '</p>';
        }
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
                && !empty($grade->rawgrade)
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

        usort($grading, function($a, $b) {
            $atime = empty($a->closetime) ? $a->opentime : $a->closetime;
            $btime = empty($b->closetime) ? $b->opentime : $b->closetime;
            if ($atime === $btime) {
                if ($a->coursemoduleid === $b->coursemoduleid) {
                    return 0;
                }
                return ($a->coursemoduleid < $b->coursemoduleid) ? -1 : 1;
            }
            return ($atime < $btime) ? -1 : 1;
        }
        );
        return $grading;
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
