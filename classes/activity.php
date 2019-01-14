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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/assign/locallib.php');

/**
 * Activity functions.
 * These functions are in a class purely for auto loading convenience.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity {

    public static $phpunitallowcaching = false;

    /**
     * @param \cm_info $mod
     * @return activity_meta
     */
    public static function module_meta(\cm_info $mod) {
        $methodname = $mod->modname . '_meta';
        if (method_exists('theme_snap\\activity', $methodname)) {
            $meta = call_user_func('theme_snap\\activity::' . $methodname, $mod);
        } else {
            $meta = new activity_meta(); // Return empty activity meta.
        }
        return $meta;
    }

    /**
     * Return standard meta data for module
     *
     * @param cm_info $mod
     * @param string $timeopenfld
     * @param string $timeclosefld
     * @param string $keyfield
     * @param string $submissiontable
     * @param string $submittedonfld
     * @param string $submitstrkey
     * @param bool $isgradeable
     * @param string $submitselect - sql to further filter submission row select statement - e.g. st.status='finished'
     * @param bool $submissionnotrequired
     * @return activity_meta
     */
    protected static function std_meta(\cm_info $mod,
                                       $timeopenfld,
                                       $timeclosefld,
                                       $keyfield,
                                       $submissiontable,
                                       $submittedonfld,
                                       $submitstrkey,
                                       $isgradeable = false,
                                       $submitselect = '',
                                       $submissionnotrequired = false
    ) {
        global $USER;

        $courseid = $mod->course;

        // Create meta data object.
        $meta = new activity_meta();
        $meta->submissionnotrequired = $submissionnotrequired;
        $meta->submitstrkey = $submitstrkey;
        $meta->submittedstr = get_string($submitstrkey, 'theme_snap');
        $meta->notsubmittedstr = get_string('not'.$submitstrkey, 'theme_snap');
        if (get_string_manager()->string_exists($mod->modname.'draft', 'theme_snap')) {
            $meta->draftstr = get_string($mod->modname.'draft', 'theme_snap');
        } else {
            $meta->drafstr = get_string('draft', 'theme_snap');
        }

        if (get_string_manager()->string_exists($mod->modname.'reopened', 'theme_snap')) {
            $meta->reopenedstr = get_string($mod->modname.'reopened', 'theme_snap');
        } else {
            $meta->reopenedstr = get_string('reopened', 'theme_snap');
        }

        // If module is not visible to the user then don't bother getting meta data.
        if (!$mod->visibleoncoursepage) {
            return $meta;
        }

        $activitydates = self::instance_activity_dates($courseid, $mod, $timeopenfld, $timeclosefld);
        $meta->timeopen = $activitydates->timeopen;
        $meta->timeclose = $activitydates->timeclose;
        $meta->timesfromcache = !empty($activitydates->fromcache);

        if (isset($activitydates->extension)) {
            $meta->extension = $activitydates->extension;
        }

        // TODO: use activity specific "teacher" capabilities.
        if (has_capability('mod/assign:grade', $mod->context)) {
            $meta->isteacher = true;

            // Teacher - useful teacher meta data.
            $methodnsubmissions = $mod->modname.'_num_submissions';
            $methodnungraded = $mod->modname.'_num_submissions_ungraded';

            if (method_exists('theme_snap\\activity', $methodnsubmissions)) {
                $meta->numsubmissions = call_user_func('theme_snap\\activity::'.$methodnsubmissions, $courseid, $mod->instance);
            }
            if (method_exists('theme_snap\\activity', $methodnungraded)) {
                $meta->numrequiregrading = call_user_func('theme_snap\\activity::'.$methodnungraded, $courseid, $mod->instance);
            }
        } else {
            // Student - useful student meta data - only display if activity is available.
            if (empty($activitydates->timeopen) || $activitydates->timeopen <= time()) {

                $submissionrow = self::get_submission_row($courseid, $mod, $submissiontable, $keyfield, $submitselect);

                if (!empty($submissionrow)) {
                    if ($mod->modname === 'assign' && !empty($submissionrow->status)) {
                        switch ($submissionrow->status) {
                            case ASSIGN_SUBMISSION_STATUS_DRAFT:
                                $meta->draft = true;
                                break;

                            case ASSIGN_SUBMISSION_STATUS_REOPENED:
                                $meta->reopened = true;
                                break;

                            case ASSIGN_SUBMISSION_STATUS_SUBMITTED:
                                $meta->submitted = true;
                                break;
                        }
                    } else {
                        $meta->submitted = true;
                        $meta->timesubmitted = !empty($submissionrow->$submittedonfld) ? $submissionrow->$submittedonfld : null;
                    }
                    // If submitted on field uses modified field then fall back to timecreated if modified is 0.
                    if (empty($meta->timesubmitted) && $submittedonfld = 'timemodified') {
                        if (isset($submissionrow->timemodified)) {
                            $meta->timesubmitted = $submissionrow->timemodified;
                        } else {
                            $meta->timesubmitted = $submissionrow->timecreated;
                        }
                    }
                }
            }

            $graderow = false;
            if ($isgradeable) {
                $graderow = self::grade_row($courseid, $mod);
            }

            if ($graderow) {
                $gradeitem = \grade_item::fetch(array(
                    'itemtype' => 'mod',
                    'itemmodule' => $mod->modname,
                    'iteminstance' => $mod->instance,
                ));

                $grade = new \grade_grade(array('itemid' => $gradeitem->id, 'userid' => $USER->id));

                $coursecontext = \context_course::instance($courseid);
                $canviewhiddengrade = has_capability('moodle/grade:viewhidden', $coursecontext);

                if (!$grade->is_hidden() || $canviewhiddengrade) {
                    $meta->grade = true;
                }
            }
        }

        if (!empty($meta->timeclose)) {
            // Submission required?
            $subreqd = empty($meta->submissionnotrequired);

            // Overdue?
            $meta->overdue = $subreqd && empty($meta->submitted) && (time() > $meta->timeclose);
        }

        return $meta;
    }

    /**
     * Get assignment meta data
     *
     * @param cm_info $modinst - module instance
     * @return activity_meta
     */
    public static function assign_meta(\cm_info $modinst) {
        global $DB;
        static $submissionsenabled;

        $courseid = $modinst->course;

        // Get count of enabled submission plugins grouped by assignment id.
        // Note, under normal circumstances we only run this once but with PHP unit tests, assignments are being
        // created one after the other and so this needs to be run each time during a PHP unit test.
        if (empty($submissionsenabled) || PHPUNIT_TEST) {
            $sql = "SELECT a.id, count(1) AS submissionsenabled
                      FROM {assign} a
                      JOIN {assign_plugin_config} ac ON ac.assignment = a.id
                     WHERE a.course = ?
                       AND ac.name='enabled'
                       AND ac.value = '1'
                       AND ac.subtype='assignsubmission'
                       AND plugin!='comments'
                  GROUP BY a.id;";
            $submissionsenabled = $DB->get_records_sql($sql, array($courseid));
        }

        $submitselect = '';

        // If there aren't any submission plugins enabled for this module, then submissions are not required.
        if (empty($submissionsenabled[$modinst->instance])) {
            $submissionnotrequired = true;
        } else {
            $submissionnotrequired = false;
        }

        $meta = self::std_meta($modinst, 'allowsubmissionsfromdate', 'duedate', 'assignment', 'submission',
            'timemodified', 'submitted', true, $submitselect, $submissionnotrequired);

        return ($meta);
    }

    /**
     * Get choice module meta data
     *
     * @param cm_info $modinst - module instance
     * @return string
     */
    public static function choice_meta(\cm_info $modinst) {
        return  self::std_meta($modinst, 'timeopen', 'timeclose', 'choiceid', 'answers', 'timeseen', 'answered');
    }

    /**
     * Get database module meta data
     *
     * @param cm_info $modinst - module instance
     * @return string
     */
    public static function data_meta(\cm_info $modinst) {
        return self::std_meta($modinst, 'timeavailablefrom', 'timeavailableto', 'dataid', 'records', 'timemodified', 'contributed');
    }

    /**
     * Get feedback module meta data
     *
     * @param cm_info $modinst - module instance
     * @return string
     */
    public static function feedback_meta(\cm_info $modinst) {
        return self::std_meta($modinst, 'timeopen', 'timeclose', 'feedback', 'completed', 'timemodified', 'submitted');
    }

    /**
     * Get lesson module meta data
     *
     * @param cm_info $modinst - module instance
     * @return string
     */
    public static function lesson_meta(\cm_info $modinst) {
        $meta = self::std_meta($modinst, 'available', 'deadline', 'lessonid', 'attempts', 'timeseen', 'attempted', true);
        $meta->submissionnotrequired = true;
        return $meta;
    }

    /**
     * Get quiz module meta data
     *
     * @param cm_info $modinst - module instance
     * @return string
     */
    public static function quiz_meta(\cm_info $modinst) {
        return self::std_meta($modinst, 'timeopen', 'timeclose', 'quiz',
            'attempts', 'timemodified', 'attempted', true, 'AND st.state=\'finished\'');
    }

    /**
     * Get all assignments (for all courses) waiting to be graded.
     *
     * @param array $courseids
     * @param int $since
     * @return array $ungraded
     */
    public static function assign_ungraded($courseids, $since = null) {
        global $DB;

        $ungraded = array();

        if ($since === null) {
            $since = time() - (12 * WEEKSECS);
        }

        // Limit to assignments with grades.
        $gradetypelimit = 'AND gi.gradetype NOT IN (' . GRADE_TYPE_NONE . ',' . GRADE_TYPE_TEXT . ')';

        foreach ($courseids as $courseid) {

            // Get the assignments that need grading.
            list($esql, $params) = get_enrolled_sql(\context_course::instance($courseid), 'mod/assign:submit', 0, true);
            $params['courseid'] = $courseid;

            list($sqlgroupsjoin, $sqlgroupswhere, $groupparams) = self::get_groups_sql($courseid);

            $sql = "-- Snap sql
                    SELECT cm.id AS coursemoduleid, a.id AS instanceid, a.course,
                           a.allowsubmissionsfromdate AS opentime, a.duedate AS closetime,
                           count(DISTINCT sb.userid) AS ungraded
                      FROM {assign} a
                      JOIN {course} c ON c.id = a.course
                      JOIN {modules} m ON m.name = 'assign'

                      JOIN {course_modules} cm
                        ON cm.module = m.id
                       AND cm.instance = a.id

                      JOIN {assign_submission} sb
                        ON sb.assignment = a.id
                       AND sb.latest = 1

                      JOIN ($esql) e
                        ON e.id = sb.userid

 -- Start of join required to make assignments marked via gradebook not show as requiring grading
 -- Note: This will lead to disparity between the assignment page (mod/assign/view.php[questionmark]id=[id])
 -- and the module page will still say that 1 item requires grading.

                 LEFT JOIN {assign_grades} ag
                        ON ag.assignment = sb.assignment
                       AND ag.userid = sb.userid
                       AND ag.attemptnumber = sb.attemptnumber

                 LEFT JOIN {grade_items} gi
                        ON gi.courseid = a.course
                       AND gi.itemtype = 'mod'
                       AND gi.itemmodule = 'assign'
                       AND gi.itemnumber = 0
                       AND gi.iteminstance = cm.instance

                 LEFT JOIN {grade_grades} gg
                        ON gg.itemid = gi.id
                       AND gg.userid = sb.userid
                       $sqlgroupsjoin

-- End of join required to make assignments classed as graded when done via gradebook

                     WHERE sb.status = 'submitted'
                       AND a.course = :courseid

                       AND (
                           sb.timemodified > gg.timemodified
                           OR gg.finalgrade IS NULL
                       )

                       AND (a.duedate = 0 OR a.duedate > $since)
                       $sqlgroupswhere
                 $gradetypelimit
                 GROUP BY instanceid, a.course, opentime, closetime, coursemoduleid ORDER BY a.duedate ASC";
            $rs = $DB->get_records_sql($sql, array_merge($params, $groupparams));
            $ungraded = array_merge($ungraded, $rs);
        }

        return $ungraded;
    }

    /**
     * Get Quizzes waiting to be graded.
     *
     * @param array $courseids
     * @param int $since
     * @return array $ungraded
     */
    public static function quiz_ungraded($courseids, $since = null) {
        global $DB;

        if ($since === null) {
            $since = time() - (12 * WEEKSECS);
        }

        $ungraded = array();

        foreach ($courseids as $courseid) {

            // Get people who are typically not students (people who can view grader report) so that we can exclude them!
            list($graderids, $params) = get_enrolled_sql(\context_course::instance($courseid), 'moodle/grade:viewall');
            $params['courseid'] = $courseid;

            $sql = "-- Snap SQL
                    SELECT cm.id AS coursemoduleid, q.id AS instanceid, q.course,
                           q.timeopen AS opentime, q.timeclose AS closetime,
                           count(DISTINCT qa.userid) AS ungraded
                      FROM {quiz} q
                      JOIN {course} c ON c.id = q.course AND q.course = :courseid
                      JOIN {modules} m ON m.name = 'quiz'
                      JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id

-- Get ALL ungraded attempts for this quiz

					  JOIN {quiz_attempts} qa ON qa.quiz = q.id
					   AND qa.sumgrades IS NULL

-- Exclude those people who can grade quizzes

                     WHERE qa.userid NOT IN ($graderids)
                       AND qa.state = 'finished'
                       AND (q.timeclose = 0 OR q.timeclose > $since)
                  GROUP BY instanceid, q.course, opentime, closetime, coursemoduleid
                  ORDER BY q.timeclose ASC";

            $rs = $DB->get_records_sql($sql, $params);
            $ungraded = array_merge($ungraded, $rs);
        }

        return $ungraded;
    }

    // The lesson_ungraded function has been removed as it was very tricky to implement.
    // This was because it creates a grade record as soon as a student finishes the lesson.

    /**
     * Get number of ungraded submissions for specific assignment
     * Based on count_submissions_need_grading() in mod/assign/locallib.php
     *
     * @param int $courseid
     * @param int $modid
     * @return int
     */
    public static function assign_num_submissions_ungraded($courseid, $modid) {
        global $DB;

        static $hasgrades = null;
        static $totalsbyid;

        // Use cache to see if assign has grades.
        if ($hasgrades != null && !isset($hasgrades[$modid])) {
            return 0;
        }

        // Use cache to return number of assigns yet to be graded.
        if (!empty($totalsbyid)) {
            if (isset($totalsbyid[$modid])) {
                return intval($totalsbyid[$modid]->total);
            } else {
                return 0;
            }
        }

        // Check to see if this assign is graded.
        $params = array(
            'courseid'      => $courseid,
            'itemtype'      => 'mod',
            'itemmodule'    => 'assign',
            'gradetypenone' => GRADE_TYPE_NONE,
            'gradetypetext' => GRADE_TYPE_TEXT,
        );

        $sql = 'SELECT DISTINCT iteminstance
                FROM {grade_items}
                WHERE courseid = ?
                AND itemtype = ?
                AND itemmodule = ?
                AND gradetype <> ?
                AND gradetype <> ?';

        $hasgrades = $DB->get_records_sql($sql, $params);

        if (!isset($hasgrades[$modid])) {
            return 0;
        }

        // Get grading information for remaining of assigns.
        $coursecontext = \context_course::instance($courseid);
        list($esql, $params) = get_enrolled_sql($coursecontext, 'mod/assign:submit', 0, true);

        $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $params['courseid'] = $courseid;

        $sql = "-- Snap sql
                 SELECT sb.assignment, count(sb.userid) AS total
                   FROM {assign_submission} sb

                   JOIN {assign} an
                     ON sb.assignment = an.id

              LEFT JOIN {assign_grades} ag
                     ON sb.assignment = ag.assignment
                    AND sb.userid = ag.userid
                    AND sb.attemptnumber = ag.attemptnumber

-- Start of join required to make assignments marked via gradebook not show as requiring grading
-- Note: This will lead to disparity between the assignment page (mod/assign/view.php[questionmark]id=[id])
-- and the module page will still say that 1 item requires grading.

              LEFT JOIN {grade_items} gi
                     ON gi.courseid = an.course
                    AND gi.itemtype = 'mod'
                    AND gi.itemmodule = 'assign'
                    AND gi.itemnumber = 0
                    AND gi.iteminstance = an.id

              LEFT JOIN {grade_grades} gg
                     ON gg.itemid = gi.id
                    AND gg.userid = sb.userid

-- End of join required to make assignments classed as graded when done via gradebook

-- Start of enrolment join to make sure we only include students that are allowed to submit. Note this causes an ALL
-- join on mysql!
                   JOIN ($esql) e
                     ON e.id = sb.userid
-- End of enrolment join

                  WHERE an.course = :courseid
                    AND sb.timemodified IS NOT NULL
                    AND sb.status = :submitted
                    AND sb.latest = 1

                    AND (
                        sb.timemodified > gg.timemodified
                        OR gg.finalgrade IS NULL
                    )

                GROUP BY sb.assignment
               ";

        $totalsbyid = $DB->get_records_sql($sql, $params);
        return isset($totalsbyid[$modid]) ? intval($totalsbyid[$modid]->total) : 0;
    }

    /**
     * Standard function for getting number of submissions (where sql is not complicated and pretty much standard)
     *
     * @param int $courseid
     * @param int $modid
     * @param string $maintable
     * @param string $mainkey
     * @param string $submittable
     * @return int
     */
    protected static function std_num_submissions($courseid,
                                                  $modid,
                                                  $maintable,
                                                  $mainkey,
                                                  $submittable,
                                                  $extraselect = '') {
        global $DB;

        static $modtotalsbyid = array();

        if (!isset($modtotalsbyid[$maintable][$courseid])) {
            // Results are not cached, so lets get them.

            // Get people who are typically not students (people who can view grader report) so that we can exclude them!
            list($graderids, $params) = get_enrolled_sql(\context_course::instance($courseid), 'moodle/grade:viewall');
            $params['courseid'] = $courseid;

            // Get the number of submissions for all $maintable activities in this course.
            $sql = "-- Snap sql
                    SELECT m.id, COUNT(DISTINCT sb.userid) as totalsubmitted
                      FROM {".$maintable."} m
                      JOIN {".$submittable."} sb ON m.id = sb.$mainkey
                     WHERE m.course = :courseid
                           AND sb.userid NOT IN ($graderids)
                           $extraselect
                     GROUP BY m.id";
            $modtotalsbyid[$maintable][$courseid] = $DB->get_records_sql($sql, $params);
        }
        $totalsbyid = $modtotalsbyid[$maintable][$courseid];

        if (!empty($totalsbyid)) {
            if (isset($totalsbyid[$modid])) {
                return intval($totalsbyid[$modid]->totalsubmitted);
            }
        }
        return 0;
    }

    /**
     * Assign module function for getting number of submissions
     *
     * @param int $courseid
     * @param int $modid
     * @return int
     */
    public static function assign_num_submissions($courseid, $modid) {
        global $DB;

        static $modtotalsbyid = array();

        if (!isset($modtotalsbyid['assign'][$courseid])) {
            // Results are not cached, so lets get them.

            list($esql, $params) = get_enrolled_sql(\context_course::instance($courseid), 'mod/assign:submit', 0, true);
            $params['courseid'] = $courseid;
            $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;

            list($sqlgroupsjoin, $sqlgroupswhere, $groupparams) = self::get_groups_sql($courseid);

            // Get the number of submissions for all assign activities in this course.
            $sql = "-- Snap sql
                SELECT m.id, COUNT(sb.userid) as totalsubmitted
                  FROM {assign} m
                  JOIN {assign_submission} sb
                    ON m.id = sb.assignment
                   AND sb.latest = 1

                  JOIN ($esql) e
                    ON e.id = sb.userid
                       $sqlgroupsjoin

                 WHERE m.course = :courseid
                       AND sb.status = :submitted
                       $sqlgroupswhere
                 GROUP by m.id";
            $modtotalsbyid['assign'][$courseid] = $DB->get_records_sql($sql, array_merge($params, $groupparams));
        }
        $totalsbyid = $modtotalsbyid['assign'][$courseid];

        if (!empty($totalsbyid)) {
            if (isset($totalsbyid[$modid])) {
                return intval($totalsbyid[$modid]->totalsubmitted);
            }
        }
        return 0;
    }


    /**
     * Get number of answers for specific choice
     *
     * @param int $courseid
     * @param int $choiceid
     * @return int
     */
    public static function choice_num_submissions($courseid, $modid) {
        return self::std_num_submissions($courseid, $modid, 'choice', 'choiceid', 'choice_answers');
    }

    /**
     * Get number of submissions for feedback activity
     *
     * @param int $courseid
     * @param int $feedbackid
     * @return int
     */
    public static function feedback_num_submissions($courseid, $modid) {
        return self::std_num_submissions($courseid, $modid, 'feedback', 'feedback', 'feedback_completed');
    }

    /**
     * Get number of submissions for lesson activity
     *
     * @param int $courseid
     * @param int $feedbackid
     * @return int
     */
    public static function lesson_num_submissions($courseid, $modid) {
        return self::std_num_submissions($courseid, $modid, 'lesson', 'lessonid', 'lesson_attempts');
    }

    /**
     * Get number of attempts for specific quiz
     *
     * @param int $courseid
     * @param int $quizid
     * @return int
     */
    public static function quiz_num_submissions($courseid, $modid) {
        return self::std_num_submissions($courseid, $modid, 'quiz', 'quiz', 'quiz_attempts');
    }

    /**
     * Get number of ungraded quiz attempts for specific quiz
     *
     * @param int $courseid
     * @param int $quizid
     * @return int
     */
    public static function quiz_num_submissions_ungraded($courseid, $quizid) {
        global $DB;

        static $totalsbyquizid;

        $coursecontext = \context_course::instance($courseid);
        // Get people who are typically not students (people who can view grader report) so that we can exclude them!
        list($graderids, $params) = get_enrolled_sql($coursecontext, 'moodle/grade:viewall');
        $params['courseid'] = $courseid;

        if (!isset($totalsbyquizid)) {
            // Results are not cached.
            $sql = "-- Snap sql
                    SELECT q.id, count(DISTINCT qa.userid) as total
                      FROM {quiz} q

-- Get ALL ungraded attempts for this quiz

					  JOIN {quiz_attempts} qa ON qa.quiz = q.id
					   AND qa.sumgrades IS NULL

-- Exclude those people who can grade quizzes

                     WHERE qa.userid NOT IN ($graderids)
                       AND qa.state = 'finished'
                       AND q.course = :courseid
                     GROUP BY q.id";
            $totalsbyquizid = $DB->get_records_sql($sql, $params);
        }

        if (!empty($totalsbyquizid)) {
            if (isset($totalsbyquizid[$quizid])) {
                return intval($totalsbyquizid[$quizid]->total);
            }
        }

        return 0;
    }

    /**
     * Get activity submission row
     *
     * @param $mod
     * @param $submissiontable
     * @param $modfield
     * @param $tabrow
     * @return mixed
     */
    public static function get_submission_row($courseid, $mod, $submissiontable, $modfield, $extraselect='') {
        global $DB, $USER;

        // Note: Caches all submissions to minimise database transactions.
        static $submissions = array();

        // Pull from cache?
        if (!PHPUNIT_TEST) {
            if (isset($submissions[$courseid.'_'.$mod->modname])) {
                if (isset($submissions[$courseid.'_'.$mod->modname][$mod->instance])) {
                    return $submissions[$courseid.'_'.$mod->modname][$mod->instance];
                } else {
                    return false;
                }
            }
        }

        $submissiontable = $mod->modname.'_'.$submissiontable;

        if ($mod->modname === 'assign') {
            $params = [$courseid, $USER->id];
            $sql = "-- Snap sql
                SELECT a.id AS instanceid, st.*
                    FROM {".$submissiontable."} st

                    JOIN {".$mod->modname."} a
                      ON a.id = st.$modfield

                   WHERE a.course = ?
                     AND st.latest = 1
                     AND st.userid = ? $extraselect
                ORDER BY $modfield DESC, st.id DESC";
        } else {
            // Less effecient general purpose for other module types.
            $params = [$USER->id, $courseid, $USER->id];
            $sql = "-- Snap sql
                SELECT a.id AS instanceid, st.*
                    FROM {".$submissiontable."} st

                    JOIN {".$mod->modname."} a
                      ON a.id = st.$modfield

                    -- Get only the most recent submission.
                    JOIN (SELECT $modfield AS modid, MAX(id) AS maxattempt
                            FROM {".$submissiontable."}
                           WHERE userid = ?
                        GROUP BY modid) AS smx
                      ON smx.modid = st.$modfield
                     AND smx.maxattempt = st.id

                   WHERE a.course = ?
                     AND st.userid = ? $extraselect
                ORDER BY $modfield DESC, st.id DESC";
        }

        // Not every activity has a status field...
        // Add one if it is missing so code assuming there is a status property doesn't explode.
        $result = $DB->get_records_sql($sql, $params);
        if (!$result) {
            unset($submissions[$courseid.'_'.$mod->modname]);
            return false;
        }

        foreach ($result as $r) {
            if (!isset($r->status)) {
                $r->status = null;
            }
        }

        $submissions[$courseid.'_'.$mod->modname] = $result;

        if (isset($submissions[$courseid.'_'.$mod->modname][$mod->instance])) {
            return $submissions[$courseid.'_'.$mod->modname][$mod->instance];
        } else {
            return false;
        }
    }

    /**
     * Take events array and rehash by modulename instance
     * @param array $events
     * @return array
     */
    protected static function hash_events_by_module_instance(array $events) {
        $tmparr = [];
        foreach ($events as $event) {

            if (!isset($tmparr[$event->modulename])) {
                $tmparr[$event->modulename] = [];
            }

            if (!isset($tmparr[$event->modulename][$event->instance])) {
                $tmparr[$event->modulename][$event->instance] = [];
            }

            $tmparr[$event->modulename][$event->instance][] = $event;
        }
        return $tmparr;
    }

    /**
     * Get the activity open from date for a specific module instance
     *
     * @param $courseid
     * @param \cm_info $mod
     * @param string $timeopenfld
     * @param string $timeclosefld
     *
     * @return bool|stdClass
     */
    public static function instance_activity_dates($courseid, \cm_info $mod, $timeopenfld = '', $timeclosefld = '') {
        global $DB, $USER, $COURSE;

        // Note: Caches all moduledates to minimise database transactions.
        static $moddates = [];

        // Did we use the MUC to get the events from the calendar?
        static $eventsfromcache = false;

        // Note: Caches all moduledates by instance to minimise db transactions.
        static $eventsbymodinst = [];

        $modname = $mod->modname;
        $modinst = $mod->instance;

        $phpunittest = defined('PHPUNIT_TEST') && PHPUNIT_TEST;

        if (!empty($moddates[$courseid.'_'.$modname][$modinst]) && !$phpunittest) {
            return $moddates[$courseid.'_'.$modname][$modinst];
        }

        if ($modname === 'quiz') {
            $timeopenfld = 'timeopen';
            $timeclosefld = 'timeclose';
        } else if ($modname === 'lesson') {
            $timeopenfld = 'available';
            $timeclosefld = 'deadline';
        }

        if ($mod->modname != 'assign') {
            // Get moddates WITHOUT overrides.
            $sql = "-- Snap sql
                    SELECT id, $timeopenfld AS timeopen, $timeclosefld as timeclose
                        FROM {" . $modname . "}
                    WHERE course = ?";
            $params = [$courseid];
        } else {
            // Get assignment moddates + time opening overrides.
            // Assignment doesn't put opening time overrides in the calendar so we need to get them here.
            $groups = groups_get_user_groups($courseid);

            if ($groups[0]) {
                list ($groupsql, $params) = $DB->get_in_or_equal($groups[0]);

                $sql = "-- Snap sql
                    SELECT ma.id,
                      CASE
                      WHEN mao.allowsubmissionsfromdate IS NOT NULL
                      THEN mao.allowsubmissionsfromdate
                      ELSE CASE WHEN maog.allowsubmissionsfromdate IS NOT NULL
                      THEN maog.allowsubmissionsfromdate
                      ELSE ma.allowsubmissionsfromdate
                      END
                      END AS timeopen,
                          ma.duedate as timeclose

                     FROM {assign} ma

                LEFT JOIN {assign_overrides} mao ON mao.assignid = ma.id AND mao.userid = ? AND mao.groupid IS NULL
                LEFT JOIN {assign_overrides} maog ON maog.assignid = ma.id AND maog.groupid $groupsql
                      AND maog.sortorder = 1

                    WHERE course = ?";

                array_unshift($params, $USER->id);
                $params[] = $courseid;

            } else {

                $sql = "-- Snap sql
                    SELECT ma.id,
                      CASE
                      WHEN mao.allowsubmissionsfromdate IS NOT NULL
                      THEN mao.allowsubmissionsfromdate
                      ELSE ma.allowsubmissionsfromdate
                      END AS timeopen,
                          ma.duedate as timeclose
                     FROM {assign} ma

                LEFT JOIN {assign_overrides} mao ON mao.assignid = ma.id AND mao.userid = ? AND mao.groupid IS NULL

                    WHERE course = ?";

                $params = [$USER->id, $courseid];
            }

        }
        $moddates[$courseid . '_' . $modname] = $DB->get_records_sql($sql, $params);

        // Override moddates with calendar dates.
        // Note - we only get 1 years of dates to use for overrides, etc.
        // This means 6 months after an override date expires it will show the default date.
        $tz = new \DateTimeZone(\core_date::get_user_timezone($USER));
        $today = new \DateTime('today', $tz);
        $todayts = $today->getTimestamp();
        $tstart = $todayts - (YEARSECS / 2);
        $tend = $todayts + (YEARSECS / 2);

        if ($phpunittest || !isset($eventsbymodinst[$courseid])) {
            if ($COURSE->id = $courseid) {
                $coursesparam = [$courseid => $COURSE];
            } else {
                $coursesparam = [$courseid => get_course($courseid)];
            }
            $cachepfx = 'course'.$courseid.'_';
            $eventsobj = self::user_activity_events($USER, $coursesparam, $tstart, $tend, $cachepfx, 1000);
            $events = $eventsobj->events;
            $eventsfromcache = $eventsobj->fromcache;
            $eventsbymodinst[$courseid] = self::hash_events_by_module_instance($events);
        }

        // Extract opening time and closing time from events.

        if (!empty($eventsbymodinst[$courseid][$modname])) {
            foreach ($eventsbymodinst[$courseid][$modname] as $modinstevents) {
                $timeopen = null;
                $timeclose = null;
                foreach ($modinstevents as $event) {
                    if ($event->timestart === null) {
                        continue;
                    }

                    if ($event->eventtype === 'open') {
                        $timeopen = $event->timestart;
                    } else if (($event->eventtype === 'close' || $event->eventtype === 'due')) {
                        $timeclose = $event->timestart + $event->timeduration;
                    }

                }

                // If we have a null time open or close, use initial dates gotten from module query.
                $initialdates = null;
                if (!empty($moddates[$courseid . '_' . $modname][$event->instance])) {
                    $initialdates = $moddates[$courseid . '_' . $modname][$event->instance];
                }
                if ($timeopen === null && !empty($initialdates)) {
                    $timeopen = $initialdates->timeopen;
                }
                if ($timeclose === null && !empty($initialdates)) {
                    $timeclose = $initialdates->timeclose;
                }

                $instdates = (object)[
                    'timeopen' => $timeopen,
                    'timeclose' => $timeclose,
                    'fromcache' => $eventsfromcache
                ];

                if ($event->modulename === $modname) {
                    // Only statically cache for the current module type we are requesting.
                    $moddates[$courseid . '_' . $modname][$event->instance] = $instdates;
                }
            }
        }

        return $moddates[$courseid.'_'.$modname][$modinst];

    }

    /**
     * Return grade row for specific module instance.
     *
     * @param $courseid
     * @param $mod
     * @param $modfield
     * @return bool
     */
    public static function grade_row($courseid, $mod) {
        global $DB, $USER;

        static $grades = array();

        if (isset($grades[$courseid.'_'.$mod->modname])
            && isset($grades[$courseid.'_'.$mod->modname][$mod->instance])
        ) {
            return $grades[$courseid.'_'.$mod->modname][$mod->instance];
        }

        $sql = "-- Snap sql
                SELECT m.id AS instanceid, gg.*

                    FROM {".$mod->modname."} m

                    JOIN {grade_items} gi
                      ON m.id = gi.iteminstance
                     AND gi.itemtype = 'mod'
                     AND gi.itemmodule = :modname
                     AND gi.courseid = :courseid1

                    JOIN {grade_grades} gg
                      ON gi.id = gg.itemid

                   WHERE m.course = :courseid2
                     AND gg.userid = :userid
                     AND (
                         gg.rawgrade IS NOT NULL
                         OR gg.finalgrade IS NOT NULL
                         OR gg.feedback IS NOT NULL
                     )
                     ";
        $params = array(
            'modname' => $mod->modname,
            'courseid1' => $courseid,
            'courseid2' => $courseid,
            'userid' => $USER->id
        );
        $grades[$courseid.'_'.$mod->modname] = $DB->get_records_sql($sql, $params);

        if (isset($grades[$courseid.'_'.$mod->modname][$mod->instance])) {
            return $grades[$courseid.'_'.$mod->modname][$mod->instance];
        } else {
            return false;
        }
    }

    /**
     * Get everything graded from a specific date to the current date.
     *
     * @param bool $onlyactive - only show grades in courses actively enrolled on if true.
     * @param null|int $showfrom - timestamp to show grades from. Note if not set defaults to 1 month ago.
     * @return mixed
     */
    public static function events_graded($onlyactive = true, $showfrom = null) {
        global $DB, $USER;

        $params = [];
        $coursesql = '';
        if ($onlyactive) {
            $courses = enrol_get_my_courses();
            $courseids = array_keys($courses);
            $courseids[] = SITEID;
            list ($coursesql, $params) = $DB->get_in_or_equal($courseids);
            $coursesql = 'AND gi.courseid '.$coursesql;
        }

        $onemonthago = time() - (DAYSECS * 31);
        $showfrom = $showfrom !== null ? $showfrom : $onemonthago;

        $sql = "-- Snap sql
                SELECT gg.*, gi.itemmodule, gi.iteminstance, gi.courseid, gi.itemtype
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi
                    ON gg.itemid = gi.id $coursesql
                 WHERE gg.userid = ?
                   AND (gg.timemodified > ?
                    OR gg.timecreated > ?)
                   AND (gg.finalgrade IS NOT NULL
                    OR gg.rawgrade IS NOT NULL
                    OR gg.feedback IS NOT NULL)
                   AND gi.itemtype = 'mod'
                 ORDER BY timemodified DESC";

        $params = array_merge($params, [$USER->id, $showfrom, $showfrom]);
        $grades = $DB->get_records_sql($sql, $params, 0, 5);

        $eventdata = array();
        foreach ($grades as $grade) {
            $eventdata[] = $grade;
        }

        return $eventdata;
    }

    /**
     * Note: This function is not optimised for usage in big loops but it does have the advantage of using core logic
     * for evaluating override priority.
     * Get the most appropriate due date, including overrides and extensions.
     * @param int $assignid
     * @param stdClass | int $userid
     * @return stdClass
     * @throws \coding_exception
     */
    public static function assignment_due_date_info($assignid, $userid) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/assign/locallib.php');

        $duedateinfo = (object) ['duedate' => null, 'extended' => false];

        list ($course, $cminfo) = get_course_and_cm_from_instance($assignid, 'assign');
        unset($course);

        // Check overrides.
        $assign = new \assign($cminfo->context, $cminfo, false);
        $overrides = $assign->override_exists($userid);
        if (!empty($overrides->duedate)) {
            $duedate = $overrides->duedate;
        } else {
            $duedate = $assign->get_instance()->duedate;
        }

        // Check deadline extensions.
        $flags = $assign->get_user_flags($userid, true);
        if (!empty($flags->extensionduedate)) {
            // Extension always overwrites duedate, even if it's less than due date or overridden due date.
            $duedate = $flags->extensionduedate;
            $duedateinfo->extended = true;
        }

        $duedateinfo->duedate = $duedate;
        return $duedateinfo;
    }

    /**
     * Get all events restricted by various parameters, taking in to account user and group overrides.
     * Copied from calendar/classes/local/api.php.
     * Uses
     *
     * @param int|null      $timestartfrom         Events with timestart from this value (inclusive).
     * @param int|null      $timestartto           Events with timestart until this value (inclusive).
     * @param int|null      $timesortfrom          Events with timesort from this value (inclusive).
     * @param int|null      $timesortto            Events with timesort until this value (inclusive).
     * @param int|null      $timestartaftereventid Restrict the events in the timestart range to ones after this ID.
     * @param int|null      $timesortaftereventid  Restrict the events in the timesort range to ones after this ID.
     * @param int           $limitnum              Return at most this number of events.
     * @param int|null      $type                  Return only events of this type.
     * @param array|null    $usersfilter           Return only events for these users.
     * @param array|null    $groupsfilter          Return only events for these groups.
     * @param array|null    $coursesfilter         Return only events for these courses.
     * @param bool          $withduration          If true return only events starting within specified
     *                                             timestart otherwise return in progress events as well.
     * @param bool          $ignorehidden          If true don't return hidden events.
     * @return \core_calendar\local\event\entities\event_interface[] Array of event_interfaces.
     */
    public static function get_events(
        $timestartfrom = null,
        $timestartto = null,
        $timesortfrom = null,
        $timesortto = null,
        $timestartaftereventid = null,
        $timesortaftereventid = null,
        $limitnum = 20,
        $type = null,
        array $usersfilter = null,
        array $groupsfilter = null,
        array $coursesfilter = null,
        $withduration = true,
        $ignorehidden = true
    ) {

        \theme_snap\calendar\event\container::ovd_init();
        $vault = \theme_snap\calendar\event\container::get_event_vault();

        $timestartafterevent = null;
        $timesortafterevent = null;

        if ($timestartaftereventid && $event = $vault->get_event_by_id($timestartaftereventid)) {
            $timestartafterevent = $event;
        }

        if ($timesortaftereventid && $event = $vault->get_event_by_id($timesortaftereventid)) {
            $timesortafterevent = $event;
        }

        return $vault->get_events(
            $timestartfrom,
            $timestartto,
            $timesortfrom,
            $timesortto,
            $timestartafterevent,
            $timesortafterevent,
            $limitnum,
            $type,
            $usersfilter,
            $groupsfilter,
            $coursesfilter,
            null,
            $withduration,
            $ignorehidden
        );
    }

    /**
     * Get calendar activity events for specific date range and array of courses.
     * Note - only deals with due, open, close event types.
     * @param int $tstart
     * @param int $tend
     * @param \stdClass[] $courses
     * @return array
     */
    public static function get_calendar_activity_events($tstart, $tend, array $courses, $limit = 40) {

        $calendar = new \calendar_information(0, 0, 0, $tstart);
        $course = get_course(SITEID);
        $calendar->set_sources($course, $courses);

        $withduration = true;
        $ignorehidden = true;
        $mapper = \core_calendar\local\event\container::get_event_mapper();

        // Normalise the users, groups and courses parameters so that they are compliant with
        // the calendar apis get_events method.
        // Existing functions that were using the old calendar_get_events() were passing a mixture of array, int,
        // boolean for these parameters, but with the new API method, only null and arrays are accepted.
        list($userparam, $groupparam, $courseparam) = array_map(function($param) {
            // If parameter is true, return null.
            if ($param === true) {
                return null;
            }

            // If parameter is false, return an empty array.
            if ($param === false) {
                return [];
            }

            // If the parameter is a scalar value, enclose it in an array.
            if (!is_array($param)) {
                return [$param];
            }

            // No normalisation required.
            return $param;
        }, [$calendar->users, $calendar->groups, $calendar->courses]);

        $events = self::get_events(
            $tstart,
            $tend,
            null,
            null,
            null,
            null,
            $limit,
            null,
            $userparam,
            $groupparam,
            $courseparam,
            $withduration,
            $ignorehidden
        );

        return array_reduce($events, function($carry, $event) use ($mapper) {
            return $carry + [$event->get_id() => $mapper->from_event_to_stdclass($event)];
        }, []);
    }

    /**
     * Return user's deadlines from calendar.
     *
     * @param int|stdClass $userorid
     * @param stdClass[] $courses array of courses hashed by course id.
     * @param int $tstart
     * @param int $tend
     * @param string $cacheprefix
     * @param int $limit
     * @return stdClass
     */
    public static function user_activity_events($userorid, array $courses, $tstart, $tend, $cacheprefix = '',
                                                $limit = 40) {

        $retobj = (object) [
            'timestamp' => null,
            'events' => [],
            'fromcache' => false
        ];

        $user = local::get_user($userorid);
        if (!$user) {
            return $retobj;
        }

        // The cache key includes the start and end dates rounded to a day.
        $dstart = strtotime(date('Y-m-d', $tstart));
        $dend = strtotime(date('Y-m-d', $tend));

        $cachekey = $cacheprefix.$user->id.'_'.($dstart + $dend).'_'.$limit;

        if (self::$phpunitallowcaching || !(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            $muc = \cache::make('theme_snap', 'activity_deadlines');
            $cached = $muc->get($cachekey);

            if ($cached && $cached->timestamp >= time() - HOURSECS) {

                $cachestamps = local::get_calendar_change_stamps();

                $activitiesstamp = $cached->timestamp;
                $cachefresh = true; // Until proven otherwise.

                foreach ($courses as $courseid => $course) {
                    if (isset($cachestamps[$courseid])) {
                        $stamp = $cachestamps[$courseid];
                        if ($stamp > $activitiesstamp) {
                            $cachefresh = false;
                        }
                    }
                }

                if ($cachefresh) {
                    $cached->fromcache = true; // Useful for debugging and unit testing.
                    return $cached;
                }
            }
        }

        if (empty($courses)) {
            return $retobj;
        }

        $events = self::get_calendar_activity_events($tstart, $tend, $courses, $limit);

        // Filter down array and also modify event name if necessary;
        // Note, filter_array cannot be used here as we need to modify the event name, not just filter.
        $tmparr = [];
        foreach ($events as $event) {

            /** @var cm_info $cminfo */
            list ($course, $cminfo) = get_course_and_cm_from_instance($event->instance, $event->modulename);
            unset($course);

            // We are only interested in modules with valid instances.
            if (empty($cminfo)) {
                continue;
            }

            if (!$cminfo->uservisible) {
                continue;
            }
            if ($event->eventtype === 'close') {
                // Revert the addition of e.g. "(Quiz closes)" to the event name.
                $event->name = $cminfo->name;
            }

            if (isset($courses[$event->courseid])) {
                $course = $courses[$event->courseid];
                $event->coursefullname = format_string($course->fullname);
            }

            $tmparr[$event->id] = $event;

        }
        $events = $tmparr;
        unset($tmparr);

        $retobj->timestamp = microtime(true);
        $retobj->events = $events;

        if (self::$phpunitallowcaching || !(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            $muc->set($cachekey, $retobj);
        }

        return $retobj;
    }

    /**
     * Return user's upcoming activity deadlines from the calendar.
     *
     * All deadlines from today, then any from the next 6 months up to the
     * max requested.
     * @param \stdClass|integer $userorid
     * @param integer $maxdeadlines
     * @return array
     */
    public static function upcoming_deadlines($userorid, $maxdeadlines = 5) {

        global $USER;
        $origuser = $USER;
        $user = local::get_user($userorid);
        $USER = $user;

        $tz = new \DateTimeZone(\core_date::get_user_timezone($user));
        $today = new \DateTime('today', $tz);
        $todayts = $today->getTimestamp();
        $tomorrow = new \DateTime('tomorrow', $tz);
        $tomorrowts = $tomorrow->getTimestamp();

        $courses = enrol_get_users_courses($user->id, true);

        $eventsobj = self::user_activity_events($user, $courses, $todayts, $todayts + (YEARSECS / 2), 'deadlines');

        $events = $eventsobj->events;
        uasort($events, function($e1, $e2) {
            if ($e1->timestart === $e2->timestart) {
                return 0;
            }
            return ($e1->timestart < $e2->timestart) ? -1 : 1;
        });

        $counteventstoday = 0;

        $tmparr = [];
        foreach ($events as $event) {
            if ($event->timestart >= $todayts) {
                if ($event->eventtype != 'close' && $event->eventtype != 'due' && $event->eventtype != 'expectcompletionon') {
                    continue;
                }

                $tmparr[] = $event;

                if ($event->timestart < $tomorrowts) {
                    $counteventstoday++;
                }
            }
        }
        $events = $tmparr;

        // We have unlimited events for today but a maximum of five events for everything passed today.
        // If we have 10 events today then we will see 10 events, if we have 3 events for today then we will see
        // a maximum of 5 events including all the events that happen beyond today's date.
        $maxevents = $counteventstoday > $maxdeadlines ? $counteventstoday : $maxdeadlines;

        $eventsobj->events = array_slice($events, 0, $maxevents);

        $USER = $origuser;

        return $eventsobj;
    }

    /**
     * Returns the join and where statements required to validate the assignment submissions by groups on a course.
     * @param integer $courseid
     * @return array
     */
    private static function get_groups_sql($courseid) {
        global $USER;

        $sqlgroupsjoin = '';
        $sqlgroupswhere = '';
        $groupparams = array();

        $course = get_course($courseid);
        $groupmode = groups_get_course_groupmode($course);
        $context = \context_course::instance($courseid);

        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            $groupparams['userid'] = $USER->id;
            $groupparams['courseid2'] = $courseid;

            $sqlgroupsjoin = "
                    JOIN {groups_members} gm
                      ON gm.userid = sb.userid
                    JOIN {groups} g
                      ON gm.groupid = g.id";
            $sqlgroupswhere = "
                     AND gm.groupid
                      IN (SELECT g.id
                    FROM {groups} g
                    JOIN {groups_members} gm ON gm.groupid = g.id
                   WHERE g.courseid = :courseid2
                     AND gm.userid = :userid)";
        }
        return array($sqlgroupsjoin, $sqlgroupswhere, $groupparams);
    }

}
