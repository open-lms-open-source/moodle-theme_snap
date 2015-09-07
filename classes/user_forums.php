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

use theme_snap\local;

class user_forums {

    /**
     * @var stdClass;
     */
    protected $user;

    /**
     * @var array
     */
    protected $courses = [];

    /**
     * @var array
     */
    protected $forumids = [];

    /**
     * @var array
     */
    protected $aforumids = [];

    /**
     * @var array
     */
    protected $forumidsallgroups = [];

    /**
     * @var array
     */
    protected $aforumidsallgroups = [];

    /**
     * @param null $userorid
     */
    function __construct($userorid = null){
        $this->user = local::get_user($userorid);
        $this->populate_forums();
    }

    /**
     * @return array
     */
    public function forumids() {
        return $this->forumids;
    }

    /**
     * @return array
     */
    public function aforumids() {
        return $this->aforumids;
    }

    /**
     * @return array
     */
    public function forumidsallgroups() {
        return $this->forumidsallgroups;
    }

    /**
     * @return array
     */
    public function aforumidsallgroups() {
        return $this->aforumidsallgroups;
    }

    /**
     * Remove qanda forums from forums array.
     * @param $forums
     * @return array
     */
    private function purge_qa_forums(Array $forums) {
        if (empty($forums)) {
            return $forums;
        }
        return array_filter($forums, function($forum) {
            return $forum->type !== 'qanda';
        });
    }

    /**
     * Get forumids where current user has accessallgroups capability
     */
    private function forumids_accessallgroups($forums, $type = 'forum') {
        $forumidsallgroups = [];
        foreach ($forums as $forum) {
            $cm = get_coursemodule_from_instance($type, $forum->id);
            if (intval($cm->groupmode) === SEPARATEGROUPS) {
                $cm_context = \context_module::instance($cm->id);
                $allgroups = has_capability('moodle/site:accessallgroups', $cm_context);
                if ($allgroups) {
                    $forumidsallgroups[] = $forum->id;
                }
            }
        }
        return $forumidsallgroups;
    }

    /**
     * When was a specific course last accessed?
     * @param int $courseorid
     * @param int|null $userid
     * @param int|null $timestart
     * @return int|bool
     */
    protected function course_last_accessed($courseorid, $userid = null, $timestart = null) {

        $courseid = is_object($courseorid) ? $courseorid->id : $courseorid;

        $logmanger = get_log_manager();
        $readers = $logmanger->get_readers('\core\log\sql_select_reader');
        $reader = reset($readers);
        if (empty($reader)) {
            return false; // No log reader found.
        }

        $select = "courseid = :courseid AND eventname = :eventname";
        $params = array(
            'courseid'     => $courseid,
            'eventname'    => '\mod_collaborate\event\session_launched'
        );

        if (!empty($timestart)) {
            $params['since'] = $timestart;
        }

        if (!empty($userid)) {
            $select .= ' AND userid = :userid';
            $params['userid'] = $userid;
        }

        $events = $reader->get_events_select($select, $params, 'timecreated DESC', 0, 1);
        if (!empty($events)) {
            return reset($events)->timecreated;
        } else {
            return false;
        }
    }

    /**
     * Remove forums which are in stale courses.
     * @param array $forums
     * @param array $stalecourseids
     * @param int $limit
     * @return mixed
     */
    protected function remove_stale_forums(Array $forums, Array $stalecourseids, $limit = 200) {
        if (!empty($stalecourseids)) {
            $tmpforums = $forums;
            foreach ($forums as $id => $forum) {
                if (in_array($forum->course, $stalecourseids)) {
                    unset ($tmpforums[$id]);
                }
                if (count($tmpforums) <= $limit) {
                    break;
                }
            }
            $forums = $tmpforums;
        }
        return ($forums);
    }

    /**
     * Identify and remove stale forums.
     * This is necessary when there are a large number of forums to query - for performance reasons and also because
     * there are query parameter limits in mssql and oracle.
     *
     * @param $forums
     * @return mixed
     */
    protected function process_stale_forums(Array $forums) {
        // Pass 1 on removing forums from stale courses.
        if (count($forums) > 200) {
            // Attempt to remove stale forums by courses not accessed by anyone in one month.
            $stalecourseids = [];
            foreach ($this->courses as $course) {
                if ($this->course_last_accessed($course->id, null, time() - (WEEKSECS * 4)) === false) {
                    $stalecourseids[] = $course->id;
                }
            }
            $forums = $this->remove_stale_forums($forums, $stalecourseids);
        }

        // Pass 2 on removing forums from stale courses.
        if (count($forums) > 200) {
            // Attempt to remove stale forums by courses not accessed by current user in half a year.
            $stalecourseids = [];
            foreach ($this->courses as $course) {
                if ($this->course_last_accessed($course->id, $this->user->id, time() - (YEARSECS / 2)) === false) {
                    $stalecourseids[] = $course->id;
                }
            }
            $forums = $this->remove_stale_forums($forums, $stalecourseids);
        }
        return $forums;
    }

    protected function forums_by_lastpost() {
        $sql = 'SELECT mfd.forum, MAX(created) lastpost
                  FROM {forum_posts} mfp
                  JOIN {forum_discussions} mfd
                    ON mfd.id = mfp.discussion
              GROUP BY mfd.forum
              ORDER BY lastpost DESC';
    }

    /**
     * Populate forum id arrays.
     * @throws \coding_exception
     */
    protected function populate_forums() {
        if ($this->user->id !==null) {
            local::swap_global_user($this->user->id);
        }
        $this->courses = enrol_get_my_courses();

        $forums = [];
        $aforums = [];

        foreach ($this->courses as $course) {
            $forums = $forums + forum_get_readable_forums($this->user->id, $course->id);
            if (function_exists('hsuforum_get_readable_forums')) {
                $aforums = $aforums + hsuforum_get_readable_forums($this->user->id, $course->id, true);
            }
        }

        // Remove Q&A forums from array.
        $forums = $this->purge_qa_forums($forums);
        $aforums = $this->purge_qa_forums($aforums);

        // Rmove forums in courses not accessed for a long time.
        $forums = $this->process_stale_forums($forums);
        $aforums = $this->process_stale_forums($aforums);

        /*
        // Limit the number of forums to work with.
        if (count($forums) > 200) {
            $forums = array_slice($forums, 0, 200);
        }
        if (count($aforums) > 200) {
            $aforums = array_slice($aforums, 0, 200);
        }*/

        $this->forumids = array_keys($forums);
        $this->forumidsallgroups = $this->forumids_accessallgroups($forums);
        $this->aforumids = array_keys($aforums);
        $this->aforumidsallgroups = $this->forumids_accessallgroups($aforums, 'hsuforum');

        local::swap_global_user(false);
    }
}