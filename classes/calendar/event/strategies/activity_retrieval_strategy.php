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
 * Raw event retrieval strategy.
 *
 * @package    theme_snap
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com> / 2017 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\calendar\event\strategies;

/**
 * Extend core raw event retrieval strategy to cope with assignment extensions.
 *
 * @copyright 2017 Ryan Wyllie <ryan@moodle.com> / 2017 Blacboard Inc
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_retrieval_strategy extends \core_calendar\local\event\strategies\raw_event_retrieval_strategy {

    /**
     * Tweaks to core function - only get module level events, only get due, closing and opening events.
     * Also override assignment duedate with extension when present.
     * UPDATE: get also expectcompletionon events
     *
     * @param array|int|boolean $users array of users, user id or boolean for all/no user events
     * @param array|int|boolean $groups array of groups, group id or boolean for all/no group events
     * @param array|int|boolean $courses array of courses, course id or boolean for all/no course events
     * @param string $whereconditions The conditions in the WHERE clause.
     * @param array $whereparams The parameters for the WHERE clause.
     * @param string $ordersql The ORDER BY clause.
     * @param int $offset Offset.
     * @param int $limitnum Limit.
     * @param boolean $ignorehidden whether to select only visible events or all events
     * @return array $events of selected events or an empty array if there aren't any (or there was an error)
     */
    protected function get_raw_events_legacy_implementation(
        $users,
        $groups,
        $courses,
        $categories,
        $whereconditions,
        $whereparams,
        $ordersql,
        $offset,
        $limitnum,
        $ignorehidden
    ) {
        global $DB, $CFG;

        $params = array();
        // Quick test.
        if (empty($users) && empty($groups) && empty($courses) && empty($categories)) {
            return array();
        }

        if (is_numeric($users)) {
            $users = array($users);
        }
        if (is_numeric($groups)) {
            $groups = array($groups);
        }
        if (is_numeric($courses)) {
            $courses = array($courses);
        }
        if (is_numeric($categories)) {
            $categories = array($categories);
        }

        // Array of filter conditions. To be concatenated by the OR operator.
        $filters = [];

        // User filter.
        if (is_array($users) && !empty($users)) {
            // Events from a number of users.
            list($insqlusers, $inparamsusers) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
            $filters[] = "(e.userid $insqlusers AND e.courseid = 0 AND e.groupid = 0 AND e.categoryid = 0)";
            $params = array_merge($params, $inparamsusers);
        } else if ($users === true) {
            // Events from ALL users.
            $filters[] = "(e.userid != 0 AND e.courseid = 0 AND e.groupid = 0 AND e.categoryid = 0)";
        }
        // Boolean false (no users at all): We don't need to do anything.

        // Group filter.
        if (is_array($groups) && !empty($groups)) {
            // Events from a number of groups.
            list($insqlgroups, $inparamsgroups) = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED);
            $filters[] = "e.groupid $insqlgroups";
            $params = array_merge($params, $inparamsgroups);
        } else if ($groups === true) {
            // Events from ALL groups.
            $filters[] = "e.groupid != 0";
        }
        // Boolean false (no groups at all): We don't need to do anything.

        // Course filter.
        if (is_array($courses) && !empty($courses)) {
            list($insqlcourses, $inparamscourses) = $DB->get_in_or_equal($courses, SQL_PARAMS_NAMED);
            $filters[] = "(e.groupid = 0 AND e.courseid $insqlcourses)";
            $params = array_merge($params, $inparamscourses);
        } else if ($courses === true) {
            // Events from ALL courses.
            $filters[] = "(e.groupid = 0 AND e.courseid != 0)";
        }

        // Category filter.
        if (is_array($categories) && !empty($categories)) {
            list($insqlcategories, $inparamscategories) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);
            $filters[] = "(e.groupid = 0 AND e.courseid = 0 AND e.categoryid $insqlcategories)";
            $params = array_merge($params, $inparamscategories);
        } else if ($categories === true) {
            // Events from ALL categories.
            $filters[] = "(e.groupid = 0 AND e.courseid = 0 AND e.categoryid != 0)";
        }

        // Security check: if, by now, we have NOTHING in $whereclause, then it means
        // that NO event-selecting clauses were defined. Thus, we won't be returning ANY
        // events no matter what. Allowing the code to proceed might return a completely
        // valid query with only time constraints, thus selecting ALL events in that time frame!
        if (empty($filters)) {
            return array();
        }

        // Build our clause for the filters.
        $filterclause = implode(' OR ', $filters);

        // Array of where conditions for our query. To be concatenated by the AND operator.
        $whereconditions[] = "($filterclause)";

        // Show visible only.
        if ($ignorehidden) {
            $whereconditions[] = "(e.visible = 1)";
        }

        // Build the main query's WHERE clause.
        $whereclause = implode(' AND ', $whereconditions);

        // Build SQL subquery and conditions for filtered events based on priorities.
        $subquerywhere = '';
        $subqueryconditions = [];
        $subqueryparams = [];
        $allusercourses = [];

        if (is_array($users) && !empty($users)) {
            $userrecords = $DB->get_records_sql("SELECT * FROM {user} WHERE id $insqlusers", $inparamsusers);
            foreach ($userrecords as $userrecord) {
                // Get the user's courses. Otherwise, get the default courses being shown by the calendar.
                $usercourses = calendar_get_default_courses(null, 'id, category, groupmode, groupmodeforce',
                        false, $userrecord->id);

                // Set calendar filters.
                list($usercourses, $usergroups, $user) = calendar_set_filters($usercourses, true, $userrecord);

                $allusercourses = array_merge($allusercourses, $usercourses);

                // Flag to indicate whether the query needs to exclude group overrides.
                $viewgroupsonly = false;

                if ($user) {
                    // Set filter condition for the user's events.
                    // Even though $user is a single scalar, we still use get_in_or_equal() because we are inside a loop.
                    list($inusers, $inuserparams) = $DB->get_in_or_equal($user, SQL_PARAMS_NAMED);
                    $subqueryconditions[] = "(ev.userid $inusers AND ev.courseid = 0 AND ev.groupid = 0 AND ev.categoryid = 0)";
                    $subqueryparams = array_merge($subqueryparams, $inuserparams);

                    foreach ($usercourses as $courseid) {
                        if (has_capability('moodle/site:accessallgroups', \context_course::instance($courseid), $userrecord)) {
                            $usergroupmembership = groups_get_all_groups($courseid, $user, 0, 'g.id');
                            if (count($usergroupmembership) == 0) {
                                $viewgroupsonly = true;
                                break;
                            }
                        }
                    }
                }

                // Set filter condition for the user's group events.
                if ($usergroups === true || $viewgroupsonly) {
                    // Fetch group events, but not group overrides.
                    $subqueryconditions[] = "(ev.groupid != 0 AND ev.eventtype = 'group')";
                } else if (!empty($usergroups)) {
                    // Fetch group events and group overrides.
                    list($inusergroups, $inusergroupparams) = $DB->get_in_or_equal($usergroups, SQL_PARAMS_NAMED);
                    $subqueryconditions[] = "(ev.groupid $inusergroups)";
                    $subqueryparams = array_merge($subqueryparams, $inusergroupparams);
                }
            }
        } else if ($users === true) {
            // Events from ALL users.
            $subqueryconditions[] = "(ev.userid != 0 AND ev.courseid = 0 AND ev.groupid = 0 AND ev.categoryid = 0)";

            if (is_array($groups)) {
                // Events from a number of groups.
                list($insqlgroups, $inparamsgroups) = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED);
                $subqueryconditions[] = "ev.groupid $insqlgroups";
                $subqueryparams = array_merge($subqueryparams, $inparamsgroups);
            } else if ($groups === true) {
                // Events from ALL groups.
                $subqueryconditions[] = "ev.groupid != 0";
            }

            if ($courses === true) {
                // ALL course events. It's not needed to worry about users' access as $users = true.
                $subqueryconditions[] = "(ev.groupid = 0 AND ev.courseid != 0 AND ev.categoryid = 0)";
            }
        }

        // Get courses to be used for the subquery.
        $subquerycourses = [];
        if (is_array($courses)) {
            $subquerycourses = $courses;
        }
        // Merge with user courses, if necessary.
        if (!empty($allusercourses)) {
            $subquerycourses = array_merge($subquerycourses, $allusercourses);
            // Make sure we remove duplicate values.
            $subquerycourses = array_unique($subquerycourses);
        }

        // Set subquery filter condition for the courses.
        if (!empty($subquerycourses)) {
            list($incourses, $incoursesparams) = $DB->get_in_or_equal($subquerycourses, SQL_PARAMS_NAMED);
            $subqueryconditions[] = "(ev.groupid = 0 AND ev.courseid $incourses AND ev.categoryid = 0)";
            $subqueryparams = array_merge($subqueryparams, $incoursesparams);
        }

        // Set subquery filter condition for the categories.
        if ($categories === true) {
            $subqueryconditions[] = "(ev.categoryid != 0 AND ev.eventtype = 'category')";
        } else if (!empty($categories)) {
            list($incategories, $incategoriesparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);
            $subqueryconditions[] = "(ev.groupid = 0 AND ev.courseid = 0 AND ev.categoryid $incategories)";
            $subqueryparams = array_merge($subqueryparams, $incategoriesparams);
        }

        // Build the WHERE condition for the sub-query.
        $subqueryfrom = '{event} ev ';
        if (!empty($subqueryconditions)) {
            if (!empty($CFG->core_calendar_use_subquery_union_event_strategy)) {
                $unionstartquery = "
                                SELECT modulename, instance, eventtype, priority
                                  FROM {event} ev
                                 WHERE ";
                $subqueryunion = $unionstartquery . implode(" UNION $unionstartquery ", $subqueryconditions);
                $subqueryfrom = "($subqueryunion) ev";
            } else {
                $subqueryfrom .= 'WHERE ' . implode(" OR ", $subqueryconditions);
            }
        }

        // Merge subquery parameters to the parameters of the main query.
        if (!empty($subqueryparams)) {
            $params = array_merge($params, $subqueryparams);
        }

        // Sub-query that fetches the list of unique events that were filtered based on priority.
        $subquery = "SELECT ev.modulename,
                            ev.instance,
                            ev.eventtype,
                            MIN(ev.priority) as priority
                       FROM $subqueryfrom
                   GROUP BY ev.modulename, ev.instance, ev.eventtype";

        $assignoverride = '';
        $selectfields = 'e.*';
        if ($user) {
            $params['userid2'] = $user;
            $assignoverride = "LEFT JOIN {assign_user_flags} auf
                                      ON e.modulename = 'assign'
                                     AND e.eventtype = 'due'
                                     AND auf.userid = :userid2
                                     AND auf.assignment = e.instance
                                     AND auf.extensionduedate > 0";
            $cols = $DB->get_columns('event');
            $selectfields = '';
            foreach ($cols as $col) {
                $selectfields .= $selectfields === '' ? '' : ', ';
                if ($col->name === 'timestart') {
                    $selectfields .= 'CASE WHEN auf.extensionduedate IS NULL THEN e.timestart '.
                        'ELSE auf.extensionduedate END AS timestart';
                } else if ($col->name === 'timesort') {
                    $selectfields .= 'CASE WHEN auf.extensionduedate IS NULL THEN e.timesort '.
                        'ELSE auf.extensionduedate END AS timesort';
                } else {
                    $selectfields .= 'e.'.$col->name;
                }
            }
        }

        // Disable activities in the Deadlines feed, as set in $CFG->theme_snap_disable_deadline_mods.
        if (!empty($CFG->theme_snap_disable_deadline_mods)) {
            $disabledmods = $CFG->theme_snap_disable_deadline_mods;

            // Make the query clause "AND e.modulename NOT IN (disabled mods)".
            $equal = false;
            list($insql, $inparams) = $DB->get_in_or_equal($disabledmods, SQL_PARAMS_NAMED, 'dism', $equal);
            $disabledmodssql = "AND e.modulename $insql";
        } else {
            $disabledmodssql = '';
        }

        // Build the main query.
        $sql = "-- Snap sql
        ";
        $sql .= "SELECT {$selectfields}
                   FROM {event} e
             INNER JOIN ($subquery) fe
                     ON e.modulename = fe.modulename
                        AND e.instance = fe.instance
                        AND e.eventtype = fe.eventtype
                        AND (e.priority = fe.priority OR (e.priority IS NULL AND fe.priority IS NULL))
              LEFT JOIN {modules} m
                     ON e.modulename = m.name
                     $assignoverride
               WHERE ((m.visible = 1 OR m.visible IS NULL)
                     AND (e.modulename IS NOT NULL)
                     AND (e.eventtype = 'open' OR e.eventtype = 'close' OR e.eventtype = 'due'
                      OR e.eventtype = 'expectcompletionon'))
                     AND $whereclause
                     $disabledmodssql
              ORDER BY " . ($ordersql ? $ordersql : "e.timestart");

        if (!empty($whereparams)) {
            $params = array_merge($params, $whereparams);
        }

        if (!empty($inparams)) {
            $params = array_merge($params, $inparams);
        }

        $events = $DB->get_records_sql($sql, $params, $offset, $limitnum);

        return  $events === false ? [] : $events;
    }
}
