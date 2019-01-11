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
 * Snap assignment renderer.
 * Overrides core assignment renderer, this code is not great.
 * Unfortunatly tried to keep as close to original renderer for maintainability.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use assign_submission_status;
use assign_submission_plugin_submission;
use assign_feedback_plugin_feedback;
use theme_snap\activity;

require_once($CFG->dirroot.'/mod/assign/renderer.php');

class mod_assign_renderer extends \mod_assign_renderer {

    /**
     * Copied from assign/renderer.php
     * Displayed for all users.
     * Render the header.
     *
     * @param assign_header $header
     * @return string
     */
    public function render_assign_header(\assign_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'assign'));
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->assign->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro) {
            $o .= '<div class="assign-intro">';
            $o .= $this->render_assign_duedate($header);
            $o .= '<div class="p-y-1">';
            $o .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            $o .= $header->postfix;
            $o .= '</div>';
            $o .= '</div>';
        } else {
            $o .= '<div class="assign-intro">';
            $o .= $this->render_assign_duedate($header);
            $o .= '</div>';
        }
        return $o;
    }

    /**
     *
     * Displayed for all users.
     * Render the assignment due date and associated due data.
     *
     * @param assign_header $header
     * @return string
     */
    public function render_assign_duedate(\assign_header $header) {
        global $USER;

        $status = $header->assign;

        $duedateinfo = activity::assignment_due_date_info($status->id, $USER->id);
        $duedate = $duedateinfo->duedate;

        $time = time();
        $duedata = '';
        if ($duedate > 0) {
            // Allow submissions from.
            if ($status->allowsubmissionsfromdate && $time <= $status->allowsubmissionsfromdate) {
                $date = userdate($status->allowsubmissionsfromdate);
                $duedata .= '<div>'.get_string('allowsubmissionsfromdatesummary', 'assign', $date).'</div>';
            }

            // Due date.
            $duedata .= '<div>'.get_string('due', 'theme_snap', userdate($duedate)).'</div>';

            // Time remaining.
            if ($duedate - $time >= 0) {
                $due = format_time($duedate - $time);
                $duedata .= '<div>'.get_string('timeremaining', 'assign').': '.$due.'</div>';
            }

            // Tell user they have been granted an extension.
            if ($duedateinfo->extended) {
                $duedata .= '<div>'.get_string('eventextensiongranted', 'assign').'</div>';
            }

            // Late submissions data.
            if ($duedate < $time) {
                $cutoffdate = $status->cutoffdate;
                if ($cutoffdate) {
                    $late = get_string('nomoresubmissionsaccepted', 'assign');
                    if ($cutoffdate > $time) {
                        $late = get_string('latesubmissionsaccepted', 'assign', userdate($status->cutoffdate));
                    }
                    $duedata .= '<div>'.get_string('latesubmissions', 'assign').': '.$late.'</div>';
                }
            }
        }

        if (empty($duedate)) {
            if ($status->allowsubmissionsfromdate && $time <= $status->allowsubmissionsfromdate) {
                $date = userdate($status->allowsubmissionsfromdate);
                $duedata .= '<div>'.get_string('allowsubmissionsfromdatesummary', 'assign', $date).'</div>';
            }
        }

        return '<div class="duedata">'.$duedata.'</div>';
    }

    /**
     * Copied from assign/renderer.php
     * Displayed for editors.
     * Render the current status of the grading process.
     *
     * @param assign_grading_summary $summary
     * @return string
     */
    public function render_assign_grading_summary(\assign_grading_summary $summary) {
        $o = '<div class="assign-grading-summary">';
        // Needs grading and draft data.
        $needsgradingdata = '';
        if ($summary->submissionsenabled) {
            // Ungraded submissions.
            if ($summary->submissionsneedgradingcount > 0) {
                $needsgradingdata .= '<div>';
                $needsgradingdata .= get_string('numberofsubmissionsneedgrading', 'assign').' ';
                $needsgradingdata .= '<span class="badge badge-success pull-right">'.
                    $summary->submissionsneedgradingcount.
                    '</span>';
                $needsgradingdata .= '</div>';
            }
            // Team submission - don't show needs grading count.
            if ($summary->teamsubmission) {
                $needsgradingdata .= '';
            }
        }
        // Drafts count. Don't show drafts count when using offline assignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $needsgradingdata .= '<div>'.get_string('numberofdraftsubmissions', 'assign').
                ' <span class="pull-right badge badge-warning">'.$summary->submissiondraftscount.'</span></div>';
        }
        $o .= '<div class="needsgradingdata">'.$needsgradingdata.'</div>';

        // Number of submissions.
        $submissionsdata = '';
        if ($summary->teamsubmission) {
            // Team submissions.
            if ($summary->warnofungroupedusers) {
                $submissionsdata = '<div class="alert alert-danger">'.get_string('ungroupedusers', 'assign').'</div>';
            }
            // SHAME - participantcount is replaced by number of groups in teamsubmission - not sure why.
            // On the page this seems to only add confusion.
            $submissionsdata .= '<div>'.get_string('numberofteams', 'assign').': '.$summary->participantcount.'</div>';
            $submissionsdata .= '<div>'.get_string('numberofsubmittedassignments', 'assign').': '.
                    $summary->submissionssubmittedcount.'</div>';
        } else {
            // Single submissions.
            $percentage = 0;
            if ($summary->participantcount) {
                $percentage = round(($summary->submissionssubmittedcount / $summary->participantcount), 3) * 100 . '%';
            }
            $submissionsdata = '<div class="submissions-status">';
            $submissionsdata .= get_string('submissions', 'assign').': ';
            $submissionsdata .= '<div class="submission-status-row">';
            $submissionsdata .= '<span>'.$summary->submissionssubmittedcount.' / '.$summary->participantcount.'</span>';
            $submissionsdata .= '<span>'.$percentage.'</span>';
            $submissionsdata .= '</div>';
            $submissionsdata .= '<br><div class="submissions-line" style="width:'.$percentage.'"></div>';
            $submissionsdata .= '</div>';
        }
        $o .= '<div class="submissionsdata">'.$submissionsdata.'</div>';

        // View all submissions link.
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grading');
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        $o .= '<a href="'.$url.'" class="pull-right btn btn-link">'.get_string('viewgrading', 'mod_assign').'</a>';
        // Grade button.
        $urlparams = array('id' => $summary->coursemoduleid, 'action' => 'grader');
        $url = new moodle_url('/mod/assign/view.php', $urlparams);
        $o .= '<a href="'.$url.'" role="button" class="btn btn-primary">'.get_string('grade').'</a>';
        // Close assign-grading-summary.
        $o .= '</div>';
        return $o;
    }

    /**
     * Copied from assign/renderer.php
     * Displayed for students.
     * Render the current status of the submission.
     *
     * @param assign_submission_status $status
     * @return string
     */
    public function render_assign_submission_status(\assign_submission_status $status) {
        global $USER, $OUTPUT;

        // User picture and name.
        $userpicture = new \user_picture($USER);
        $userpicture->link = false;
        $userpicture->alttext = false;
        $userpicture->class = 'userpicture';
        $userpicture->size = 35;
        $userpic = $OUTPUT->render($userpicture).' '.s(fullname($USER));

        $o = '';
        $statusdata = '';
        // Team submissions - group name, warning.
        if ($status->teamsubmissionenabled) {
            // Team.
            $team = get_string('submissionteam', 'assign').': ';
            $group = $status->submissiongroup;
            if ($group) {
                $statusdata .= '<h4 class="h6">'.$team.format_string($group->name, false, $status->context).'</h4>';
            } else {
                $statusdata .= '<h4 class="h6">'.$team.get_string('defaultteam', 'assign').'</h4>';
            }

            if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $warning = get_string('noteam', 'assign').'.<br>'.get_string('noteam_desc', 'assign');
                    $statusdata .= '<div class="alert alert-danger">'.$warning.'</div>';
                } else if (count($status->usergroups) > 1) {
                    $warning = get_string('multipleteams', 'assign').'.<br>'.get_string('multipleteams_desc', 'assign');
                    $statusdata .= '<div class="alert alert-danger">'.$warning.'</div>';
                }
            }
        }

        // Attempt number.
        if ($status->attemptreopenmethod != ASSIGN_ATTEMPT_REOPEN_METHOD_NONE) {
            $currentattempt = 1;
            if ($status->teamsubmissionenabled && $status->teamsubmission) {
                // Team.
                $currentattempt = $status->teamsubmission->attemptnumber + 1;
            } else if ($status->submission) {
                // Single user.
                $currentattempt = $status->submission->attemptnumber + 1;
            }

            $statusdata .= '<div>'.get_string('attemptnumber', 'assign').'<br>';
            $maxattempts = $status->maxattempts;
            if ($maxattempts == ASSIGN_UNLIMITED_ATTEMPTS) {
                $attempts = get_string('currentattempt', 'assign', $currentattempt);
            } else {
                $attempts = get_string('currentattemptof', 'assign', array('attemptnumber' => $currentattempt,
                                                                          'maxattempts' => $maxattempts));
            }
            $statusdata .= $attempts;
            $statusdata .= '</div>';
        }

        // Status.
        // Add a tick if submitted.
        $tick = '';
        if (is_object($status->submission) && property_exists($status->submission, 'status')
                && $status->submission->status === 'submitted') {
            $tick = '<i class="icon fa fa-check text-success fa-fw " aria-hidden="true" role="presentation"></i>';
        }
        $statusdata .= '<div>'.$tick.'<strong>'
                .get_string('submissionstatus', 'assign').':</strong>';

        $statusstr = '';
        if ($status->teamsubmissionenabled) {
            // Team.
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $statusstr = get_string('nosubmission', 'assign');
            } else if ($status->teamsubmission && $status->teamsubmission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $submissionsummary = get_string('submissionstatus_'.$teamstatus, 'assign');
                $groupid = 0;
                if ($status->submissiongroup) {
                    $groupid = $status->submissiongroup->id;
                }

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course' => $status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == assign_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'assign', $userstr);
                    $submissionsummary .= $this->output->container($formatteduserstr);
                }
                $statusstr = $submissionsummary;
            } else {
                if (!$status->submissionsenabled) {
                    $statusstr = get_string('noonlinesubmissions', 'assign');
                } else {
                    $statusstr = get_string('nosubmission', 'assign');
                }
            }
        } else {
            // Single user.
            if ($status->submission && $status->submission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $statusstr = get_string('submissionstatus_'.$status->submission->status, 'assign');
            } else {
                if (!$status->submissionsenabled) {
                    $statusstr = get_string('noonlinesubmissions', 'assign');
                } else {
                    $statusstr = get_string('noattempt', 'assign');
                }
            }
        }

        $statusdata .= '<span class="submissionstatus">'.$statusstr.'</span>';

        // Is locked?
        if ($status->locked) {
            $statusdata .= '<br>'.get_string('submissionslocked', 'assign');
        }
        $statusdata .= '</div>';

        // Grading status.
        $tick = '';
        if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
                $tick = '<i class="icon fa fa-check text-success fa-fw " aria-hidden="true" role="presentation"></i>';
        }
        $gradingdata = '<div>'.$tick.'<strong>'
                .get_string('gradingstatus', 'assign').':</strong>';
        if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == ASSIGN_GRADING_STATUS_NOT_GRADED) {
            $gradingstatuslabel = get_string($status->gradingstatus, 'assign');
        } else {
            $gradingstatus = 'markingworkflowstate'.$status->gradingstatus;
            $gradingstatuslabel = get_string($gradingstatus, 'assign');
        }
        $gradingdata .= '<span class="gradingstatus">'.$gradingstatuslabel.'</span></div>';

        // Show graders whether this submission is editable by students.
        if ($status->view == assign_submission_status::GRADER_VIEW) {
            $gradingdata .= '<div>'.get_string('editingstatus', 'assign').'<br>';
            if ($status->canedit) {
                $gradingdata .= get_string('submissioneditable', 'assign');
            } else {
                $gradingdata .= get_string('submissionnoteditable', 'assign');
            }
            $gradingdata .= '</div>';
        }

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $submissiondata = '';
        if ($submission) {
            $submissiondata .= '<div class="assign-submission-data">';
            $submissiondata .= '<div>'.$userpic.'</div><hr>';
            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                        $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                        $plugin->is_visible() &&
                        $plugin->has_user_summary() &&
                        $pluginshowsummary
                    ) {
                            $submissiondata .= '<h4 class="h6 plugin-submission-title">'.$plugin->get_name().'</h4>';
                            $displaymode = assign_submission_plugin_submission::SUMMARY;
                            $pluginsubmission = new assign_submission_plugin_submission($plugin,
                            $submission,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                            $submissiondata .= $this->render($pluginsubmission);
                    }
                }
            }

            $submissiondata .= '<div class="statusdata">'.$statusdata.'</div>';
            $submissiondata .= '<div class="gradingdata">'.$gradingdata.'</div>';

            if ($submission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $calico = '<i class="icon fa fa-calendar fa-fw " aria-hidden="true" role="presentation"></i>';
                $submissiondata .= '<div class="lastmodified">'.$calico.'<strong>' .get_string('timemodified', 'assign').
                    ':</strong>';
                $submissiondata .= '<span>'.userdate($submission->timemodified).'</span></div>';
            }
            $submissiondata .= '<br>';

            // Links to sumbit assignment.
            if ($status->view == assign_submission_status::STUDENT_VIEW) {
                if ($status->canedit) {
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $url = new moodle_url('/mod/assign/view.php', $urlparams);
                    if (!$submission || $submission->status == ASSIGN_SUBMISSION_STATUS_NEW) {
                        $submissiondata .= '<a href="'.$url.'" role="button" class="btn btn-primary">'
                            .get_string('addsubmission', 'assign').'</a>';
                    } else if ($submission->status == ASSIGN_SUBMISSION_STATUS_REOPENED) {
                        $submissiondata .= '<div>'.get_string('addnewattempt_help', 'assign').'</div>';
                        $submissiondata .= '<a href="'.$url.'" role="button" class="btn btn-primary">'
                            .get_string('addnewattempt', 'assign').'</a>';

                        $submissiondata .= '<div>'.get_string('addnewattemptfromprevious_help', 'assign').'</div>';
                        $urlparams = array('id' => $status->coursemoduleid,
                                           'action' => 'editprevioussubmission',
                                           'sesskey' => sesskey());
                        $url = new moodle_url('/mod/assign/view.php', $urlparams);
                        $submissiondata .= '<a href="'.$url.'" role="button" class="btn btn-primary">'
                            .get_string('addnewattemptfromprevious', 'assign').'</a>';
                    } else {
                        $submissiondata .= '<a href="'.$url.'" role="button" class="btn btn-primary">'
                            .get_string('editsubmission', 'assign').'</a>';
                    }
                }

                if ($status->cansubmit) {
                    $submissiondata .= '<div>'.get_string('submitassignment_help', 'assign').'</div>';
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'submit');
                    $url = new moodle_url('/mod/assign/view.php', $urlparams);
                    $submissiondata .= '<a href="'.$url.'" role="button" class="btn btn-primary">'
                        .get_string('submitassignment', 'assign').'</a>';
                }
            }
            $submissiondata .= '</div><!-- close assign-submission-data -->';
        }
        $o .= $submissiondata;

        // If assignment not graded, output marking criteria,
        // else feedback is displayed which contains marking criteria.
        if (!$status->graded && $status->gradingcontrollerpreview) {
            $gradingmethodpreview = '<div class="gradingmethodpreview">';
            $gradingmethodpreview .= '<h5>'.get_string('gradingmethodpreview', 'assign').'</h5>';
            $gradingmethodpreview .= $status->gradingcontrollerpreview;
            $gradingmethodpreview .= '</div>';
            $o .= $gradingmethodpreview;
        }
        return $o;
    }

    /**
     * Render all the current grades and feedback.
     *
     * @param assign_feedback_status $status
     * @return string
     */
    public function render_assign_feedback_status(\assign_feedback_status $status) {
        $o = '<div class="assign-feedback">';
        $o .= '<h3>'.get_string('feedback', 'assign').'</h3><br>';
        if ($status->grader) {
            // Grader.
            $userdescription = $this->output->user_picture($status->grader).
                               ' '.
                               fullname($status->grader, $status->canviewfullnames);
            $grader = $userdescription;
            if (isset($status->gradefordisplay)) {
                $grader = '<div class="row"><div class="col-sm-6">'.$grader.'</div>';
                $grader .= '<div class="col-sm-6"><em>'.userdate($status->gradeddate).'</em></div>';
                $grader .= '</div>';
            }
            $o .= $grader;
        }
        // Grade.
        if (isset($status->gradefordisplay)) {
            $o .= '<div class="p-y-1">'.$status->gradefordisplay.'</div>';
        }

        foreach ($status->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() &&
                    $plugin->is_visible() &&
                    $plugin->has_user_summary() &&
                    !empty($status->grade) &&
                    !$plugin->is_empty($status->grade)) {

                $o .= '<div>';
                $o .= '<h5>'.$plugin->get_name().'</h5>';
                $displaymode = assign_feedback_plugin_feedback::SUMMARY;
                $pluginfeedback = new assign_feedback_plugin_feedback($plugin,
                                                                      $status->grade,
                                                                      $displaymode,
                                                                      $status->coursemoduleid,
                                                                      $status->returnaction,
                                                                      $status->returnparams);
                $o .= $this->render($pluginfeedback);
                $o .= '</div>';
            }
        }
        $o .= '</div>';
        $o .= '</div>';
        return $o;
    }
}
