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

namespace theme_snap\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/coursecatlib.php');

use stdClass;
use context_course;
use context_system;
use DateTime;
use html_writer;
use moodle_url;
use user_picture;
use theme_snap\local;
use theme_snap\services\course;
use theme_snap\renderables\settings_link;
use theme_snap\renderables\bb_dashboard_link;
use theme_snap\renderables\course_card;
// We have to force include this class as it's on login and the auto loader may not have been updated via a cache dump.
require_once($CFG->dirroot.'/theme/snap/classes/renderables/login_alternative_methods.php');
use theme_snap\renderables\login_alternative_methods;

class core_renderer extends toc_renderer {

    public function course_footer() {
        global $DB, $COURSE, $CFG, $PAGE;

        // Note: This check will be removed for Snap 2.7.
        if (empty($PAGE->theme->settings->coursefootertoggle)) {
            return false;
        }

        $context = context_course::instance($COURSE->id);
        $courseteachers = '';
        $coursesummary = '';

        $clist = new \course_in_list($COURSE);
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


    /**
     * Print links to more information for personal menu colums.
     *
     * @author: SL
     * @param string $langstring
     * @param string $iconname
     * @param string $url
     * @return string
     */
    public function column_header_icon_link($langstring, $iconname, $url) {
        global $OUTPUT;
        $text = get_string($langstring, 'theme_snap');
        $iconurl = $OUTPUT->pix_url($iconname, 'theme');
        $icon = '<img class="svg-icon" role="presentation" src="' .$iconurl. '">';
        $link = '<a class="snap-personal-menu-more" href="' .$url. '"><small>' .$text. '</small>' .$icon. '</a>';
        return $link;
    }


    /**
     * Print links for personal menu on mobile.
     *
     * @author: SL
     * @param string $langstring
     * @param string $iconname
     * @param string $url
     * @return string
     */
    public function mobile_menu_link($langstring, $iconname, $url) {
        global $OUTPUT;
        $alt = get_string($langstring, 'theme_snap');
        $iconurl = $OUTPUT->pix_url($iconname, 'theme');
        $icon = '<img class="svg-icon" alt="' .$alt. '" src="' .$iconurl. '">';
        $link = '<a href="' .$url. '">' .$icon. '</a>';
        return $link;
    }

    /**
     * Print links for social media icons.
     *
     * @author: SL
     * @param string $iconname
     * @param string $url
     * @return string
     */
    public function social_menu_link($iconname, $url) {
        global $OUTPUT;
        $iconurl = $OUTPUT->pix_url($iconname, 'theme');
        $icon = '<img class="svg-icon" title="' .$iconname. '" alt="' .$iconname. '" src="' .$iconurl. '">';
        $link = '<a href="' .$url. '" target="_blank">' .$icon. '</a>';
        return $link;
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
                // Get mod icon, empty alt as title already there.
                $img = html_writer::tag('img', '', array(
                    'src' => $OUTPUT->pix_url('icon', $modname),
                    'alt' => '',
                ));
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
        global $OUTPUT;
        if (!$settingslink->output) {
            return '';
        }
        $iconurl = $OUTPUT->pix_url('gear', 'theme');
        $gearicon = '<img src="' .$iconurl. '">';
        $url = '#inst' . $settingslink->instanceid;
        $attributes = array(
            'id' => 'admin-menu-trigger',
            'class' => 'pull-right',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'title' => get_string('admin', 'theme_snap'),
            'aria-label' => get_string('admin', 'theme_snap'),
        );

        return html_writer::link($url, $gearicon, $attributes);
    }


    /**
     * Settings link for opening the Administration menu, only shown if needed.
     * @param bb_dashboard_link $bblink
     *
     * @return string
     */
    public function render_bb_dashboard_link(bb_dashboard_link $bblink) {

        if (!$bblink->output) {
            return '';
        }

        $linkcontent = $this->render(new \pix_icon('sso', get_string('blackboard', 'local_geniusws'), 'local_geniusws')).
                get_string('dashboard', 'local_geniusws');
        $html = html_writer::link($bblink->loginurl, $linkcontent, ['class' => 'bb_dashboard_link']);

        return $html;
    }


    /**
     * Get badge renderer.
     * @return null|message_badge_renderer
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
        } catch (\Exception $e) {
            $badgerend = null;
        }

        // Note: In certain circumstances the snap message badge render is not loaded and the original message badge
        // render is loaded instead - e.g. when you initially switch to the snap theme.
        // This results in the fixy menu button looking broken as it shows the badge in its original format as opposed
        // to the overriden format provided by the snap message badge renderer.
        if (!$badgerend instanceof \theme_snap\output\message_badge_renderer) {
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
        if ($badgerend && $badgerend instanceof \theme_snap\output\message_badge_renderer) {
            $badges = '<div class="alert_stream">
                '.$badgerend->messagestitle().'
                    <div class="message_badge_container"></div>
                </div>';
        }
        return $badges;
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
            $url = new moodle_url('/course/');
            $output = $this->column_header_icon_link('browseallcourses', 'courses', $url);
        }
        return $output;
    }


    /**
     * Render messages from users
     * @return string
     */
    protected function render_messages() {
        if ($this->page->theme->settings->messagestoggle == 0) {
            return '';
        }

        $messagesheading = get_string('messages', 'theme_snap');
        $o = '<h2>'.$messagesheading.'</h2>';
        $o .= '<div id="snap-personal-menu-messages"></div>';

        $messagseurl = new moodle_url('/message/');
        $o .= $this->column_header_icon_link('viewmessaging', 'messages', $messagseurl);
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

        $forumpostsheading = get_string('forumposts', 'theme_snap');
        $o = '<h2>'.$forumpostsheading.'</h2>
        <div id="snap-personal-menu-forumposts"></div>';
        $forumurl = new moodle_url('/mod/forum/user.php', ['id' => $USER->id]);
        $o .= $this->column_header_icon_link('viewforumposts', 'forumposts', $forumurl);
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

        $metastr = '';
        // For forum posts meta is an array with the course title / forum name.
        if (is_array($meta)) {
            $metastr = '<span class="snap-media-meta">';
            foreach ($meta as $metaitem) {
                $metastr .= $metaitem.'<br>';
            }
            $metastr .= '</span>';
        } else if ($meta) {
            $metastr = '<span class="snap-media-meta">' .$meta.'</span>';
        }

        $title = '<h3>' .$title. '</h3>' .$content;
        $link = html_writer::link($url, $title);

        $object = $image
                . '<div class="snap-media-body">'
                . '<h3>' .$link. '</h3>'
                . $metastr
                . '</div>';

        return '<div class="snap-media-object '.$extraclasses.'">'.$object.'</div>';
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

        $mobilemenu = '<div id="fixy-mobile-menu">';
        $mobilemenu .= $this->mobile_menu_link('courses', 'courses', '#fixy-my-courses');
        $deadlines = $this->render_deadlines();
        if (!empty($deadlines)) {
            $columns[] = $deadlines;
            $mobilemenu .= $this->mobile_menu_link('deadlines', 'calendar', '#snap-personal-menu-deadlines');
        }

        $graded = $this->render_graded();
        $grading = $this->render_grading();
        if (empty($grading)) {
            $gradebookmenulink = $this->mobile_menu_link('recentfeedback', 'grading', '#snap-personal-menu-graded');
        } else {
            $gradebookmenulink = $this->mobile_menu_link('grading', 'grading', '#snap-personal-menu-grading');
        }
        if (!empty($grading)) {
            $columns[] = $grading;
            $mobilemenu .= $gradebookmenulink;
        } else if (!empty($graded)) {
            $columns[] = $graded;
            $mobilemenu .= $gradebookmenulink;
        }

        $badges = $this->render_badges();
        if (!empty($badges)) {
            $columns[] = '<div id="snap-personal-menu-badges">' .$badges. '</div>';
            $mobilemenu .= $this->mobile_menu_link('alerts', 'alerts', '#snap-personal-menu-badges');

        }

        $messages = $this->render_messages();
        if (!empty($messages)) {
            $columns[] = $messages;
            $mobilemenu .= $this->mobile_menu_link('messages', 'messages', '#snap-personal-menu-messages');
        }

        $forumposts = $this->render_forumposts();
        if (!empty($forumposts)) {
            $columns[] = $forumposts;
            $mobilemenu .= $this->mobile_menu_link('forumposts', 'forumposts', '#snap-personal-menu-forumposts');
        }

        $mobilemenu .= '</div>';

        if (empty($columns)) {
             return '';
        } else {
            $o = '<div class="callstoaction">';
            foreach ($columns as $column) {
                $o .= '<section>' .$column. '</section>';
            }
            $o .= '</div>'.$mobilemenu;
        }
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
        global $USER, $OUTPUT;

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
        global $OUTPUT;
        if (!$this->feedback_toggle_enabled()) {
            return '';
        }

        $recentfeedback = get_string('recentfeedback', 'theme_snap');
        $o = "<h2>$recentfeedback</h2>";
        $o .= '<div id="snap-personal-menu-graded"></div>';
        $url = new moodle_url('/grade/report/mygrades.php');
        $o .= $this->column_header_icon_link('viewmyfeedback', 'tick', $url);
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
        $o .= $this->column_header_icon_link('viewcalendar', 'calendar', $calurl);
        return $o;
    }


    /**
     * Print login button
     *
     */
    public function login_button() {
        global $CFG;

        $output = '';
        $loginurl = '#';
        $loginatts = [
            'aria-haspopup' => 'true',
            'class' => 'btn btn-default snap-login-button js-personal-menu-trigger',
        ];
        if (!empty($CFG->alternateloginurl)) {
            $loginurl = $CFG->wwwroot.'/login/index.php';
            $loginatts = [
                'class' => 'btn btn-default snap-login-button',
            ];
        }
        // This check is here for the front page login.
        if (!isloggedin() || isguestuser()) {
            $output = html_writer::link($loginurl, get_string('login'), $loginatts);
        }
        return $output;
    }

    /**
     * @param course_card $card
     * @return string
     * @throws \moodle_exception
     */
    public function render_course_card(course_card $card) {
        return $this->render_from_template('theme_snap/course_cards', $card);
    }

    /**
     * @param login_alternative_methods $methods
     * @return string
     */
    public function render_login_alternative_methods(login_alternative_methods $methods) {
        if (empty($methods->potentialidps)) {
            return '';
        }
        return $this->render_from_template('theme_snap/login_alternative_methods', $methods);
    }

    /**
     * The "fixy" overlay that drops down when the link in the top right corner is clicked. It will say either
     * "login" or "menu" (for signed in users).
     *
     */
    public function fixed_menu() {
        global $CFG, $USER;

        $logout = get_string('logout');
        $isguest = isguestuser();

        $courseservice = course::service();

        $output = '';
        if (!isloggedin() || $isguest) {
            $login = get_string('login');
            $cancel = get_string('cancel');
            if (!empty($CFG->loginpasswordautocomplete)) {
                $autocomplete = 'autocomplete="off"';
            } else {
                $autocomplete = '';
            }
            if (empty($CFG->authloginviaemail)) {
                $username = get_string('username');
            } else {
                $username = get_string('usernameemail');
            }
            if (empty($CFG->loginhttps)) {
                $wwwroot = $CFG->wwwroot;
            } else {
                $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
            }
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
                        '<a href="'.s($wwwroot).'/login/index.php">'.
                        get_string('helpwithloginandguest', 'theme_snap').'</a></p>';
                } else {
                    if (empty($CFG->forcelogin)) {
                        $help = get_string('helpwithloginandguest', 'theme_snap');
                    } else {
                        $help = get_string('helpwithlogin', 'theme_snap');
                    }
                    $helpstr = "<p class='text-center'><a href='".s($wwwroot)."/login/index.php'>$help</a></p>";
                }
            }
            if (local::current_url_path() != '/login/index.php') {
                $output .= $this->login_button();

                $altlogins = $this->render_login_alternative_methods(new login_alternative_methods());

                $output .= "<div class='fixy' id='snap-login' role='dialog' aria-label='$loginform' tabindex='-1'>
                    <form action='$wwwroot/login/index.php'  method='post'>
                    <div class=fixy-inner>
                    <div class=fixy-header>
                    <a id='fixy-close' class='js-personal-menu-trigger pull-right snap-action-icon' href='#'>
                        <i class='icon icon-close'></i><small>$cancel</small>
                    </a>
                    <h1>$login</h1>
                    </div>
                    <label for='username'>$username</label>
                    <input autocapitalize='off' type='text' name='username' id='username'>
                    <label for='password'>$password</label>
                    <input type='password' name='password' id='password' $autocomplete>
                    <br>
                    <input type='submit' value='" . s($login) . "'>
                    $helpstr
                    $altlogins
                    </div>
                    </form></div>";
            }
        } else {
            $courselist = "";
            $userpicture = new user_picture($USER);
            $userpicture->link = false;
            $userpicture->alttext = false;
            $userpicture->size = 100;
            $picture = $this->render($userpicture);

            list($favorited, $notfavorited) = $courseservice->my_courses_split_by_favorites();

            // Create courses array with favorites first.
            $mycourses = $favorited + $notfavorited;

            $courselist .= '<section id="fixy-my-courses"><div class="clearfix"><h2>' .get_string('courses'). '</h2>';
            $courselist .= '<div id="fixy-visible-courses">';

            // Default text when no courses.
            if (!$mycourses) {
                $courselist .= "<p>".get_string('coursefixydefaulttext', 'theme_snap')."</p>";
            }

            // Visible / hidden course vars.
            $visiblecoursecount = 0;
            // How many courses are in the hidden section (hidden and not favorited).
            $hiddencoursecount = 0;
            $hiddencourselist = '';
            // How many courses are actually hidden.
            $actualhiddencount = 0;

            foreach ($mycourses as $course) {

                $ccard = new course_card($course->id);
                $coursecard = $this->render($ccard);

                // If course is not visible.
                if (!$course->visible) {
                    $actualhiddencount++;
                    // Only add to list of hidden courses if not favorited.
                    if (!isset($favorited[$course->id])) {
                        $hiddencoursecount++;
                        $hiddencourselist .= $coursecard;
                    } else {
                        // OK, this is hidden but it's favorited, so technically visible.
                        $visiblecoursecount ++;
                        $courselist .= $coursecard;
                    }
                } else {
                    $visiblecoursecount ++;
                    $courselist .= $coursecard;
                }
            }
            $courselist .= '</div>';
            $courselist .= $this->browse_all_courses_button();
            $courselist .= '</div>';

            if ($actualhiddencount && $visiblecoursecount) {
                // Output hidden courses toggle when there are visible courses.
                $togglevisstate = !empty($hiddencourselist) ? ' state-visible' : '';
                $hiddencourses = '<div class="clearfix"><h2 class="header-hidden-courses'.$togglevisstate.'"><a id="js-toggle-hidden-courses" href="#">'. get_string('hiddencoursestoggle', 'theme_snap', $hiddencoursecount).'</a></h2>';
                $hiddencourses .= '<div id="fixy-hidden-courses" class="clearfix" tabindex="-1">' .$hiddencourselist. '</div>';
                $hiddencourses .= '</div>';
                $courselist .= $hiddencourses;
            } else if (!$visiblecoursecount && $hiddencoursecount) {
                $hiddencourses = '<div id="fixy-hidden-courses" class="clearfix state-visible">' .$hiddencourselist. '</div>';
                $courselist .= $hiddencourses;
            }
            $courselist .= '</section>';

            $menu = get_string('menu', 'theme_snap');
            $badge = $this->render_badge_count();
            $linkcontent = $menu.$picture.$badge;
            $attributes = array(
                'aria-haspopup' => 'true',
                'class' => 'js-personal-menu-trigger snap-my-courses-menu',
                'id' => 'fixy-trigger',
                'aria-controls' => 'primary-nav',
            );

            $output .= html_writer::link('#', $linkcontent, $attributes);

            $close = get_string('close', 'theme_snap');
            $viewyourprofile = get_string('viewyourprofile', 'theme_snap');
            $realuserinfo = '';
            if (\core\session\manager::is_loggedinas()) {
                $realuser = \core\session\manager::get_realuser();
                $via = get_string('via', 'theme_snap');
                $fullname = fullname($realuser, true);
                $realuserinfo = html_writer::span($via.' '.html_writer::span($fullname, 'real-user-name'), 'real-user-info');
            }

            $output .= '<nav id="primary-nav" class="fixy toggle-details" tabindex="-1">
            <div class="fixy-inner">
            <div class="fixy-header">
            <a id="fixy-close" class="js-personal-menu-trigger pull-right snap-action-icon" href="#">
                <i class="icon icon-close"></i><small>'.$close.'</small>
            </a>

            <div id="fixy-user">'.$picture.'
            <div id="fixy-user-details">
                <a title="'.s($viewyourprofile).'" href="'.s($CFG->wwwroot).'/user/profile.php" >'.
                    '<span class="h1" role="heading" aria-level="1">'.format_string(fullname($USER)).'</span>
                </a> '.$realuserinfo.'
                <a id="fixy-logout" href="'.s($CFG->wwwroot).'/login/logout.php?sesskey='.sesskey().'">'.$logout.'</a>
            </div>
            </div>
            </div>



        <div id="fixy-content">'
            .$courselist.$this->render_callstoaction().'
        </div><!-- end fixy-content -->
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

            if ($item->type == \navigation_node::TYPE_COURSE) {
                $courseitem = $item;
            }
            if ($item->type == \navigation_node::TYPE_SECTION) {
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

    /**
     * Cover image selector.
     * @return bool|null|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function cover_image_selector() {
        global $PAGE;
        if (has_capability('moodle/course:changesummary', $PAGE->context)) {
            $vars = ['accepttypes' => local::supported_coverimage_typesstr()];
            return $this->render_from_template('theme_snap/cover_image_selector', $vars);
        }
        return null;
    }

    /**
     * Get page heading.
     *
     * @param string $tag
     * @return string
     */
    public function page_heading($tag = 'h1') {
        global $COURSE, $PAGE;

        $heading = $this->page->heading;

        if ($this->page->pagelayout == 'mypublic') {
            // For the user profile page message button we need to call 2.9 content_header.
            $heading = parent::context_header();
        } else if ($COURSE->id != SITEID && stripos($heading, format_string($COURSE->fullname)) === 0) {
            // If we are on a course page which is not the site level course page.
            $courseurl = new moodle_url('/course/view.php', ['id' => $COURSE->id]);
            // Set heading to course fullname - ditch anything else that's in it.
            // This will make huge page headings like:
            // My course: View: Preferences: Grader report
            // simply show -
            // My course
            // This is intentional.
            $heading = $COURSE->fullname;
            $heading = html_writer::link($courseurl, $heading);
            $heading = html_writer::tag($tag, $heading);
        } else {
            // Default heading.
            $heading = html_writer::tag($tag, $heading);
        }

        // If we are on the main page of a course, add the cover image selector.
        if ($COURSE->id != SITEID) {
            $courseviewpage = local::current_url_path() === '/course/view.php';
            if ($courseviewpage) {
                $heading .= $this->cover_image_selector();
            }
        }

        // For the front page we add the site strapline.
        if ($this->page->pagelayout == 'frontpage') {
            $heading .= '<p class="snap-site-description">' . format_string($this->page->theme->settings->subtitle) . '</p>';
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
    protected function render_custom_menu(\custom_menu $menu) {
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
    protected function render_custom_menu_item(\custom_menu_item $menunode) {
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
        $context = \context_module::instance($cm->id, MUST_EXIST);

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

    protected function render_tabtree(\tabtree $tabtree) {
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

    protected function render_tabobject(\tabobject $tab) {
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
        global $PAGE, $COURSE, $SESSION;

        $classes = parent::body_css_classes($additionalclasses);
        $classes = explode (' ', $classes);

        $classes[] = 'device-type-'.$PAGE->devicetypeinuse;

        $forcepasschange = get_user_preferences('auth_forcepasswordchange');
        if (isset($SESSION->justloggedin) && empty($forcepasschange)) {
            $openfixyafterlogin = !empty($PAGE->theme->settings->personalmenulogintoggle);
            $onfrontpage = ($PAGE->pagetype === 'site-index');
            $onuserdashboard = ($PAGE->pagetype === 'my-index');
            if ($openfixyafterlogin && !isguestuser() && ($onfrontpage || $onuserdashboard)) {
                $classes[] = 'snap-fixy-open';
            }
        }
        unset($SESSION->justloggedin);

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

        if (!empty($PAGE->url)) {
            $section = $PAGE->url->param('section');
            if ($COURSE->format === 'folderview' && !empty($section)) {
                $classes[] = 'folderview-single-section';
            }
        }

        // Add completion tracking class.
        if (!empty($COURSE->enablecompletion)) {
            $classes[] = 'completion-tracking';
        }

        // Add resource display class.
        if (!empty($PAGE->theme->settings->resourcedisplay)) {
            $classes[] = 'snap-resource-'.$PAGE->theme->settings->resourcedisplay;
        } else {
            $classes[] = 'snap-resource-card';
        }

        // Add theme-snap class so modules can customise css for snap.
        $classes[] = 'theme-snap';

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
            $continue = new \single_button(new moodle_url($continue), get_string('continue'), 'post');
        } else if ($continue instanceof moodle_url) {
            $continue = new \single_button($continue, get_string('continue'), 'post');
        } else if (!$continue instanceof \single_button) {
            throw new \coding_exception(
                'The continue param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.'
            );
        }

        if (is_string($cancel)) {
            $cancel = new \single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new \single_button($cancel, get_string('cancel'), 'get');
        } else if (!$cancel instanceof \single_button) {
            throw new \coding_exception(
                'The cancel param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.'
            );
        }

        $output = $this->box_start('generalbox snap-continue-cancel', 'notice');
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), array('class' => 'buttons'));
        $output .= $this->box_end();
        return $output;
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
                if (!$bc instanceof \block_contents) {
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
            if ($bc instanceof \block_contents) {
                    $output .= $this->block($bc, $region);
                    $lastblock = $bc->title;
            } else if ($bc instanceof \block_move_target) {
                $output .= $this->block_move_target($bc, $zones, $lastblock, $region);
            } else {
                throw new \coding_exception('Unexpected type of thing (' . get_class($bc) . ') found in list of block contents.');
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
        $formatoptions = new stdClass;
        $formatoptions->filter = false;
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
            $forumpath = $activity->courseshortname. ' / ' .$activity->forumname;
            $meta = [
                local::relative_time($activity->timestamp),
                format_text($forumpath, FORMAT_HTML, $formatoptions)
            ];
            $formattedsubject = '<p>' .format_text($activity->content->subject, FORMAT_HTML, $formatoptions). '</p>';
            $output .= $this->snap_media_object($url, $picture, $fullname, $meta, $formattedsubject);
        }
        return $output;
    }


    /**
     * Return feature spot cards html.
     *
     * @return string
     */
    public function feature_spot_cards() {
        global $PAGE;

        $fsnames = array("fs_one", "fs_two", "fs_three");
        $features = array();
        // Note - we are using underscores in the settings to make easier to read.
        foreach ($fsnames as $feature) {
            $title = $feature . '_title';
            $text = $feature . '_text';
            $image = $feature . '_image';
            if (!empty($PAGE->theme->settings->$title) && !empty($PAGE->theme->settings->$text)) {
                $img = '';
                if (!empty($PAGE->theme->settings->$image)) {
                    $url = $this->page->theme->setting_file_url($image, $image);
                    $img = '<!--Card image-->
                    <img class="snap-feature-image" src="' .$url. '" alt="" role="presentation">';
                }
                $features[] = $this->feature_spot_card($PAGE->theme->settings->$title, $img, $PAGE->theme->settings->$text);
            }
        }

        $fscount = count($features);
        if ($fscount > 0) {
            $fstitle = '';
            if (!empty($PAGE->theme->settings->fs_heading)) {
                $fstitle = '<h2 class="snap-feature-spots-heading">' .s($PAGE->theme->settings->fs_heading). '</h2>';
            }

            $colclass = '';
            if ($fscount === 2) {
                $colclass = 'col-sm-6'; // Two cards = 50%.
            }
            if ($fscount === 3) {
                $colclass = 'col-sm-4'; // Three cards = 33.3%.
            }

            $cards = '';
            $i = 1;
            foreach ($features as $feature) {
                $cards .= '<div class="' .$colclass. '" id="snap-feature-' .$i. '">' .$feature. '</div>';
                $i++;
            }

            $fsedit = '';
            if ($this->page->user_is_editing()) {
                $url = new moodle_url('/admin/settings.php', ['section' => 'themesnapfeaturespots']);
                $link = html_writer::link($url, get_string('featurespotsedit', 'theme_snap'), ['class' => 'btn btn-primary']);
                $fsedit = '<p class="text-center">'.$link.'</p>';
            }

            // Build feature spots.
            $featurespots = '<div id="snap-feature-spots">';
            $featurespots .= $fstitle;
            $featurespots .= '<div class="row">' .$cards. '</div>';
            $featurespots .= $fsedit;
            $featurespots .= '</div>';

            // Return feature spots.
            return $featurespots;
        }
    }

    /**
     * Return feature spot card html.
     *
     * @param string $title
     * @param string $image
     * @param string $text
     * @return string
     */
    protected function feature_spot_card($title, $image, $text) {
        $card = '<div class="snap-feature">
            <!--Card content-->
            <div class="snap-feature-block">
                ' .$image. '
                <!--Title-->
                <h3 class="snap-feature-title h5">' .s($title). '</h3>
                <!--Content-->
                <p class="snap-feature-text">' .s($text). '</p>
            </div>
            <!--/.Card content-->
        </div>';
        return $card;
    }


    /**
     * Return featured courses html.
     * There are intentionally no checks for hidden course status
     * OR current users enrolment status.
     *
     * @return string
     */
    public function featured_courses() {
        global $PAGE, $DB;
        // Build array of course ids to display.
        $ids = array("fc_one", "fc_two", "fc_three", "fc_four", "fc_five", "fc_six", "fc_seven", "fc_eight");
        $courseids = array();
        foreach ($ids as $id) {
            if (!empty($PAGE->theme->settings->$id)) {
                $courseids[] = $PAGE->theme->settings->$id;
            }
        }

        // Get DB records for course ids.
        $courses = array();
        if (count($courseids)) {
            list ($coursesql, $params) = $DB->get_in_or_equal($courseids);
            $sql = "SELECT * FROM {course} WHERE id $coursesql";
            $courses = $DB->get_records_sql($sql, $params);
        } else {
            return '';
        }

        // Order records to match order input.
        $orderedcourses = array();
        foreach ($courseids as $courseid) {
            if (!empty($courses[$courseid])) {
                $orderedcourses[] = $courses[$courseid];
            }
        }

        // Build html for course card.
        $cards = array();
        foreach ($orderedcourses as $course) {
            $cards[] = $this->featured_course($course);
        }

        // Double check there is content, or return ''.
        $count = count($cards);
        if ($count < 1) {
            return '';
        }

        // Build grid and output.
        // Calculate boostrap column class.
        $colclass = '';
        if ($count >= 4) {
            $colclass = 'col-sm-3'; // Four cards = 25%.
        }
        if ($count === 2) {
            $colclass = 'col-sm-6'; // Two cards = 50%.
        }
        if ($count === 3 || $count === 6) {
            $colclass = 'col-sm-4'; // Three cards = 33.3%.
        }

        // Build featured courses cards.
        $i = 1;
        $colums = '';
        foreach ($cards as $card) {
            $colums .= '<div class="' .$colclass. '" id="snap-featured-course-' .$i. '">' .$card. '</div>';
            $i++;
        }

        // Featured courses title.
        $title = '';
        if (!empty($PAGE->theme->settings->fc_heading)) {
            $title = '<h2 class="snap-featured-courses-heading">' .s($PAGE->theme->settings->fc_heading). '</h2>';
        }

        // Featured courses browse all link.
        $browse = '';
        if (!empty($PAGE->theme->settings->fc_browse_all)) {
            $url = new moodle_url('/course/');
            $link = html_writer::link($url, get_string('featuredcoursesbrowseall', 'theme_snap'), ['class' => 'btn btn-primary']);
            $browse = '<p class="text-center">'.$link.'</p>';
        }

        // Featured courses quick edit link.
        $edit = '';
        if ($this->page->user_is_editing()) {
            $url = new moodle_url('/admin/settings.php', ['section' => 'themesnapfeaturedcourses']);
            $link = html_writer::link($url, get_string('featuredcoursesedit', 'theme_snap'), ['class' => 'btn btn-primary']);
            $edit = '<p class="text-center">'.$link.'</p>';
        }

        // Build featured courses section.
        $output = '<div id="snap-featured-courses" class="text-center">';
        $output .= $title;
        $output .= '<div class="row text-center">' .$colums. '</div>';
        $output .= $browse;
        $output .= $edit;
        $output .= '</div>';
        // Return featured courses.
        return $output;
    }

    /**
     * Return featured course card html.
     *
     * @param object $course
     * @return string
     */
    protected function featured_course($course) {
        $url = new moodle_url('/course/view.php?id=' .$course->id);
        $coverimage = local::course_coverimage_url($course->id);
        $bgcss = '';
        if (!empty($coverimage)) {
            $bgcss = "background-image: url($coverimage);";
        }
        $card = '<a href="' .$url. '" class="snap-featured-course" style="' .$bgcss.'">
            <!--Card content-->
            <span class="snap-featured-course-title">' .s($course->fullname). '</span>
        </a>';
        return $card;
    }
}
