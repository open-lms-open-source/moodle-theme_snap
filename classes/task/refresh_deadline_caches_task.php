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
 * Deadlines refresh task.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2021 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\task;

use context_course;
use core\task\scheduled_task;
use core_date;
use theme_snap\activity;

defined('MOODLE_INTERNAL') || die();

/**
 * Deadlines refresh task class.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2021 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_deadline_caches_task extends scheduled_task {

    /**
     * {@inheritDoc}
     */
    public function get_name() {
        return get_string('refreshdeadlinestask', 'theme_snap');
    }

    /**
     * {@inheritDoc}
     */
    public function execute() {
        global $DB, $CFG;

        if (empty(get_config('theme_snap', 'personalmenurefreshdeadlines'))) {
            // Skip, setting is off.
            return;
        }

        $lastlogindateformat = empty($CFG->theme_snap_refresh_deadlines_last_login) ?
            '6 months ago' : $CFG->theme_snap_refresh_deadlines_last_login;

        // Fill deadlines for users who logged in yesterday.
        $query                    = <<<SQL
  SELECT u.id, u.lastlogin
    FROM {user} u
   WHERE u.deleted = :deleted
     AND u.lastlogin >= :lastlogints
SQL;
        $lastlogindate            = new \DateTime($lastlogindateformat, core_date::get_server_timezone_object());
        $lastlogints              = $lastlogindate->getTimestamp();
        $users                    = $DB->get_recordset_sql($query, [
            'deleted'             => 0,
            'lastlogints'         => strtotime(date('Y-m-d', $lastlogints))
        ]);
        $blockinstances           = []; // Local cache of instances in courses.
        $snapfeedsdeadlinesconfig = base64_encode(serialize((object) [
            'feedtype' => 'deadlines'
        ]));
        foreach ($users as $userid => $user) {
            // This populates deadline caches or does nothing if run the same day.
            activity::upcoming_deadlines($userid);
            $courses = enrol_get_users_courses($user->id, true);

            // Give a helping hand populating caches for course snap feeds blocks.
            foreach ($courses as $course) {
                if (!isset($blockinstances[$course->id])) {
                    $contextcourse = context_course::instance($course->id);
                    $parentcontextid = $contextcourse->id;
                    $query = <<<SQL
   SELECT *
     FROM {block_instances}
    WHERE blockname = :blockname
      AND parentcontextid = :parentcontextid
      AND configdata = :configdata
SQL;

                    $blockinstances[$course->id] = $DB->record_exists_sql($query, [
                        'blockname'       => 'snapfeeds',
                        'parentcontextid' => $parentcontextid,
                        'configdata'      => $snapfeedsdeadlinesconfig,
                    ]);
                }

                if ($blockinstances[$course->id]) {
                    activity::upcoming_deadlines($userid, 500, $course);
                }
            }
        }
    }
}
