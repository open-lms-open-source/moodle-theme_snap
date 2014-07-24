<?php
namespace theme_snap;

require_once($CFG->dirroot.'/calendar/lib.php');

class local {


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
     * Return due date events.
     * @param integer $start time uts
     * @param integer $end time uts
     * @param null|integer $userid
     * @return array
     */
    public static function duedate_events($start, $end, $userid = null) {
        global $USER;
        $userid = empty($userid) ? $USER->id : $userid;
        $courses = enrol_get_all_users_courses($userid);

        if (empty($courses)) {
            return null;
        }

        foreach ($courses as $course) {
            $courseids[] = $course->id;
        }
        $events = calendar_get_events($start, $end, $userid, true, $courseids, false);
        $ddevents = array();
        foreach ($events as $key => $event) {
            if (isset($courses[$event->courseid])){
                $course = $courses[$event->courseid];
                $event->courseshortname = $course->shortname;
                $event->coursefullname = $course->fullname;
                $usercreatedevents = 'course';
                if ($event->eventtype != $usercreatedevents){
                    $ddevents[] = $event;
                }
            }
        }
        return $ddevents;
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
