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

require_once($CFG->dirroot.'/mod/assign/locallib.php');

/**
 * Activity functions.
 * These functions are in a class purely for auto loading convenience.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class activity {

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
     * @return bool | \theme_snap\activity_meta
     */
    protected static function std_meta(\cm_info $mod,
                                       $timeopenfld,
                                       $timeclosefld,
                                       $keyfield,
                                       $submissiontable,
                                       $submittedonfld,
                                       $submitstrkey,
                                       $isgradeable = false,
                                       $submitselect = ''
    ) {
        global $USER;

        $courseid = $mod->course;

        // Create meta data object.
        $meta = new \theme_snap\activity_meta();
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
        if (!$mod->uservisible) {
            return $meta;
        }

        $activitydates = self::instance_activity_dates($courseid, $mod, $timeopenfld, $timeclosefld);
        $meta->timeopen = $activitydates->timeopen;
        $meta->timeclose = $activitydates->timeclose;

        // TODO: use activity specific "teacher" capabilities.
        if (has_capability('mod/assign:grade', \context_course::instance($courseid))) {
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
            if (empty($activitydates->timeopen) || usertime($activitydates->timeopen) <= time()) {

                $submissionrow = self::get_submission_row($courseid, $mod, $submissiontable, $keyfield, $submitselect);

                if (!empty($submissionrow)) {
                    if ($submissionrow->status) {
                        switch ($submissionrow->status) {
                            case 'draft' : $meta->draft = true; break;
                            case 'reopened' : $meta->reopened = true; break;
                            case 'submitted' : $meta->submitted = true; break;
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
                $graderow = self::grade_row($courseid, $mod, $keyfield);
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
        return $meta;
    }

    /**
     * Get assignment meta data
     *
     * @param cm_info $modinst - module instance
     * @return string
     */
    public static function assign_meta(\cm_info $modinst) {
        global $DB;
        static $submissionsenabled;

        $courseid = $modinst->course;

        // Get count of enabled submission plugins grouped by assignment id.
        if (empty($submissionsenabled)) {
            $sql = "SELECT a.id, count(1) AS submissionsenabled
                      FROM {assign} a
                      JOIN {assign_plugin_config} ac ON ac.assignment = a.id
                     WHERE a.course = ?
                       AND ac.name='enabled'
                       AND ac.value=1
                       AND ac.subtype='assignsubmission'
                       AND plugin!='comments'
                  GROUP BY a.id;";
            $submissionsenabled = $DB->get_records_sql($sql, array($courseid));
        }

        $submitselect = '';

        $meta = self::std_meta($modinst, 'allowsubmissionsfromdate', 'duedate', 'assignment', 'submission',
            'timemodified', 'submitted', true, $submitselect);

        // If there aren't any submission plugins enabled for this module, then submissions are not required.
        if (empty($submissionsenabled[$modinst->instance])) {
            $meta->submissionnotrequired = true;
        }
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
     * @param $assignmentid
     * @return array $ungraded
     */
    public static function assign_ungraded($courseids) {
        global $DB;

        $ungraded = array();

        $sixmonthsago = time() - YEARSECS / 2;

        foreach ($courseids as $courseid) {

            list($esql, $params) = get_enrolled_sql(\context_course::instance($courseid), 'mod/assign:submit', 0, true);
            $params['courseid'] = $courseid;

            $submissionmaxattempt = 'SELECT mxs.assignment AS assignid, mxs.userid, MAX(mxs.attemptnumber) AS maxattempt
                                 FROM {assign_submission} mxs
                                 GROUP BY assignid, mxs.userid';

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

                      JOIN ($submissionmaxattempt) smx
                        ON sb.assignment = smx.assignid

                      JOIN ($esql) e
                        ON e.id = sb.userid

 -- Start of join required to make assignments marked via gradebook not show as requiring grading
 -- Note: This will lead to disparity between the assignment page (mod/assign/view.php[questionmark]id=[id])
 -- and the module page will still say that 1 item requires grading.

                 LEFT JOIN {assign_grades} ag
                        ON ag.assignment = sb.assignment
                       AND ag.userid = sb.userid
                       AND ag.attemptnumber = smx.maxattempt

                 LEFT JOIN {grade_items} gi
                        ON gi.courseid = a.course
                       AND gi.itemtype = 'mod'
                       AND gi.itemmodule = 'assign'
                       AND gi.itemnumber = 0
                       AND gi.iteminstance = cm.instance

                 LEFT JOIN {grade_grades} gg
                        ON gg.itemid = gi.id
                       AND gg.userid = sb.userid

-- End of join required to make assignments classed as graded when done via gradebook

                     WHERE sb.status = 'submitted'
                       AND a.course = :courseid
                       AND sb.assignment = smx.assignid
                       AND sb.attemptnumber = smx.maxattempt

                       AND (
                           sb.timemodified > gg.timemodified
                           OR gg.finalgrade IS NULL
                       )

                       AND sb.userid = smx.userid
                       AND (a.duedate = 0 OR a.duedate > $sixmonthsago)
                  GROUP BY instanceid, a.course, opentime, closetime, coursemoduleid ORDER BY a.duedate ASC";
            $rs = $DB->get_records_sql($sql, $params);
            $ungraded = array_merge($ungraded, $rs);
        }

        return $ungraded;
    }

    /**
     * Get Quizzes waiting to be graded.
     *
     * @param $assignmentid
     * @return array $ungraded
     */
    public static function quiz_ungraded($courseids) {
        global $DB;

        $sixmonthsago = time() - YEARSECS / 2;

        $ungraded = array();

        foreach ($courseids as $courseid) {

            // Get people who are typically not students (people who can view grader report) so that we can exclude them!
            list($graderids, $params) = get_enrolled_sql(\context_course::instance($courseid), 'moodle/grade:viewall');
            $params['courseid'] = $courseid;

            // Note, that unlike assessments we don't check the gradebook as the only time a quiz needs to be marked is
            // when there are essay questions or other questions that require manual marking. As these questions need
            // to be marked manually the operation should always take place via the module.
            $sql = "-- Snap sql
                    SELECT cm.id AS coursemoduleid, q.id AS instanceid, q.course,
                           q.timeopen AS opentime, q.timeclose AS closetime,
                           count(DISTINCT qa.userid) AS ungraded
                      FROM {quiz} q
                      JOIN {course} c ON c.id = q.course
                      JOIN {modules} m ON m.name = 'quiz'
                      JOIN {course_modules} cm ON cm.module = m.id
                           AND cm.instance = q.id
                      JOIN {quiz_attempts} qa ON qa.quiz = q.id
                 LEFT JOIN {quiz_grades} gd ON gd.quiz = qa.quiz
                           AND gd.userid = qa.userid
                     WHERE gd.id IS NULL
                           AND q.course = :courseid
                           AND qa.userid NOT IN ($graderids)
                           AND qa.state = 'finished'
                           AND (q.timeclose = 0 OR q.timeclose > $sixmonthsago)
                  GROUP BY instanceid, q.course, opentime, closetime, coursemoduleid ORDER BY q.timeclose ASC";

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

        static $totalsbyid;

        if (!empty($totalsbyid)) {
            if (isset($totalsbyid[$modid])) {
                return intval($totalsbyid[$modid]->total);
            } else {
                return 0;
            }
        }
        $coursecontext = \context_course::instance($courseid);
        list($esql, $params) = get_enrolled_sql($coursecontext, 'mod/assign:submit', 0, true);

        $submissionmaxattempt = 'SELECT mxs.assignment AS assignid, mxs.userid, MAX(mxs.attemptnumber) AS maxattempt
                                 FROM {assign_submission} mxs
                                 GROUP BY assignid, mxs.userid';

        $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $params['courseid'] = $courseid;

        $sql = "-- Snap sql
                 SELECT sb.assignment, count(sb.userid) AS total
                   FROM {assign_submission} sb

                   JOIN {assign} an
                     ON sb.assignment = an.id

                   JOIN ($submissionmaxattempt) smx
                     ON sb.assignment = smx.assignid

              LEFT JOIN {assign_grades} ag
                     ON sb.assignment = ag.assignment
                    AND sb.userid = ag.userid
                    AND ag.attemptnumber = smx.maxattempt

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

                   JOIN ($esql) e
                     ON e.id = sb.userid

                  WHERE an.course = :courseid
                    AND sb.timemodified IS NOT NULL
                    AND sb.status = :submitted

                    AND sb.userid = smx.userid
                    AND sb.attemptnumber = smx.maxattempt

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
                     GROUP by m.id";
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

            $submissionmaxattempt = 'SELECT mxs.assignment AS assignid, mxs.userid, MAX(mxs.attemptnumber) AS maxattempt
                                 FROM {assign_submission} mxs
                                 GROUP BY assignid, mxs.userid';

            // Get the number of submissions for all assign activities in this course.
            $sql = "-- Snap sql
                SELECT m.id, COUNT(sb.userid) as totalsubmitted
                  FROM {assign} m
                  JOIN {assign_submission} sb ON m.id = sb.assignment
                  JOIN ($submissionmaxattempt) smx
                 ON sb.userid = smx.userid
                AND sb.assignment = smx.assignid

                  JOIN ($esql) e
                    ON e.id = sb.userid

                 WHERE m.course = :courseid
                       AND sb.attemptnumber = smx.maxattempt
                       AND sb.status = :submitted
                 GROUP by m.id";
            $modtotalsbyid['assign'][$courseid] = $DB->get_records_sql($sql, $params);
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
            // Get the number of attempts that requiring marking for all quizes in this course.
            $sql = "-- Snap sql
                    SELECT q.id, count(*) as total
                      FROM {quiz_attempts} sb
                 LEFT JOIN {quiz} q ON q.id=sb.quiz
                 LEFT JOIN {quiz_grades} gd ON gd.quiz = sb.quiz
                           AND gd.userid = sb.userid
                     WHERE sb.timefinish IS NOT NULL
                           AND gd.id IS NULL
                           AND q.course = :courseid
                           AND sb.userid NOT IN ($graderids)
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
        if (isset($submissions[$courseid.'_'.$mod->modname])) {
            if (isset($submissions[$courseid.'_'.$mod->modname][$mod->instance])) {
                return $submissions[$courseid.'_'.$mod->modname][$mod->instance];
            } else {
                return false;
            }
        }

        $submissiontable = $mod->modname.'_'.$submissiontable;
        $sql = "-- Snap sql
                SELECT a.id AS instanceid, st.*
                    FROM {".$submissiontable."} st

                    JOIN {".$mod->modname."} a
                      ON a.id = st.$modfield

-- Get only the most recent submission.
                    JOIN (SELECT $modfield as modid, MAX(id) as maxattempt
                            FROM {".$submissiontable."}
                           WHERE userid = ?
                        GROUP BY modid) as smx
                      ON smx.modid = st.$modfield
                     AND smx.maxattempt = st.id

                   WHERE a.course = ?
                     AND userid = ? $extraselect
                ORDER BY $modfield DESC, st.id DESC";
        $submissions[$courseid.'_'.$mod->modname] = $DB->get_records_sql($sql,
            array($USER->id, $courseid, $USER->id));

        if (isset($submissions[$courseid.'_'.$mod->modname][$mod->instance])) {
            return $submissions[$courseid.'_'.$mod->modname][$mod->instance];
        } else {
            return false;
        }
    }

    /**
     * Get the activity dates for a specific module instance
     *
     * @param $courseid
     * @param stdClass $mod
     * @param $timeopenfld
     * @param $timeclosefld
     *
     * @return bool|stdClass
     */
    public static function instance_activity_dates($courseid, $mod, $timeopenfld, $timeclosefld) {
        global $DB;

        // Note: Caches all moduledates to minimise database transactions.
        static $moddates = array();

        if (!isset($moddates[$courseid.'_'.$mod->modname][$mod->instance])) {
            $sql = "-- Snap sql
                    SELECT id, $timeopenfld AS timeopen, $timeclosefld as timeclose
                        FROM {".$mod->modname."}
                    WHERE course = ?";
            $moddates[$courseid.'_'.$mod->modname] = $DB->get_records_sql($sql, array($courseid));

        }
        return $moddates[$courseid.'_'.$mod->modname][$mod->instance];
    }

    /**
     * Return grade row for specific module instance.
     *
     * @param $courseid
     * @param $mod
     * @param $modfield
     * @return bool
     */
    public static function grade_row($courseid, $mod, $modfield) {
        global $DB, $USER;

        static $grades = array();

        if (isset($grades[$courseid.'_'.$mod->modname])
            && isset($grades[$courseid.'_'.$mod->modname][$mod->instance])
        ) {
            return $grades[$courseid.'_'.$mod->modname][$mod->instance];
        }

        $gradetable = $mod->modname.'_grades';
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
     * @param null|int $showfrom - timestamp to show grades from. Note if not set defaults to 1 month ago.
     * @return mixed
     */
    public static function events_graded($showfrom = null) {
        global $DB, $USER;

        $onemonthago = time() - (DAYSECS * 31);
        $showfrom = $showfrom !== null ? $showfrom : $onemonthago;

        $sql = "-- Snap sql
                SELECT gg.*, gi.itemmodule, gi.iteminstance, gi.courseid, gi.itemtype
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi
                    ON gg.itemid = gi.id
                 WHERE gg.userid = ?
                   AND (gg.timemodified > ?
                    OR gg.timecreated > ?)
                   AND (gg.finalgrade IS NOT NULL
                    OR gg.rawgrade IS NOT NULL
                    OR gg.feedback IS NOT NULL)
                   AND gi.itemtype = 'mod'
                 ORDER BY timemodified DESC";

        $params = array($USER->id, $showfrom, $showfrom);
        $grades = $DB->get_records_sql($sql, $params, 0, 5);

        $eventdata = array();
        foreach ($grades as $grade) {
            $eventdata[] = $grade;
        }

        return $eventdata;
    }
}
