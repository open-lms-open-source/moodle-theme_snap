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
    public function get_name() {
        return get_string('refreshdeadlinestask', 'theme_snap');
    }

    public function execute() {
        global $DB, $CFG;

        $currenttheme = $CFG->theme;
        if ($currenttheme == 'snap'
            && empty(get_config('theme_snap', 'deadlinestoggle'))
        ) {
            // Skip if in Snap and deadlines are off.
            return;
        }

        if (empty(get_config('theme_snap', 'personalmenurefreshdeadlines'))) {
            // Skip, setting is off.
            return;
        }

        // Fill deadlines for users who logged in yesterday.
        $query = <<<SQL
  SELECT u.id, u.lastlogin
    FROM {user} u
   WHERE u.deleted = :deleted
     AND u.lastlogin >= :yesterday
SQL;
        $yesterday = new \DateTime('yesterday', core_date::get_server_timezone_object());
        $yesterdayts = $yesterday->getTimestamp();
        $users = $DB->get_recordset_sql($query, [
            'deleted'   => 0,
            'yesterday' => strtotime(date('Y-m-d', $yesterdayts))
        ]);
        foreach ($users as $userid => $user) {
            // This populates deadline caches or does nothing if run the same day.
            activity::upcoming_deadlines($userid);
        }
    }
}
