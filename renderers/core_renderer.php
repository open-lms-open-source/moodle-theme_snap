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
 * Snap core renderer.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once('toc_renderer.php');
require_once($CFG->libdir.'/coursecatlib.php');

use theme_snap\local;
use theme_snap\renderables\settings_link;

class theme_snap_core_renderer extends toc_renderer {

    public function course_footer() {
        global $DB, $COURSE, $CFG, $PAGE;

        // Note: This check will be removed for Snap 2.7.
        if (empty($PAGE->theme->settings->coursefootertoggle)) {
            return false;
        }

        $context = context_course::instance($COURSE->id);
        $courseteachers = '';
        $coursesummary = '';

        $clist = new course_in_list($COURSE);
        $teachers = $clist->get_course_contacts();

        if (!empty($teachers)) {
            // Get all teacher user records in one go.
            $teacherids = array();
            foreach ($teachers as $teacher) {
                $teacherids[] = $teacher['user']->id;
            }
            $teacherusers = $DB->get_records_list('user', 'id', $teacherids);

            // Create string for teachers.
            $courseteachers .= '<h6>'.get_string('coursecontacts', 'theme_snap').'</h6><div id=course_teachers>';
            foreach ($teachers as $teacher) {
                if (!isset($teacherusers[$teacher['user']->id])) {
                    continue;
                }
                $teacheruser = $teacherusers [$teacher['user']->id];
                $courseteachers .= $this->print_teacher_profile($teacheruser);
            }
            $courseteachers .= "</div>";
        }
        // If user can edit add link to manage users.
        if (has_capability('moodle/course:enrolreview', $context)) {
            if (empty($courseteachers)) {
                $courseteachers = "<h6>".get_string('coursecontacts', 'theme_snap')."</h6>";
            }
            $courseteachers .= '<a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/enrol/users.php?id='.
                $COURSE->id.'">'.get_string('enrolledusers', 'enrol').'</a>';
        }

        if (!empty($COURSE->summary)) {
            $coursesummary = '<h6>'.get_string('aboutcourse', 'theme_snap').'</h6>';
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $formatoptions->overflowdiv = true;
            $formatoptions->context = $context;
            $coursesummarycontent = file_rewrite_pluginfile_urls($COURSE->summary,
                'pluginfile.php', $context->id, 'course', 'summary', null);
            $coursesummarycontent = format_text($coursesummarycontent, $COURSE->summaryformat, $formatoptions);
            $coursesummary .= '<div id=course_about>'.$coursesummarycontent.'</div>';
        }

        // If able to edit add link to edit summary.
        if (has_capability('moodle/course:update', $context)) {
            if (empty($coursesummary)) {
                $coursesummary = '<h6>'.get_string('aboutcourse', 'theme_snap').'</h6>';
            }
            $coursesummary .= '<a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/course/edit.php?id='.
                $COURSE->id.'#id_descriptionhdr">'.get_string('editsummary').'</a>';
        }

        // Get recent activities on mods in the course.
        $courserecentactivities = $this->get_mod_recent_activity($context);
        if ($courserecentactivities) {
            $courserecentactivity = '<h6>'.get_string('recentactivity').'</h6>';
            $courserecentactivity .= "<div id=course_recent_updates>";
            if (!empty($courserecentactivities)) {
                $courserecentactivity .= $courserecentactivities;
            }
            $courserecentactivity .= "</div>";
        }
        // If user can edit add link to moodle recent activity stuff.
        if (has_capability('moodle/course:update', $context)) {
            if (empty($courserecentactivities)) {
                $courserecentactivity = '<h6>'.get_string('recentactivity').'</h6>';
                $courserecentactivity .= get_string('norecentactivity');
            }
            $courserecentactivity .= '<div><a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/course/recent.php?id='
                .$COURSE->id.'">'.get_string('showmore', 'form').'</a></div>';
        }

        if (!empty($courserecentactivity)) {
            $columns[] = $courserecentactivity;
        }
        if (!empty($courseteachers)) {
            $columns[] = $courseteachers;
        }
        if (!empty($coursesummary)) {
            $columns[] = $coursesummary;
        }

        // Logic for printing bootstrap grid.
        if (empty($columns)) {
            return '';
        } else if (count($columns) == 1) {
                $output  = '<div class="col-md-12">'.$columns[0].'</div>';
        } else if (count($columns) >= 2 && !empty($courserecentactivity)) {
            // Here we output recent updates any some other sections.
            if (count($columns) > 2) {
                $output  = '<div class="col-md-6">'.$columns[1].$columns[2].'</div>';
            } else {
                $output  = '<div class="col-md-6">'.$columns[1].'</div>';
            }
            $output .= '<div class="col-md-6">'.$columns[0].'</div>';
        } else if (count($columns) == 2) {
            $output  = '<div class="col-md-6">'.$columns[1].'</div>';
            $output  .= '<div class="col-md-6">'.$columns[0].'</div>';
        }

        return $output;
    }

    /**
     * Print teacher profile
     * Prints a media object with the techers photo, name (links to profile) and desctiption.
     *
     * @param stdClass $user
     * @return string
     */
    public function print_teacher_profile($user) {
        global $CFG, $COURSE;

        $userpicture = new user_picture($user);
        $userpicture->link = false;
        $userpicture->alttext = false;
        $userpicture->size = 100;
        $picture = $this->render($userpicture);

        $fullname = '<a href="'.$CFG->wwwroot.'/user/profile.php?id='.$user->id.'">'.format_string(fullname($user)).'</a>';
        $coursecontext = context_course::instance($COURSE->id);
        $user->description = file_rewrite_pluginfile_urls($user->description,
            'pluginfile.php', $coursecontext->id, 'user', 'profile', $user->id);
        $description = format_text($user->description, $user->descriptionformat);

        return "<div class='snap-media-object'>
                $picture
                <div class='snap-media-body'>
                $fullname
                $description
                </div>
                </div>";
    }


    public function get_mod_recent_activity($context) {
        global $COURSE, $OUTPUT;
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
        $recentactivity = array();
        $timestart = time() - (86400 * 7); // 7 days ago.
        if (optional_param('testing', false, PARAM_BOOL)) {
            $timestart = time() - (86400 * 700); // 700 days ago for testing purposes.
        }
        $modinfo = get_fast_modinfo($COURSE);
        $usedmodules = $modinfo->get_used_module_names();
        if (empty($usedmodules)) {
            // No used modules so return null string.
            return '';
        }
        foreach ($usedmodules as $modname => $modfullname) {
            // Each module gets it's own logs and prints them.
            ob_start();
            $hascontent = component_callback('mod_'. $modname, 'print_recent_activity',
                    array($COURSE, $viewfullnames, $timestart), false);
            if ($hascontent) {
                $content = ob_get_contents();
                if (!empty($content)) {
                    $recentactivity[$modname] = $content;
                }
            }
            ob_end_clean();
        }

        $output = '';
        if (!empty($recentactivity)) {
            foreach ($recentactivity as $modname => $moduleactivity) {
                // Get mod icon - no point in alt as title already there.
                $img = html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('icon', $modname)));
                // Create media object for module activity.
                $output .= "<div class='snap-media-object course-footer-update-$modname'>$img".
                    "<div class=snap-media-body>$moduleactivity</div></div>";
            }
        }

        return $output;
    }

    /**
     * Settings link for opening the Administration menu, only shown if needed.
     * @param settings_link $settingslink
     *
     * @return string
     */
    public function render_settings_link(settings_link $settingslink) {

        if (!$settingslink->output) {
            return '';
        }

        $burgericon = '<span class="lines"></span>';
        $url = '#inst' . $settingslink->instanceid;
        $attributes = array(
            'id' => 'admin-menu-trigger',
            'class' => 'pull-right',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'title' => get_string('admin', 'theme_snap')
        );

        return html_writer::link($url, $burgericon, $attributes);
    }

    /**
     * Link to browse all courses, shown to admins in the fixy menu.
     *
     * @return string
     */
    public function browse_all_courses_button() {
        global $CFG;

        $output = '';
        if (!empty($CFG->navshowallcourses) || has_capability('moodle/site:config', context_system::instance())) {
            $browse = get_string('browseallcourses', 'theme_snap');
            $url = new moodle_url('/course/index.php');
            $output = html_writer::link($url, $browse, ['class' => 'btn btn-default moodle-browseallcourses']);
        }
        return $output;
    }



    /**
     * Get badge renderer.
     * @return null|theme_snap_message_badge_renderer
     */
    protected function get_badge_renderer() {
        global $PAGE;

        $mprocs = get_message_processors(true);
        if (!isset($mprocs['badge'])) {
            // Badge message processor is not enabled - exit.
            return null;
        }

        try {
            $badgerend = $PAGE->get_renderer('message_badge');
        } catch (Exception $e) {
            $badgerend = null;
        }

        // Note: In certain circumstances the snap message badge render is not loaded and the original message badge
        // render is loaded instead - e.g. when you initially switch to the snap theme.
        // This results in the fixy menu button looking broken as it shows the badge in its original format as opposed
        // to the overriden format provided by the snap message badge renderer.
        if (!$badgerend instanceof theme_snap_message_badge_renderer) {
            $badgerend = null;
        }

        return $badgerend;
    }

    /**
     * Badge counter for new messages.
     * @return string
     */
    protected function render_badge_count() {
        global $USER;

        $badgerend = $this->get_badge_renderer();
        if (empty($badgerend)) {
            return '';
        }
        return $badgerend->badge($USER->id);
    }

    /**
     * Render badges.
     * @return string
     */
    protected function render_badges() {
        $mprocs = get_message_processors(true);
        if (!isset($mprocs['badge'])) {
            // Badge message processor is not enabled - exit.
            return null;
        }
        $badgerend = $this->get_badge_renderer();
        $badges = '';
        if ($badgerend && $badgerend instanceof theme_snap_message_badge_renderer) {
            $badges = '<div class="alert_stream">
                '.$badgerend->messagestitle().'
                    <div class="message_badge_container"></div>
                </div>';
        }
        return $badges;
    }

    /**
     * Render messages from users
     * @return string
     */
    protected function render_messages() {
        global $CFG;

        if ($this->page->theme->settings->messagestoggle == 0) {
            return '';
        }

        $messagesheading = get_string('messages', 'theme_snap');
        $o = '<h2>'.$messagesheading.'</h2>
                <div id="snap-personal-menu-messages"></div>';

        $messagesbutton = get_string('messaging', 'theme_snap');
        $messageurl = "$CFG->wwwroot/message";
        $o .= '<div class="text-center">';
        $o .= '<a class="btn btn-default" href="'.$messageurl.'">'.$messagesbutton.'</a>';
        $o .= '</div>';
        return $o;
    }

    /**
     * Render forumposts.
     *
     * @return string
     */
    protected function render_forumposts() {
        global $USER;
        if (empty($this->page->theme->settings->forumpoststoggle)) {
            return '';
        }

        $messagesheading = get_string('forumposts', 'theme_snap');

        $o = '<h2>'.$messagesheading.'</h2>
        <div id="snap-personal-menu-forumposts"></div>';
        $messageurl = new moodle_url('/mod/forum/user.php', ['id' => $USER->id]);
        $o .= '<div class="text-center">';
        $o .= '<a class="btn btn-default" href="'.$messageurl.'">'.$messagesheading.'</a>';
        $o .= '</div>';
        return $o;
    }

    /**
     * @param moodle_url|string $url
     * @param string $image
     * @param string $title
     * @param array|string $meta
     * @param string $content
     * @param string $extraclasses
     * @return string
     */
    public function snap_media_object($url, $image, $title, $meta, $content, $extraclasses = '') {
        $formatoptions = new stdClass;
        $formatoptions->filter = false;
        $title = format_text($title, FORMAT_HTML, $formatoptions);
        $content = format_text($content, FORMAT_HTML, $formatoptions);

        if (is_array($meta)) {
            $metastr = '';
            foreach ($meta as $metaitem) {
                $metastr .= '<span class="snap-media-meta">'.
                    format_text($metaitem, FORMAT_HTML, $formatoptions).'</span>';
            }
        } else {
            $metastr = '<span class="snap-media-meta">'.
                format_text($meta, FORMAT_HTML, $formatoptions).'</span>';
        }

        $linkcontent = $image
                . '<div class="snap-media-body">'
                . "<h3>$title</h3>"
                . '<span class="snap-media-meta">'.$metastr.'</span>'
                . $content
                . '</div>';

        $link = html_writer::link($url, $linkcontent);

        return '<div class="snap-media-object '.$extraclasses.'">'.$link.'</div>';
    }

    /**
     * Return friendly text date (e.g. "Today", "Tomorrow") in a <time> tag
     * @return string
     */
    public function friendly_datetime($time) {
        $timetext = \calendar_day_representation($time);
        $timetext .= ', ' . \calendar_time_representation($time);
        $datetime = date(DateTime::W3C, $time);
        return html_writer::tag('time', $timetext, array(
            'datetime' => $datetime)
        );
    }

    protected function render_callstoaction() {

        $deadlines = $this->render_deadlines();
        if (!empty($deadlines)) {
            $columns[] = $deadlines;
        }

        $graded = $this->render_graded();
        $grading = $this->render_grading();
        if (!empty($grading)) {
            $columns[] = $grading;
        } else if (!empty($graded)) {
            $columns[] = $graded;
        }

        $badges = $this->render_badges();
        if (!empty($badges)) {
            $columns[] = $badges;
        } else {
            $messages = $this->render_messages();
            if (!empty($messages)) {
                $columns[] = $messages;
            }
        }

        $forumposts = $this->render_forumposts();
        if (!empty($forumposts)) {
            $columns[] = $forumposts;
        }

        $o = '<div class="row callstoaction">';
        if (empty($columns)) {
             return '';
        } else if (count($columns) == 1) {
            $o .= '<section class="col-md-12">'.$columns[0].'</section>';
        } else if (count($columns) == 2) {
            $o .= '
              <section class="col-md-6">'.$columns[0].'</section>
              <section class="col-md-6">'.$columns[1].'</section>
            ';
        } else if (count($columns) == 3) {
            $o .= '
              <section class="col-md-4">'.$columns[0].'</section>
              <section class="col-md-4">'.$columns[1].'</section>
              <section class="col-md-4">'.$columns[2].'</section>
            ';
        } else if (count($columns) == 4) {
            $o .= '
              <section class="col-md-3">'.$columns[0].'</section>
              <section class="col-md-3">'.$columns[1].'</section>
              <section class="col-md-3">'.$columns[2].'</section>
              <section class="col-md-3">'.$columns[3].'</section>
            ';
        }

        $o .= '</div>';
        return ($o);
    }

    /**
     * Is feedback toggle enabled?
     * Note: If setting has never been set then default to enabled (return true).
     *
     * @return bool
     */
    protected function feedback_toggle_enabled() {
        if (property_exists($this->page->theme->settings, 'feedbacktoggle')
            && $this->page->theme->settings->feedbacktoggle == 0) {
            return false;
        }
        return true;
    }

    /**
     * Render all grading CTAs for markers
     * @return string
     */
    protected function render_grading() {
        global $USER;

        if (!$this->feedback_toggle_enabled()) {
            return '';
        }

        $courseids = local::gradeable_courseids($USER->id);

        if (empty($courseids)) {
            return '';
        }

        $gradingheading = get_string('grading', 'theme_snap');
        $o = "<h2>$gradingheading</h2>";

        $o .= '<div id="snap-personal-menu-grading"></div>';

        return $o;
    }

    /**
     * Render all graded CTAs for students
     * @return string
     */
    protected function render_graded() {

        if (!$this->feedback_toggle_enabled()) {
            return '';
        }

        $recentfeedback = get_string('recentfeedback', 'theme_snap');
        $o = "<h2>$recentfeedback</h2>";

        $mygradesurl = new moodle_url('/grade/report/mygrades.php');
        $mygradesbutton = html_writer::link($mygradesurl,
                                            get_string('mygrades', 'theme_snap'),
                                            ['class' => 'btn btn-default']);

        $o .= '<div id="snap-personal-menu-graded"></div>
               <div class="text-center">
               '.$mygradesbutton.'
               </div>';

        return $o;
    }

    /**
     * Render all course deadlines.
     * @return string
     */
    protected function render_deadlines() {
        global $CFG;

        if ($this->page->theme->settings->deadlinestoggle == 0) {
            return '';
        }

        $deadlinesheading = get_string('deadlines', 'theme_snap');
        $o = "<h2>$deadlinesheading</h2>";

        $o .= '<div id="snap-personal-menu-deadlines"></div>';

        $calurl = $CFG->wwwroot.'/calendar/view.php?view=month';
        $calendar = get_string('calendar', 'calendar');
        $o .= '<div class="text-center">';
        $o .= '<a class="btn btn-default" href="'.$calurl.'">'.$calendar.'</a>';
        $o .= '</div>';

        return $o;
    }


    /**
     * Print login button
     *
     */
    public function login_button() {
        global $CFG;

        $output = '';
        $loginurl = '#login';
        if (!empty($CFG->alternateloginurl)) {
            $loginurl = $CFG->wwwroot.'/login/index.php';
        }
        // This check is here for the front page login.
        if (!isloggedin() || isguestuser()) {
            $loginatts = [
                'aria-haspopup' => 'true',
                'class' => 'snap-login-button fixy-trigger'
            ];
            $output = html_writer::link($loginurl, get_string('login'), $loginatts);
        }
        return $output;
    }
    /**
     * The "fixy" overlay that drops down when the link in the top right corner is clicked. It will say either
     * "login" or "menu" (for signed in users).
     *
     */
    public function fixed_menu() {
        global $CFG, $USER, $PAGE, $DB;

        $logout = get_string('logout');
        $isguest = isguestuser();

        $output = '';
        if (!isloggedin() || $isguest) {
            $login = get_string('login');
            $cancel = get_string('cancel');
            $username = get_string('username');
            $password = get_string('password');
            $loginform = get_string('loginform', 'theme_snap');
            $helpstr = '';

            if (empty($CFG->forcelogin)
                || $isguest
                || !isloggedin()
                || !empty($CFG->registerauth)
                || is_enabled_auth('none')
                || !empty($CFG->auth_instructions)
            ) {
                if ($isguest) {
                    $helpstr = '<p class="text-center">'.get_string('loggedinasguest', 'theme_snap').'</p>';
                    $helpstr .= '<p class="text-center">'.
                        '<a class="btn btn-primary" href="'.
                        s($CFG->wwwroot).'/login/logout.php?sesskey='.sesskey().'">'.$logout.'</a></p>';
                    $helpstr .= '<p class="text-center">'.
                        '<a href="'.s($CFG->wwwroot).'/login/index.php">'.
                        get_string('helpwithloginandguest', 'theme_snap').'</a></p>';
                } else {
                    if (empty($CFG->forcelogin)) {
                        $help = get_string('helpwithloginandguest', 'theme_snap');
                    } else {
                        $help = get_string('helpwithlogin', 'theme_snap');
                    }
                    $helpstr = "<p class='text-center'><a href='".s($CFG->wwwroot)."/login/index.php'>$help</a></p>";
                }
            }
            $output .= $this->login_button();
            $output .= "<div class='fixy' id='login' role='dialog' aria-label='$loginform' tabindex='0'>
            <form action='$CFG->wwwroot/login/'  method='post'>
        <a id='fixy-close' class='pull-right snap-action-icon' href='#'>
            <i class='icon icon-close'></i><small>$cancel</small>
        </a>
            <div class=fixy-inner>
            <legend>$loginform</legend>
            <label for='username'>$username</label>
            <input autocapitalize='off' type='text' name='username' id='username' placeholder='".s($username)."'>
            <label for='password'>$password</label>
            <input type='password' name='password' id='password' placeholder='".s($password)."'>
            <br>
            <input type='submit' id='loginbtn' value='".s($login)."'>
            $helpstr
            </div>
        </form></div>";
        } else {
            $courselist = "";
            $userpicture = new user_picture($USER);
            $userpicture->link = false;
            $userpicture->alttext = false;
            $userpicture->size = 100;
            $picture = $this->render($userpicture);
            $mycourses = enrol_get_my_courses(null, 'visible DESC, fullname ASC, id DESC');

            $courselist .= "<section id='fixy-my-courses'><div class='clearfix'><h2>".get_string('courses')."</h2>";

            // Default text when no courses.
            if (!$mycourses) {
                $courselist .= "<p>".get_string('coursefixydefaulttext', 'theme_snap')."</p>";
            }
            foreach ($mycourses as $c) {
                $pubstatus = "";
                if (!$c->visible) {
                    $notpublished = get_string('notpublished', 'theme_snap');
                    $pubstatus = "<small class='published-status'>".$notpublished."</small>";
                }

                $bgcolor = local::get_course_color($c->id);
                $courseimagecss = "background-color: #$bgcolor;";
                $bgimage = local::course_coverimage_url($c->id);
                if (!empty($bgimage)) {
                    $courseimagecss .= "background-image: url($bgimage);";
                }
                $dynamicinfo = '<div data-courseid="'.$c->id.'" class=dynamicinfo></div>';

                $clist = new course_in_list($c);
                $teachers = $clist->get_course_contacts();

                $courseteachers = '';
                $vcourseteachers = '';
                $ecourseteachers = '';

                if (!empty($teachers)) {
                    $avatars = [];
                    $blankavatars = [];
                    $courseteachers = '<h4 class="sr-only">'.get_string('coursecontacts', 'theme_snap').'</h4>';
                    // Get all teacher user records in one go.
                    $teacherids = array();
                    foreach ($teachers as $teacher) {
                        $teacherids[] = $teacher['user']->id;
                    }
                    $teacherusers = $DB->get_records_list('user', 'id', $teacherids);
                    foreach ($teachers as $teacher) {
                        if (!isset($teacherusers[$teacher['user']->id])) {
                            continue;
                        }
                        $teacheruser = $teacherusers [$teacher['user']->id];
                        $userpicture = new user_picture($teacheruser);
                        $userpicture->link = false;
                        $userpicture->size = 100;
                        $teacherpicture = $this->render($userpicture);

                        if (stripos($teacherpicture, 'defaultuserpic') === false) {
                            $avatars[] = $teacherpicture;
                        } else {
                            $blankavatars[] = $teacherpicture;
                        }
                    }
                    // Let's put the interesting avatars first!
                    $avatars = array_merge($avatars, $blankavatars);
                    // Limit visible to 4.
                    if (count($avatars) > 4) {
                        // Show 4 avatars and link to show more.
                        $hiddenavatars = array_slice($avatars, 4);
                        $avatars = array_slice($avatars, 0, 4);
                        $extralink = '<a class="courseinfo-teachers-more state-visible" href="#">+'.count($hiddenavatars).'</a>';
                    } else {
                        $hiddenavatars = [];
                        $extralink = '';
                    }
                    $vcourseteachers = '<div class="courseinfo-teachers-visible">'.implode('', $avatars).$extralink.'</div>';
                    $ecourseteachers = '<div class="courseinfo-teachers-extra">'.implode('', $hiddenavatars).'</div>';
                }

                $clink = '<div data-href="'.$CFG->wwwroot.'/course/view.php?id='.$c->id.
                    '" class="courseinfo" style="'.$courseimagecss.'">

                    <div class="courseinfo-body"><h3><a href="'.$CFG->wwwroot.'/course/view.php?id='.$c->id.'">'.
                    format_string($c->fullname).'</a></h3>'.$dynamicinfo.$pubstatus.'</div>
                    <div class="courseinfo-teachers">'.$courseteachers.$vcourseteachers.$ecourseteachers.'</div>
                    </div>';
                $courselist .= $clink;
            }
            $courselist .= "</div>";

            $courselist .= '<div class="row fixy-browse-search-courses"><br>';

            if (has_capability('moodle/site:config', context_system::instance())) {
                $courserenderer = $PAGE->get_renderer('core', 'course');
                $coursesearch = $courserenderer->course_search_form(null, 'fixy');
                $courselist .= '<div class="col-md-6">'.$coursesearch.'</div>';
            }

            $browseallcoursesbutton = $this->browse_all_courses_button();
            $courselist .= '<div class="col-md-6">'.$browseallcoursesbutton.'</div>';
            $courselist .= '</div></section>'; // Close row.

            $menu = get_string('menu', 'theme_snap');
            $badge = $this->render_badge_count();
            $linkcontent = $menu.$picture.$badge;
            $attributes = array(
                'aria-haspopup' => 'true',
                'class' => 'fixy-trigger',
                'id' => 'js-personal-menu-trigger',
                'aria-controls' => 'primary-nav',
                'title' => get_string('sitenavigation', 'theme_snap'),
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
            );

            $output .= html_writer::link("#primary-nav", $linkcontent, $attributes);

            $close = get_string('close', 'theme_snap');
            $viewyourprofile = get_string('viewyourprofile', 'theme_snap');
            $realuserinfo = '';
            if (\core\session\manager::is_loggedinas()) {
                $realuser = \core\session\manager::get_realuser();
                $via = get_string('via', 'theme_snap');
                $fullname = fullname($realuser, true);
                $realuserinfo = html_writer::span($via.' '.html_writer::span($fullname, 'real-user-name'), 'real-user-info');
            }

            $output .= '<nav id="primary-nav" class="fixy toggle-details" tabindex="0">
        <a id="fixy-close" class="pull-right snap-action-icon" href="#">
            <i class="icon icon-close"></i><small>'.$close.'</small>
        </a>
        <div class=fixy-inner>
        <h1 id="fixy-profile-link">
            <a title="'.s($viewyourprofile).'" href="'.s($CFG->wwwroot).'/user/profile.php" >'.
                $picture.'<span id="fixy-username">'.format_string(fullname($USER)).'</span>
            </a>
        </h1>'.$realuserinfo.$courselist.$this->render_callstoaction().'
        <div class="fixy-logout-footer clearfix text-center">
            <a class="btn btn-default logout" href="'.s($CFG->wwwroot).'/login/logout.php?sesskey='.sesskey().'">'.$logout.'</a>
        </div>
        </div><!-- end fixy-inner -->
        </nav><!-- end primary nav -->';
        }
        return $output;
    }

    /**
     * get section number by section id
     * @param int $sectionid
     * @return int|boolean (false if not found)
     */
    protected function get_section_for_id($sectionid) {
        global $COURSE;
        $modinfo = get_fast_modinfo($COURSE);
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($thissection->id == $sectionid) {
                return $section;
            }
        }
        return false;
    }

    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        global $COURSE, $CFG;

        require_once($CFG->dirroot.'/course/lib.php');

        $breadcrumbs = '';
        $courseitem = null;
        foreach ($this->page->navbar->get_items() as $item) {
            $item->hideicon = true;

            if ($item->type == navigation_node::TYPE_COURSE) {
                $courseitem = $item;
            }
            if ($item->type == navigation_node::TYPE_SECTION) {
                if ($courseitem != null) {

                    $url = $courseitem->action->out(false);

                    $item->action = $courseitem->action;
                    $sectionnumber = $this->get_section_for_id($item->key);

                    // Append section focus hash only for topics and weeks formats because we can
                    // trust the behaviour of these formats.
                    if ($COURSE->format == 'topics' || $COURSE->format == 'weeks') {
                        $url .= '#section-'.$sectionnumber;
                        if ($item->text == get_string('general')) {
                            $item->text = get_string('introduction', 'theme_snap');
                        }
                    } else {
                        $url = course_get_url($COURSE, $sectionnumber);
                    }
                    $item->action = new moodle_url($url);
                }
            }

            $breadcrumbs .= '<li>'.$this->render($item).'</li>';
        }
        return "<ol class=breadcrumb>$breadcrumbs</ol>";
    }



    public function page_heading($tag = 'h1') {
        $heading = parent::page_heading($tag);
        if ($this->page->pagelayout == 'frontpage') {
            $heading .= '<p>' . format_string($this->page->theme->settings->subtitle) . '</p>';
        }
        if ($this->page->user_is_editing() && $this->page->pagelayout == 'frontpage') {
            $url = new moodle_url('/admin/settings.php', ['section' => 'themesettingsnap'], 'admin-fullname');
            $link = html_writer::link($url,
                            get_string('changefullname', 'theme_snap'),
                            ['class' => 'btn btn-default btn-sm']);
            $heading .= $link;
        }
        return $heading;
    }

    public function favicon() {
        // Allow customized favicon from settings.
        $url = $this->page->theme->setting_file_url('favicon', 'favicon');
        return empty($url) ? parent::favicon() : $url;
    }

    /**
     * Renders custom menu as a simple list.
     * Any nesting gets flattened.
     *
     * @return string
     */
    protected function render_custom_menu(custom_menu $menu) {
        if (!$menu->has_children()) {
            return '';
        }
        $content = '';
        foreach ($menu->get_children() as $item) {
            $content .= $this->render_custom_menu_item($item);
        }
        $class = 'list-unstyled';
        $count = substr_count($content, '<li>');
        if ($count > 11) {
            $class .= ' list-large';
        }
        $content = html_writer::tag('ul', $content, array('class' => $class));

        return $content;
    }

    /**
     * Output custom menu items as flat list.
     *
     * @return string
     */
    protected function render_custom_menu_item(custom_menu_item $menunode) {
        $content = html_writer::start_tag('li');
        if ($menunode->get_url() !== null) {
            $url = $menunode->get_url();
            $content .= html_writer::link($url, $menunode->get_text(), array('title' => $menunode->get_title()));
        } else {
            $content .= $menunode->get_text();
        }

        $content .= html_writer::end_tag('li');

        if ($menunode->has_children()) {
            foreach ($menunode->get_children() as $menunode) {
                $content .= $this->render_custom_menu_item($menunode);
            }
        }
        return $content;
    }

    /**
     * Alternative rendering of front page news, called from layout/faux_site_index.php which
     * replaces the standard news output with this.
     *
     * @return string
     */
    public function site_frontpage_news() {
        global $CFG, $SITE;

        require_once($CFG->dirroot.'/mod/forum/lib.php');

        if (!$forum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }
        $cm      = get_coursemodule_from_instance('forum', $forum->id, $SITE->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id, MUST_EXIST);

        $output  = html_writer::start_tag('div', array('id' => 'site-news-forum', 'class' => 'clearfix'));
        $output .= $this->heading(format_string($forum->name, true, array('context' => $context)));

        $groupmode    = groups_get_activity_groupmode($cm, $SITE);
        $currentgroup = groups_get_activity_group($cm);

        if (!$discussions = forum_get_discussions($cm,
            'p.modified DESC', true, null, $SITE->newsitems, false, -1, $SITE->newsitems)) {
            $output .= html_writer::tag('div', '('.get_string('nonews', 'forum').')', array('class' => 'forumnodiscuss'));

            if (forum_user_can_post_discussion($forum, $currentgroup, $groupmode, $cm, $context)) {
                $output .= html_writer::link(
                    new moodle_url('/mod/forum/post.php', array('forum' => $forum->id)),
                    get_string('addanewtopic', 'forum'),
                    array('class' => 'btn btn-primary')
                );
            } else {
                // No news and user cannot edit, so return nothing.
                return '';
            }

            return $output.'</div>';
        }

        $output .= html_writer::start_div('', array('id' => 'news-articles'));
        foreach ($discussions as $discussion) {
            if (!forum_user_can_see_discussion($forum, $discussion, $context)) {
                continue;
            }
            $message    = file_rewrite_pluginfile_urls($discussion->message,
                          'pluginfile.php', $context->id, 'mod_forum', 'post', $discussion->id);

            $imagestyle = '';

            $imgarr = \theme_snap\local::extract_first_image($message);
            if ($imgarr) {
                $imageurl   = s($imgarr['src']);
                $imagestyle = " style=\"background-image:url('$imageurl')\"";
            }

            $name    = format_string($discussion->name, true, array('context' => $context));
            $date    = userdate($discussion->timemodified, get_string('strftimedatetime', 'langconfig'));

            $readmorebtn = "<a class='btn btn-default toggle' href='".
                $CFG->wwwroot."/mod/forum/discuss.php?d=".$discussion->discussion."'>".
                get_string('readmore', 'theme_snap')."</a>";

            $preview = '';
            $newsimage = '';
            if (!$imagestyle) {
                $preview = html_to_text($message, 0, false);
                $preview = "<div class='news-article-preview'><p>".shorten_text($preview, 200)."</p>
                <p class='text-right'>".$readmorebtn."</p></div>";
            } else {
                $newsimage = '<div class="news-article-image toggle"'.$imagestyle.' title="'.
                    get_string('readmore', 'theme_snap').'"></div>';
            }
            $close = get_string('close', 'theme_snap');
            $output .= <<<HTML
<div class="news-article clearfix">
    {$newsimage}
    <div class="news-article-inner">
        <div class="news-article-content">
            <h3 class='toggle'><a href="$CFG->wwwroot/mod/forum/discuss.php?d=$discussion->discussion">{$name}</a></h3>
            <em class="news-article-date">{$date}</em>
        </div>
    </div>
    {$preview}
    <div class="news-article-message" tabindex="-1">
        {$message}
        <div><hr><a class="snap-action-icon toggle" href="#">
        <i class="icon icon-close"></i><small>{$close}</small></a></div>
    </div>
</div>
HTML;
        }
        $actionlinks = html_writer::link(
            new moodle_url('/mod/forum/view.php', array('id' => $cm->id)),
            get_string('morenews', 'theme_snap'),
            array('class' => 'btn btn-default')
        );
        if (forum_user_can_post_discussion($forum, $currentgroup, $groupmode, $cm, $context)) {
            $actionlinks .= html_writer::link(
                new moodle_url('/mod/forum/post.php', array('forum' => $forum->id)),
                get_string('addanewtopic', 'forum'),
                array('class' => 'btn btn-primary')
            );
        }
        $output .= html_writer::end_div();
        $output .= "<br><div class='text-center'>$actionlinks</div>";
        $output .= html_writer::end_tag('div');

        return $output;
    }
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $firstrow = $secondrow = '';
        foreach ($tabtree->subtree as $tab) {
            $firstrow .= $this->render($tab);
            if (($tab->selected || $tab->activated) && !empty($tab->subtree) && $tab->subtree !== array()) {
                $secondrow = $this->tabtree($tab->subtree);
            }
        }
        return html_writer::tag('ul', $firstrow, array('class' => 'nav nav-tabs nav-justified')) . $secondrow;
    }

    protected function render_tabobject(tabobject $tab) {
        if ($tab->selected or $tab->activated) {
            return html_writer::tag('li', html_writer::tag('a', $tab->text), array('class' => 'active'));
        } else if ($tab->inactive) {
            return html_writer::tag('li', html_writer::tag('a', $tab->text), array('class' => 'disabled'));
        } else {
            if (!($tab->link instanceof moodle_url)) {
                // Backward compatibility when link was passed as quoted string
                // to avoid double escaping of ampersands etc.
                $link = '<a href="'.$tab->link.'" title="'.s($tab->title).'">'.$tab->text.'</a>';
            } else {
                $link = html_writer::link($tab->link, $tab->text, array('title' => $tab->title));
            }
            return html_writer::tag('li', $link);
        }
    }

    /**
     * add in additional classes that are used for Snap
     * get rid of YUI stuff so we can style it with bootstrap
     *
     * @param array $additionalclasses
     * @return array|string
     */
    public function body_css_classes(array $additionalclasses = array()) {
        global $PAGE, $COURSE;

        $classes = parent::body_css_classes($additionalclasses);
        $classes = explode (' ', $classes);

        $classes[] = 'device-type-'.$PAGE->devicetypeinuse;

        // Define the page types we want to purge yui classes from the body  - e.g. local-joulegrader-view,
        // local-pld-view, etc.
        $killyuipages = array(
            'local-pld-view',
            'local-joulegrader-view',
            'blocks-conduit-view',
            'blocks-reports-view',
            'grade-report-joulegrader-index',
            'grade-report-nortongrader-index',
            'admin-setting-modsettinglti',
            'blocks-campusvue-view',
            'enrol-instances',
            'admin-report-eventlist-index',
        );
        if (in_array($PAGE->pagetype, $killyuipages)) {
            $classes = array_diff ($classes, ['yui-skin-sam', 'yui3-skin-sam']);
            $classes [] = 'yui-bootstrapped';
        }

        $section = $PAGE->url->param('section');
        if ($COURSE->format === 'folderview' && !empty($section)) {
            $classes[] = 'folderview-single-section';
        }

        if (defined('BEHAT_SITE_RUNNING')) {
            // Required for revealing elements that behat requires in order to run.
            $classes[] = 'behat-site-running';
        }

        $classes = implode(' ', $classes);
        return $classes;
    }

    /**
     * Override to add a class to differentiate from other
     * #notice.box.generalbox that have buttons after them,
     * rather than inside them.
     */
    public function confirm($message, $continue, $cancel) {
        if (is_string($continue)) {
            $continue = new single_button(new moodle_url($continue), get_string('continue'), 'post');
        } else if ($continue instanceof moodle_url) {
            $continue = new single_button($continue, get_string('continue'), 'post');
        } else if (!$continue instanceof single_button) {
            throw new coding_exception(
                'The continue param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.'
            );
        }

        if (is_string($cancel)) {
            $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new single_button($cancel, get_string('cancel'), 'get');
        } else if (!$cancel instanceof single_button) {
            throw new coding_exception(
                'The cancel param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.'
            );
        }

        $output = $this->box_start('generalbox snap-continue-cancel', 'notice');
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), array('class' => 'buttons'));
        $output .= $this->box_end();
        return $output;
    }

    /**
     * Renders an action_menu_link item.
     *
     * @param action_menu_link $action
     * @return string HTML fragment
     */
    protected function render_action_menu_link(action_menu_link $action) {
        global $COURSE;
        if ($COURSE->id != SITEID) {
            if (   stripos($action->url, 'bui_hideid') !== false
                || stripos($action->url, 'bui_showid') !== false
            ) {
                $action->url->set_anchor('blocks');
            }
        }
        return (parent::render_action_menu_link($action));
    }

    public function pix_url($imagename, $component = 'moodle') {
        // Strip -24, -64, -256  etc from the end of filetype icons so we
        // only need to provide one SVG, see MDL-47082.
        $imagename = \preg_replace('/-\d\d\d?$/', '', $imagename);
        return $this->page->theme->pix_url($imagename, $component);
    }

    /**
     * Override parent to (optionally) remove the nav block.
     *
     * Always show when Behat tests are running as it is used by core
     * tests to navigate around the site.
     *
     * @todo For 2.7, when this will no longer be an option, we should
     * automatically turn off the nav block to stop all this at the source.
     */
    public function blocks_for_region($region) {
        $blockcontents = $this->page->blocks->get_content_for_region($region, $this);
        if (!empty($this->page->theme->settings->hidenavblock) && !defined('BEHAT_SITE_RUNNING')) {
            $blockcontents = array_filter($blockcontents, function ($bc) {
                if (!$bc instanceof block_contents) {
                    return true;
                }
                $isnavblock = strpos($bc->attributes['class'], 'block_navigation') !== false;
                return !$isnavblock;
            });
        }

        $blocks = $this->page->blocks->get_blocks_for_region($region);

        $lastblock = null;
        $zones = array();
        foreach ($blocks as $block) {
            $zones[] = $block->title;
        }
        $output = '';

        foreach ($blockcontents as $bc) {
            if ($bc instanceof block_contents) {
                    $output .= $this->block($bc, $region);
                    $lastblock = $bc->title;
            } else if ($bc instanceof block_move_target) {
                $output .= $this->block_move_target($bc, $zones, $lastblock, $region);
            } else {
                throw new coding_exception('Unexpected type of thing (' . get_class($bc) . ') found in list of block contents.');
            }
        }
        return $output;
    }

    /**
     * Render recent forum activity.
     *
     * @param array $activities
     * @return string
     */
    public function recent_forum_activity(Array $activities) {
        global $OUTPUT;
        $output = '';
        if (empty($activities)) {
            return '';
        }
        foreach ($activities as $activity) {
            if (!empty($activity->user)) {
                $userpicture = new user_picture($activity->user);
                $userpicture->link = false;
                $userpicture->alttext = false;
                $userpicture->size = 32;
                $picture = $OUTPUT->render($userpicture);
            } else {
                $picture = '';
            }

            $url = new moodle_url(
                    '/mod/'.$activity->type.'/discuss.php',
                    ['d' => $activity->content->discussion],
                    'p'.$activity->content->id
            );
            $fullname = fullname($activity->user);
            $meta = [
                $activity->courseshortname.' / '.$activity->forumname,
                $this->friendly_datetime($activity->timestamp)
            ];
            $formattedsubject = format_text($activity->content->subject);
            $output .= $this->snap_media_object($url, $picture, $fullname, $meta, $formattedsubject);
        }
        return $output;
    }

}

class theme_snap_core_renderer_ajax extends core_renderer_ajax {

    public function pix_url($imagename, $component = 'moodle') {
        // Strip -24, -64, -256  etc from the end of filetype icons so we
        // only need to provide one SVG, see MDL-47082.
        $imagename = \preg_replace('/-\d\d\d?$/', '', $imagename);
        return $this->page->theme->pix_url($imagename, $component);
    }

}
