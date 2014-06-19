<?php
// This file is part of The Snap theme
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
 * Snap core renderer
 *
 * @package    theme_snap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once('toc_renderer.php');

class theme_snap_core_renderer extends toc_renderer {

    /**
     * Print  settings link
     *
     * @return string
     */
    public function print_settings_link() {
        global $DB;

        if (!$instanceid = $DB->get_field('block_instances', 'id', array('blockname' => 'settings'))) {
            return '';
        }
        if (!has_capability('moodle/block:view', context_block::instance($instanceid))) {
            return '';
        }
        $admin = get_string('admin', 'theme_snap');
        return '<div><a class="settings-button snap-action-icon" href="#inst'.$instanceid.'">
                <i class="icon icon-arrows-02"></i><small>'.$admin.'</small></a></div>';
    }

    /**
     * Print link to browse all courses
     *
     * @return string
     */
    public function print_view_all_courses() {
        global $CFG;

        $output = '';
        if (!empty($CFG->navshowallcourses) || has_capability('moodle/site:config', context_system::instance())) {
            $browse = get_string('browseallcourses', 'theme_snap');
            $output = '<a class="btn btn-primary moodle-browseallcourses" href="'.$CFG->wwwroot.
                      '/course/index.php">'.$browse.'</a>';
        }
        return $output;
    }

    /**
     * Print  login or menu for signed in users
     *
     */
    public function print_login() {
        global $CFG, $USER, $PAGE;

        $logout = get_string('logout');

        $badge = '';
        try {
            /** @var theme_snap_message_badge_renderer|null $badgerend */
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

        $isguest = isguestuser();

        if (!isloggedin() || $isguest) {
            $loginurl = '#login';
            if (!empty($CFG->alternateloginurl)) {
                $loginurl = $CFG->wwwroot.'/login/index.php';
            }
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
            echo "<a class='fixy-trigger btn btn-primary'  href='".s($loginurl)."'>$login</a>
        <form class=fixy action='$CFG->wwwroot/login/'  method='post' id='login'>
        <a id='fixy-close' class='pull-right snap-action-icon' href='#'>
            <i class='icon icon-office-52'></i><small>$cancel</small>
        </a>
            <div class=fixy-inner>
            <legend>$loginform</legend>
            <label for='username'>$username</label>
            <input type='text' name='username' id='username' autocorrect='off' autocapitalize='off' placeholder='".s($username)."'>
            <label for='password'>$password</label>
            <input type='password' name='password' id='password' placeholder='".s($password)."'>
            <br>
            <input type='submit' id='loginbtn' value='".s($login)."'>
            $helpstr
            </div>
        </form>";
        } else {
            $courselist = "";
            $userpicture = new user_picture($USER);
            $userpicture->link = false;
            $userpicture->alttext = false;
            $userpicture->size = 100;
            $picture = $this->render($userpicture);
            $mycourses = enrol_get_my_courses(null, 'visible DESC, fullname ASC');

            $longlistclass = "";
            if (count($mycourses) > 11) {
                $longlistclass = "list-large";
            }

            $courselist .= "<div id='fixy-my-courses'>
     <ul class='list-unstyled $longlistclass'>";
            foreach ($mycourses as $c) {
                $pubstatus = "";
                if (!$c->visible) {
                    $notpublished = get_string('notpublished', 'theme_snap');
                    $pubstatus = "<br><em class='published-status'><small>$notpublished</small></em>";
                }
                $clink = "<li><a href='$CFG->wwwroot/course/view.php?id=$c->id'>".format_string($c->fullname)."</a>".$pubstatus;
                $courselist .= "</li>".$clink;
            }
            $courselist .= "</ul></div>";
            $courselist .= '<div class="row">';
            $courselist .= '<div class="col-md-6">';
            $courselist .= $this->print_view_all_courses();
            $courselist .= '</div>';
            if (has_capability('moodle/site:config', context_system::instance())) {
                $courserenderer = $PAGE->get_renderer('core', 'course');
                $courselist .= '<div class="col-md-6">';
                $courselist .= $courserenderer->course_search_form(null, 'fixy');
                $courselist .= '</div>';
            }
            $courselist .= '</div>'; // Close row.
            $menu = get_string('menu', 'theme_snap');
            if ($badgerend) {
                $badge = $badgerend->badge($USER->id);
            }
            echo "<a class=fixy-trigger href='#primary-nav'>$menu &nbsp; $picture $badge</a>";
            $close = get_string('close', 'theme_snap');
            $viewyourprofile = get_string('viewyourprofile', 'theme_snap');
            $realuserinfo = '';
            if (\core\session\manager::is_loggedinas()) {
                $realuser = \core\session\manager::get_realuser();
                $via = get_string('via', 'theme_snap');
                $fullname = fullname($realuser, true);
                $realuserinfo = html_writer::span($via.' '.html_writer::span($fullname, 'real-user-name'), 'real-user-info');
            }

            // Generate alert stream html if message/output/badge available.
            $alertstream = '';
            if ($badgerend && $badgerend instanceof theme_snap_message_badge_renderer) {
                $alertstream = '<div class="alert_stream">
                '.$badgerend->messagestitle().'
                    <hr />
                    <div class="message_badge_container"></div>
                    <hr />
                </div>';
            }

            echo '<div class=fixy id="primary-nav" class="toggle-details" role="menu" aria-live="polite" tabindex="0">
        <a id="fixy-close" class="pull-right snap-action-icon" href="#">
            <i class="icon icon-office-52"></i><small>'.$close.'</small>
        </a>
        <div class=fixy-inner>
        <h1 id="fixy-profile-link">
            <a title="'.s($viewyourprofile).'" href="'.s($CFG->wwwroot).'/user/profile.php" >'.
                $picture.'<div id="fixy-username">'.format_string(fullname($USER)).'</div>
            </a>
        </h1>'.$realuserinfo.'
        <h2>'.get_string('courses').'</h2>
        <hr> '.$courselist.' <hr>
        '.$alertstream.'
        <div class="clearfix text-center">
        <a class="btn btn-primary" href="'.s($CFG->wwwroot).'/login/logout.php?sesskey='.sesskey().'">'.$logout.'</a>
    </div>
</div>
</div>';
        }

    }



    /*
     * This renders a notification message.
     * Uses bootstrap compatible html.
     */
    public function notification($message, $classes = 'notifyproblem') {
        $classes = renderer_base::prepare_classes($classes);
        $classes = str_replace(array(
            'notifyproblem',
            'notifysuccess',
            'notifymessage',
            'redirectmessage',
        ), array(
            'alert alert-danger',
            'alert alert-success',
            'alert alert-info',
            'alert alert-block alert-info',
        ), $classes);

        return parent::notification($message, $classes);
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
                return ($section);
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
        $course = course_get_format($COURSE)->get_course();

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
                    if ($course->coursedisplay != 1 && ($COURSE->format == 'topics' || $COURSE->format == 'weeks')) {
                        $url .= '#section-'.$sectionnumber;
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
     * Custom hook that requires a patch to /index.php
     * for customized rendering of front page news.
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

        if (!$discussions = forum_get_discussions($cm,
            'p.modified DESC', true, null, $SITE->newsitems, false, -1, $SITE->newsitems)) {
            $output .= html_writer::tag('div', '('.get_string('nonews', 'forum').')', array('class' => 'forumnodiscuss'));

            $groupmode    = groups_get_activity_groupmode($cm, $SITE);
            $currentgroup = groups_get_activity_group($cm);

            if (forum_user_can_post_discussion($forum, $currentgroup, $groupmode, $cm, $context)) {
                $output .= html_writer::link(
                    new moodle_url('/mod/forum/post.php', array('forum' => $forum->id)),
                    get_string('addanewtopic', 'forum'),
                    array('class' => 'btn btn-primary')
                );
            }
            return $output;
        }
        $output .= html_writer::start_div('', array('id' => 'news-articles'));
        foreach ($discussions as $discussion) {
            if (!forum_user_can_see_discussion($forum, $discussion, $context)) {
                continue;
            }
            $message    = file_rewrite_pluginfile_urls($discussion->message,
                          'pluginfile.php', $context->id, 'mod_forum', 'post', $discussion->id);

            $imagestyle = '';
            if (preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $message, $matches)) {
                $imageurl = $matches[1][0];
                if (!empty($imageurl)) {
                    $imageurl   = s($imageurl);
                    $imagestyle = " style=\"background-image:url('$imageurl')\"";

                }
            }
            $name    = format_string($discussion->name, true, array('context' => $context));
            $date    = userdate($discussion->timemodified, get_string('strftimedatetime', 'langconfig'));

            $readmorebtn = "<a class='btn btn-primary' href='".
                $CFG->wwwroot."/mod/forum/discuss.php?d=".$discussion->discussion."'>".
                get_string('readmore', 'theme_snap')."&nbsp;&#187;</a>";

            $preview = '';
            $newsimage = '';
            if (!$imagestyle) {
                $preview = html_to_text($message, 0, false);
                $preview = "<div class='news-article-preview col-md-6 col-sm-6'><p>".shorten_text($preview, 200)."</p>
                <p class='text-right'>".$readmorebtn."</p></div>";
            } else {
                $newsimage = '<div class="news-article-image col-md-6 col-sm-6"'.$imagestyle.'></div>';
            }

            $output .= <<<HTML
<div class="news-article clearfix">
    $newsimage
    <div class="news-article-inner col-md-6 col-sm-6">
        <div class="news-article-content">
            <h3><a href="$CFG->wwwroot/mod/forum/discuss.php?d=$discussion->discussion">$name</a></h3>
            <em class="news-article-date">$date</em>
        </div>
    </div>
    $preview
</div>
HTML;
        }
        $morelink = html_writer::link(
            new moodle_url('/mod/forum/view.php', array('id' => $cm->id)),
            get_string('morenews', 'theme_snap'),
            array('class' => 'btn btn-primary')
        );
        $output .= html_writer::end_div();
        $output .= "<br><div class='text-center'>$morelink</div>";
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
     * get rid of YUI stuff so we can style it with bootstrap
     *
     * @param array $additionalclasses
     * @return array|string
     */
    public function body_css_classes(array $additionalclasses = array()) {
        global $PAGE;

        $classes = parent::body_css_classes($additionalclasses);
        $classes .= $classes == '' ? '' : ' ';
        $classes .= 'device-type-'.$PAGE->devicetypeinuse;

        // Define the page types we want to purge yui classes from the body  - e.g. local-joulegrader-view,
        // local-pld-view, etc.
        $killyuipages = array(
            'local-pld-view',
            'local-joulegrader-view',
            'blocks-conduit-view',
            'blocks-reports-view',
            'grade-report-joulegrader-index',
            'grade-report-nortongrader-index',
        );
        if (in_array($PAGE->pagetype, $killyuipages)) {

            // Purge yui classes.
            $classes = explode (' ', $classes);
            $classes = array_diff ($classes, array('yui-skin-sam', 'yui3-skin-sam'));
            // Add yui bootstrapped class so we know that we have got rid of the yui stuff and intend to style with
            // bootstrap.
            $classes [] = 'yui-bootstrapped';
            $classes = implode(' ', $classes);

        }

        return ($classes);
    }

    /**
     * Override to add a class to differentiate from other
     * #notice.box.generalbox that have buttons after them,
     * rather than inside them.
     */
    public function confirm($message, $continue, $cancel) {
        if ($continue instanceof single_button) {
            // OK.
        } else if (is_string($continue)) {
            $continue = new single_button(new moodle_url($continue), get_string('continue'), 'post');
        } else if ($continue instanceof moodle_url) {
            $continue = new single_button($continue, get_string('continue'), 'post');
        } else {
            throw new coding_exception(
                'The continue param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.'
            );
        }

        if ($cancel instanceof single_button) {
            // OK.
        } else if (is_string($cancel)) {
            $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new single_button($cancel, get_string('cancel'), 'get');
        } else {
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
}
