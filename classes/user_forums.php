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

/**
 * Provides information on all forums a user has access to.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_forums {

    /**
     * @var stdclass
     */
    protected $user;

    /**
     * @var array
     */
    protected $courses = [];

    /**
     * @var array
     */
    protected $forums = [];

    /**
     * @var array
     */
    protected $forumids = [];

    /**
     * @var array
     */
    protected $aforums = [];

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
     * @var int
     */
    public static $forumlimit = 100;

    /**
     * @param null $userorid
     */
    public function __construct($userorid = null) {
        $this->user = local::get_user($userorid);
        if (empty($this->user) || empty($this->user->id)) {
            throw new coding_exception('Failed to get user from '.var_export($userorid, true));
        }
        $this->populate_forums();
    }

    /**
     * @return mixed
     */
    public function forums() {
        return $this->forums;
    }

    /**
     * @return array
     */
    public function forumids() {
        return $this->forumids;
    }

    public function aforums() {
        return $this->aforums;
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
                $cmcontext = \context_module::instance($cm->id);
                $allgroups = has_capability('moodle/site:accessallgroups', $cmcontext);
                if ($allgroups) {
                    $forumidsallgroups[] = $forum->id;
                }
            }
        }
        return $forumidsallgroups;
    }

    /**
     * Forums by lastpost with most recently posted at the top.
     *
     * @return array
     */
    protected function forumids_by_lastpost($limit) {
        global $DB;

        $params = [$this->user->id];

        $sql = 'SELECT fd.forum, MAX(fp.modified) lastpost
                  FROM {forum_posts} fp
                  JOIN {forum_discussions} fd
                    ON fd.id = fp.discussion
                  JOIN {enrol} e
                    ON e.courseid = fd.course
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = ?
              GROUP BY fd.forum
              ORDER BY lastpost desc';

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /**
     * Forums by lastpost with most recently posted at the top.
     *
     * @return array
     */
    protected function hsuforumids_by_lastpost($limit) {
        global $DB;

        $params = [$this->user->id];

        $sql = 'SELECT fd.forum, MAX(fp.modified) lastpost
                  FROM {hsuforum_posts} fp
                  JOIN {hsuforum_discussions} fd
                    ON fd.id = fp.discussion
                  JOIN {hsuforum} f
                    ON f.id = fd.forum
                  JOIN {enrol} e
                    ON e.courseid = f.course
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = ?
              GROUP BY fd.forum
              ORDER BY lastpost desc';

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /**
     * Identify and remove stale forums.
     * This is necessary when there are a large number of forums to query - for performance reasons and also because
     * there are query parameter limits in mssql and oracle.
     *
     * @param $forums
     * @return mixed
     */
    protected function process_stale_forums(Array $forums, $hsuforum = false) {

        if (count($forums) > self::$forumlimit) {
            // Get forum ids by postid (ordered by most recently posted).
            if (!$hsuforum) {
                $forumidsbypost = $this->forumids_by_lastpost(self::$forumlimit);
            } else {
                $forumidsbypost = $this->hsuforumids_by_lastpost(self::$forumlimit);
            }

            $tmpforums = [];

            // Re-order forums by most recently posted.
            if (!empty($forumidsbypost)) {
                foreach ($forumidsbypost as $id => $postdate) {
                    if (isset($forums[$id])) {
                        $tmpforums[$id] = $forums[$id];
                    }
                }
                $forums = $tmpforums;
            }

            // Cut off the less recently active forums (most stale).
            $forums = array_slice($forums, 0, self::$forumlimit, true);
        }

        return $forums;
    }


    /**
     * Populate forum id arrays.
     * @throws \coding_exception
     */
    protected function populate_forums() {
        local::swap_global_user($this->user->id);

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
        $aforums = $this->process_stale_forums($aforums, true);

        $this->forums = $forums;
        $this->aforums = $aforums;
        $this->forumids = array_keys($forums);
        $this->forumidsallgroups = $this->forumids_accessallgroups($forums);
        $this->aforumids = array_keys($aforums);
        $this->aforumidsallgroups = $this->forumids_accessallgroups($aforums, 'hsuforum');

        local::swap_global_user(false);
    }
}