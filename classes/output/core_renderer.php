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
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/message/output/popup/lib.php');

use core_auth\output\login;
use stdClass;
use context_course;
use context_system;
use coding_exception;
use single_button;
use DateTime;
use html_writer;
use moodle_url;
use navigation_node;
use user_picture;
use theme_snap\local;
use theme_snap\services\course;
use theme_snap\renderables\settings_link;
use theme_snap\renderables\genius_dashboard_link;
use theme_snap\renderables\course_card;
use theme_snap\renderables\course_toc;
use theme_snap\renderables\featured_courses;
use lang_string;
use core_course_category;
use core\navigation\output\primary;

// We have to force include this class as it's on login and the auto loader may not have been updated via a cache dump.
require_once($CFG->dirroot.'/theme/snap/classes/renderables/login_alternative_methods.php');
use theme_snap\renderables\login_alternative_methods;

class core_renderer extends \theme_boost\output\core_renderer {

    /* Login option rendering variables */
    const ENABLED_LOGIN_BOTH = '0';
    const ENABLED_LOGIN_MOODLE = '1';
    const ENABLED_LOGIN_ALTERNATIVE = '2';
    const ORDER_LOGIN_MOODLE_FIRST = '0';
    const ORDER_LOGIN_ALTERNATIVE_FIRST = '1';


    /**
     * @var array|string[]
     */
    private array $listhidden = [
        'pluginxp' => '/blocks/xp/index.php'
    ];

    /**
     * Copied from outputrenderer.php
     * Heading with attached help button (same title text)
     * and optional icon attached.
     *
     * @param string $text A heading text
     * @param string $helpidentifier The keyword that defines a help page
     * @param string $component component name
     * @param string|moodle_url $icon
     * @param string $iconalt icon alt text
     * @param int $level The level of importance of the heading. Defaulting to 2
     * @param string $classnames A space-separated list of CSS classes. Defaulting to null
     * @return string HTML fragment
     */
    public function heading_with_help($text, $helpidentifier, $component = 'moodle', $icon = '', $iconalt = '',
                                      $level = 2, $classnames = null) {
        global $USER;
        $image = '';
        if ($icon) {
            $image = $this->pix_icon($icon, $iconalt, $component, array('class' => 'icon iconlarge'));
        }

        $help = '';
        $collapsablehelp = '';
        if ($helpidentifier) {
            // Display header mod help as collapsable instead of popover for mods.
            if ($helpidentifier === 'modulename') {
                // Get mod help text.
                $modnames = get_module_types_names();
                $modname = $modnames[$component];
                $contentitemservice = new \core_course\local\service\content_item_service(
                    new \core_course\local\repository\content_item_readonly_repository()
                );
                $mod = $contentitemservice->get_content_items_by_name_pattern($USER, $modname);
                if (!empty($mod) && isset($mod[$component]) && is_object($mod[$component]) && $mod[$component]->help) {
                    $helptext = format_text($mod[$component]->help, FORMAT_MARKDOWN);
                    $data = (object) [
                        'helptext' => $helptext,
                        'modtitle' => $mod[$component]->title
                    ];
                    $collapsablehelp = $this->render_from_template('theme_snap/heading_help_collapse', $data);
                    $classnames .= ' d-inline';
                }
                $heading = $this->heading($image.$text, $level, $classnames);
                // Return heading and help.
                return $heading.$collapsablehelp;
            } else {
                $help = $this->help_icon($helpidentifier, $component);
            }
        }

        return $this->heading($image.$text.$help, $level, $classnames);
    }

    /**
     * @return bool|string
     * @throws \moodle_exception
     */
    public function course_toc() {
        $coursetoc = new course_toc();
        return $this->render_from_template('theme_snap/course_toc', $coursetoc);
    }

    /**
     * get course image
     *
     * @return bool|\moodle_url
     */

    public function get_course_image() {
        global $COURSE;

        return \theme_snap\local::course_coverimage_url($COURSE->id);
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
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $OUTPUT;
        $text = get_string($langstring, 'theme_snap');
        $iconurl = $OUTPUT->image_url($iconname, 'theme');
        $icon = '<img class="svg-icon" role="presentation" src="' .$iconurl. '" alt="">';
        $link = '<a class="snap-personal-menu-more" href="' .$url. '"><small>' .$text. '</small>' .$icon. '</a>';
        return $link;
        // @codingStandardsIgnoreEnd
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
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $OUTPUT;
        $alt = get_string($langstring, 'theme_snap');
        $iconurl = $OUTPUT->image_url($iconname, 'theme');
        $icon = '<img class="svg-icon" alt="' .$alt. '" src="' .$iconurl. '">';
        $class = '';
        if ($iconname == 'courses') {
            $class = 'state-active'; // Initial menu iteam on load.
        }
        $link = '<a href="' .$url. '" class="' .$class. '">' .$icon. '</a>';
        return $link;
        // @codingStandardsIgnoreEnd
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
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $OUTPUT;
        $iconurl = $OUTPUT->image_url($iconname, 'theme');
        $icon = '<img class="svg-icon" title="' .$iconname. '" alt="' .$iconname. '" src="' .$iconurl. '">';
        $link = '<a href="' .$url. '" target="_blank">' .$icon. '</a>';
        return $link;
        // @codingStandardsIgnoreEnd
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
        // @codingStandardsIgnoreStart
        $gearicon = '<svg xmlns="http://www.w3.org/2000/svg" id="snap-admin-icon" viewBox="0 0 100 100">
                        <title>'.get_string('admin', 'theme_snap').'</title>
                        <path d="M85.2,54.9c0.2-1.4,0.3-2.9,0.3-4.5c0-1.5-0.1-3-0.3-4.5l9.6-7.5c0.9-0.7,1-1.9,0.6-2.9l-9.1-15.8c-0.6-1-1.8-1.3-2.8-1
                        l-11.3,4.6c-2.4-1.8-4.9-3.3-7.7-4.5l-1.8-12c-0.1-1-1-1.9-2.2-1.9H42.3c-1.1,0-2.1,0.9-2.2,1.9l-1.7,12.1c-2.8,1.1-5.3,2.7-7.7,4.5
                        l-11.3-4.6c-1-0.4-2.2,0-2.8,1L7.5,35.6c-0.6,1-0.3,2.2,0.6,2.9l9.6,7.5c-0.2,1.4-0.3,2.9-0.3,4.5c0,1.5,0.1,3,0.3,4.5L8,62.4
                        c-0.9,0.7-1,1.9-0.6,2.9l9.1,15.8c0.6,1,1.8,1.3,2.8,1l11.3-4.6c2.4,1.8,4.9,3.3,7.7,4.5L40,94.1c0.1,1,1,1.9,2.2,1.9h18.2
                        c1.1,0,2.1-0.9,2.2-1.9L64.3,82c2.8-1.1,5.3-2.7,7.7-4.5l11.3,4.6c1,0.4,2.2,0,2.8-1l9.1-15.8c0.6-1,0.3-2.2-0.6-2.9
                        C94.6,62.4,85.2,54.9,85.2,54.9z M51.4,34.6c8.8,0,15.9,7.1,15.9,15.9s-7.1,15.9-15.9,15.9s-15.9-7.1-15.9-15.9S42.6,34.6,51.4,34.6
                        z" class="snap-gear-icon"/>
                    </svg>';
         // @codingStandardsIgnoreEnd
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
     * Link to genius, only shown if needed.
     * @param genius_dashboard_link $geniuslink
     *
     * @return string
     */
    public function render_genius_dashboard_link(genius_dashboard_link $geniuslink) {

        if (!$geniuslink->output) {
            return '';
        }

        $linkcontent = $this->render(new \pix_icon('sso', get_string('openlms', 'local_geniusws'), 'local_geniusws')).
                get_string('dashboard', 'local_geniusws');
        $html = html_writer::link($geniuslink->loginurl, $linkcontent, ['class' => 'genius_dashboard_link hidden-md-down']);
        return $html;
    }

    public function activity_header() {
        $renderer = $this->page->get_renderer('core');
        $header = $this->page->activityheader;
        $headercontext = $header->export_for_template($renderer);
        if (!empty($headercontext)) {
            return $this->render_from_template('core/activity_header', $headercontext);
        }
        return '';
    }

    /**
     * Badge counter for new messages.
     * @return string
     */
    protected function render_message_icon() {
        global $CFG, $USER;

        // Add the messages icon with message count.
        // The icon should not be displayed if the user is not logged in.
        if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER) ||
            get_user_preferences('auth_forcepasswordchange') ||
            (!$USER->policyagreed && !is_siteadmin() &&
                ($manager = new \core_privacy\local\sitepolicy\manager()) && $manager->is_defined())) {
            return '';
        }

        if (!empty($CFG->messaging) && !empty(get_config('theme_snap', 'messagestoggle'))) {
            $url = new \moodle_url($CFG->wwwroot."/message/");
            // Get number of unread conversations.
            $unreadcount = \core_message\api::count_unread_conversations($USER);
            $unreadconversationsstr = get_string('unreadconversations', 'core_message', $unreadcount);
            $ariaopenmessagedrawer = get_string('openmessagedrawer', 'theme_snap');
            return '<div class="badge-count-container">
                        <a class="snap-message-count" aria-label="'.$ariaopenmessagedrawer.$unreadconversationsstr.'" +
                         href="'.$url.'" title="'.$ariaopenmessagedrawer.$unreadconversationsstr.'">
                            <i class="icon fa fa-comment fa-fw">
                                <div class="conversation_badge_count hidden"></div>
                            </i>
                        </a>
                    </div>';
        }
        return '';
    }

    /**
     * Render messages from users
     * @return string
     */
    protected function render_messages() {
        if (empty($this->page->theme->settings->messagestoggle)) {
            return '';
        }

        $heading = get_string('messages', 'theme_snap');
        if ($this->advanced_feeds_enabled()) {
            $o = ce_render_helper::get_instance()
                ->render_feed_web_component('messages', $heading, get_string('nomessages', 'theme_snap'));
        } else {
            $o = '<h2>'.$heading.'</h2>';
            $o .= '<div id="snap-personal-menu-messages"></div>';
        }

        $url = new moodle_url('/message/');
        $o .= $this->column_header_icon_link('viewmessaging', 'messages', $url);
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

        $heading = get_string('forumposts', 'theme_snap');
        if ($this->advanced_feeds_enabled()) {
            $virtualpaging = true; // Web service retrieves all elements, need to do virtual paging.
            $o = ce_render_helper::get_instance()->render_feed_web_component('forumposts', $heading,
                            get_string('noforumposts', 'theme_snap'), $virtualpaging);
        } else {
            $o = '<h2>'.$heading.'</h2>
            <div id="snap-personal-menu-forumposts"></div>';
        }

        $url = new moodle_url('/mod/forum/user.php', ['id' => $USER->id]);
        $o .= $this->column_header_icon_link('viewforumposts', 'forumposts', $url);
        return $o;
    }


    /**
     * @param moodle_url|string $url
     * @param string $image
     * @param string $title
     * @param array|string $meta
     * @param string $content
     * @param string $extraclasses
     * @param string $attributes
     * @return string
     */
    public function snap_media_object($url, $image, $title, $meta, $content, $extraclasses = '', $attributes = '') {
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

        $data = (object) [
                'image' => $image,
                'content' => $link.$metastr,
                'class' => $extraclasses,
                'attributes' => $attributes
        ];
        return $this->render_from_template('theme_snap/media_object', $data);
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

    /**
     * Output moodle blocks and Snap wrapper with edit button.
     * @return string
     */
    public function snap_blocks() {
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $COURSE, $OUTPUT;

        $editblocks = '';
        $output = '';

        $oncoursepage = strpos($this->page->pagetype, 'course-view') === 0;
        $coursecontext = \context_course::instance($COURSE->id);
        if ($COURSE->format !== 'tiles') {
            if ($oncoursepage && has_capability('moodle/course:update', $coursecontext)) {
                $url = new \moodle_url('/course/view.php', ['id' => $COURSE->id, 'sesskey' => sesskey()]);
                if ($this->page->user_is_editing()) {
                    $url->param('edit', 'off');
                    $editstring = get_string('turneditingoff');
                } else {
                    $url->param('edit', 'on');
                    $editstring = get_string('editcoursecontent', 'theme_snap');
                }
                $editblocks = '<div class="text-center"><a href="' . $url . '" class="btn btn-primary">' . $editstring . '</a></div><br>';
            }

            $output .= '<div id="moodle-blocks" class="clearfix">';
            $output .= $editblocks;
            $output .= $OUTPUT->blocks('side-pre');
            $output .= '</div>';
        } else {
            if ($oncoursepage && $this->page->user_is_editing()) {
                $output .= '<div id="moodle-blocks" class="clearfix editing-tiles">';
            } else {
                $output .= '<div id="moodle-blocks" class="clearfix">';
            }
            $output .= $OUTPUT->blocks('side-pre');
            $output .= '</div>';
        }

        return $output;
        // @codingStandardsIgnoreEnd
    }

    private function get_calltoaction_url($key) {
        return '#snap-personal-menu-' .
            ($this->advanced_feeds_enabled() ? 'feed-' : '') .
            $key;
    }

    public function edit_button(moodle_url $url, string $method = 'post') {
        return '';
    }

    protected function render_callstoaction() {

        $mobilemenu = '<div id="snap-pm-mobilemenu">';
        $mobilemenu .= $this->mobile_menu_link('courses', 'courses', '#snap-pm-courses');
        $deadlines = $this->render_deadlines();
        if (!empty($deadlines)) {
            $columns[] = $deadlines;
            $mobilemenu .= $this->mobile_menu_link('deadlines', 'calendar', $this->get_calltoaction_url('deadlines'));
        }

        $graded = $this->render_graded();
        $grading = $this->render_grading();
        if (empty($grading)) {
            $gradebookmenulink = $this->mobile_menu_link('recentfeedback', 'grading', $this->get_calltoaction_url('graded'));
        } else {
            $gradebookmenulink = $this->mobile_menu_link('grading', 'grading', $this->get_calltoaction_url('grading'));
        }
        if (!empty($grading)) {
            $columns[] = $grading;
            $mobilemenu .= $gradebookmenulink;
        } else if (!empty($graded)) {
            $columns[] = $graded;
            $mobilemenu .= $gradebookmenulink;
        }

        $messages = $this->render_messages();
        if (!empty($messages)) {
            $columns[] = $messages;
            $mobilemenu .= $this->mobile_menu_link('messages', 'messages', $this->get_calltoaction_url('messages'));
        }

        $forumposts = $this->render_forumposts();
        if (!empty($forumposts)) {
            $columns[] = $forumposts;
            $mobilemenu .= $this->mobile_menu_link('forumposts', 'forumposts', $this->get_calltoaction_url('forumposts'));
        }

        $mobilemenu .= '</div>';

        if (empty($columns)) {
             return '';
        } else {
            $sections = [];
            $intelliboard = $this->render_intelliboard();
            $intellicart = $this->render_intellicart();
            if (!empty($intelliboard)) {
                $sections[] = $intelliboard;
            }
            if (!empty($intellicart)) {
                $sections[] = $intellicart;
            }
            foreach ($columns as $column) {
                if (!empty($column)) {
                    $sections[] = $column;
                }
            }
        }

        $data = (object) [
            'update' => $sections,
            'mobilemenu' => $mobilemenu
        ];
        return $data;
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
     * Is advanced feeds enabled?
     *
     * @return bool
     */
    private function advanced_feeds_enabled() {
        if (property_exists($this->page->theme->settings, 'personalmenuadvancedfeedsenable')
            && $this->page->theme->settings->personalmenuadvancedfeedsenable == 1) {
            return true;
        }
        return false;
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

        $heading = get_string('grading', 'theme_snap');
        if ($this->advanced_feeds_enabled()) {
            $virtualpaging = true; // Web service retrieves all elements, need to do virtual paging.
            $o = ce_render_helper::get_instance()->render_feed_web_component('grading', $heading,
                            get_string('nograding', 'theme_snap'), $virtualpaging);
        } else {
            $o = "<h2>$heading</h2>";
            $o .= '<div id="snap-personal-menu-grading"></div>';
        }

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

        $heading = get_string('recentfeedback', 'theme_snap');
        if ($this->advanced_feeds_enabled()) {
            $virtualpaging = true; // Web service retrieves all elements, need to do virtual paging.
            $o = ce_render_helper::get_instance()->render_feed_web_component('graded', $heading,
                            get_string('nograded', 'theme_snap'), $virtualpaging);
        } else {
            $o = "<h2>$heading</h2>";
            $o .= '<div id="snap-personal-menu-graded"></div>';
        }

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

        $heading = get_string('deadlines', 'theme_snap');
        if ($this->advanced_feeds_enabled()) {
            $virtualpaging = true; // Web service retrieves all elements, need to do virtual paging.
            $o = ce_render_helper::get_instance()->render_feed_web_component('deadlines', $heading,
                get_string('nodeadlines', 'theme_snap'), $virtualpaging);
        } else {
            $o = "<h2>$heading</h2>";
            $o .= '<div id="snap-personal-menu-deadlines"></div>';
        }

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
        $loginurl = $CFG->wwwroot.'/login/index.php';
        $loginatts = [
            'class' => 'btn btn-primary snap-login-button',
            'role' => 'button',
        ];

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
    public function render_login_base_method() {
        global $CFG;
        // Return login form.
        if (empty($CFG->loginhttps)) {
            $wwwroot = $CFG->wwwroot;
        } else {
            $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
        }

        $action = s($wwwroot).'/login/index.php';

        $logintoken = is_callable(['\\core\\session\\manager', 'get_login_token']) ?
            \core\session\manager::get_login_token() : '';

        $data = (object) [
            'action' => $action,
            'logintoken' => $logintoken,
        ];
        return $this->render_from_template('theme_snap/login_base_methods', $data);
    }

    /**
     * Personal menu or authenticate form.
     */
    public function personal_menu() {
        global $USER, $CFG;

        if (!isloggedin() || isguestuser()) {
            return '';
        }

        // User image.
        $userpicture = new user_picture($USER);
        $userpicture->link = false;
        $userpicture->alttext = false;
        $userpicture->size = 90;
        $picture = $this->render($userpicture);

        // User name and link to profile.
        // To the DOM structure, only one H1 can exists in it, so this link
        // can not act as a header, so no role="heading" attribute can be
        // assigned to it.
        $fullnamelink = '<a href="' .s($CFG->wwwroot). '/user/profile.php"
                    title="' .s(get_string('viewyourprofile', 'theme_snap')). '"
                    class="h1" id="snap-pm-user-profile">'
                    .format_string(fullname($USER)). '</a>';

        // Real user when logged in as.
        $realfullnamelink = '';
        if (\core\session\manager::is_loggedinas()) {
            $realuser = \core\session\manager::get_realuser();
            $realfullnamelink = '<br>' .get_string('via', 'theme_snap'). ' ' .format_string(fullname($realuser, true));
        }

        // User quicklinks.
        // We need to access the User id page.
        $userid = $USER->id;
        $profilelink = [
            'id' => 'snap-pm-profile',
            'link' => s($CFG->wwwroot). '/user/profile.php?id=' .$userid,
            'title' => get_string('profile')
        ];
        $quicklinks = [$profilelink];
        // We need to verify the existence of My Account plugin in the code base to display this.
        if ((has_capability('moodle/site:config', context_system::instance())) &&
            (\core_component::get_component_directory('local_myaccount') !== null) &&
            is_callable('mr_on') &&
            mr_on("myaccount", "_MR_LOCAL")) {
            $myaccountlink = [
                'id' => 'snap-pm-myaccount',
                'link' => s($CFG->wwwroot) . '/local/myaccount/view.php?controller=default&action=view',
                'title' => get_string('myaccount', 'local_myaccount')
            ];
            $quicklinks[] = $myaccountlink;
        }
        $dashboardlink = [
            'id' => 'snap-pm-dashboard',
            'link' => s($CFG->wwwroot). '/my',
            'title' => get_string('myhome')
        ];
        $quicklinks[] = $dashboardlink;
        $gradelink = [
            'id' => 'snap-pm-grades',
            'link' => s($CFG->wwwroot). '/grade/report/overview/index.php',
            'title' => get_string('grades')
        ];
        $quicklinks[] = $gradelink;
        $preferenceslink = [
            'id' => 'snap-pm-preferences',
            'link' => s($CFG->wwwroot). '/user/preferences.php',
            'title' => get_string('preferences')
        ];
        $quicklinks[] = $preferenceslink;
        $logoutlink = [
            'id' => 'snap-pm-logout',
            'link' => s($CFG->wwwroot).'/login/logout.php?sesskey='.sesskey(),
            'title' => get_string('logout')
        ];

        if (is_callable('mr_on') && mr_on('catalogue', '_MR_LOCAL')) {
            $coursecataloguelink = [
                'id' => 'snap-pm-course-catalogue',
                'link' => s($CFG->wwwroot) . '/local/catalogue/index.php',
                'title' => get_string('pluginname', 'local_catalogue')
            ];
            $quicklinks[] = $coursecataloguelink;
        }
        if (is_callable('mr_on') && mr_on('programs', '_MR_ENROL')) {
            $programcataloguelink = [
                'id' => 'snap-pm-program-catalogue',
                'link' => s($CFG->wwwroot) . '/enrol/programs/catalogue/index.php',
                'title' => get_string('catalogue', 'enrol_programs')
            ];
            $quicklinks[] = $programcataloguelink;
        }
        if (is_callable('mr_on') && mr_on('myprograms', '_MR_BLOCKS')) {
            $myprogramslink = [
                'id' => 'snap-pm-my-programs',
                'link' => s($CFG->wwwroot) . '/enrol/programs/my/index.php',
                'title' => get_string('pluginname', 'block_myprograms')
            ];
            $quicklinks[] = $myprogramslink;
        }

        $courseid = $this->page->course->id;
        $coursecontext = context_course::instance($courseid);
        if (has_capability('moodle/role:switchroles', $coursecontext) || is_role_switched($courseid)) {
            $returnurl = $this->page->url->out_as_local_url(false);
            if (!is_role_switched($courseid)) {
                $link = new moodle_url('/course/switchrole.php', array(
                    'id' => $courseid,
                    'sesskey' => sesskey(),
                    'switchrole' => -1,
                    'returnurl' => $returnurl
                ));
                $switchrole = [
                    'id' => 'snap-pm-switchroleto',
                    'link' => $link->out(false),
                    'title' => get_string('switchroleto')
                ];
            } else {
                $link = new moodle_url('/course/switchrole.php', array(
                    'id' => $courseid,
                    'sesskey' => sesskey(),
                    'switchrole' => 0,
                    'returnurl' => $returnurl
                ));
                $switchrole = [
                    'id' => 'snap-pm-switchrolereturn',
                    'link' => $link->out(false),
                    'title' => get_string('switchrolereturn')
                ];
            }
            $quicklinks[] = $switchrole;
        }
        $quicklinks[] = $logoutlink;

        // Build up courses.
        $courseservice = course::service();
        [$pastcourses, $favorited, $notfavorited] = $courseservice->my_courses_split_by_favorites();
        // If we have past course, the template needs a variable.
        $coursenav = !empty($pastcourses);

        // Current courses data.
        // Note, we have to do this before we build up past or hidden courses so that the first 12 card images viewed
        // are loaded immediately - see course_card.php renderable and static $count.
        $currentcourses = $favorited + $notfavorited;
        $published = []; // Published course & favorites when user visible.
        $hidden = []; // Hidden courses.
        foreach ($currentcourses as $course) {
            $ccard = new course_card($course);
            if (isset($favorited[$course->id]) || $course->visible) {
                $published[] = $ccard;
            }
        }
        foreach ($currentcourses as $course) {
            $ccard = new course_card($course);
            if (!isset($favorited[$course->id]) && !$course->visible) {
                $hidden[] = $ccard;
            }
        }

        $currentcourses = [];
        if ($published) {
            $currentcourses = [
                'count' => count($published),
                'courses' => $published
            ];
        }

        $hiddencourses = [];
        if ($hidden) {
            $hiddencourses = [
                'count' => count($hidden),
                'courses' => $hidden
            ];
        }

        // Past courses data.
        $pastcourselist = [];
        foreach ($pastcourses as $yearcourses) {
            // A courses array for each year.
            $courses = [];
            // Add course cards to each year.
            foreach ($yearcourses as $course) {
                $ccard = new course_card($course);
                $ccard->archived = true;
                $courses[] = $ccard;
            }
            $endyear = array_values($yearcourses)[0]->endyear;
            $year = (object) [
                 'year' => $endyear,
                 'courses' => $courses
            ];
            // Append each year object.
            $pastcourselist[] = $year;
        }

        // When there are no currentcourses we set hiddencourses as the main list.
        if (!$currentcourses) {
            $currentcourses = $hiddencourses;
            $hiddencourses = '';
        }

        // We can only populate the currentcourselist if there is either currentcourses or hiddencourses available.
        // This is so the template will correctly show the coursefixydefaulttext when the user is not enrolled on any
        // visible or hidden courses.
        $currentcourselist = [];
        if (!empty($currentcourses) || !empty($hiddencourses)) {
            $currentcourselist = [
                'hidden' => $hiddencourses,
                'published' => $currentcourses
            ];
        }

        $browseallcourses = '';
        if (!empty($CFG->navshowallcourses) || has_capability('moodle/site:config', context_system::instance())) {
            $url = new moodle_url('/course/');
            $browseallcourses = $this->column_header_icon_link('browseallcourses', 'courses', $url);
        }

        $maxcourses = !empty($CFG->theme_snap_bar_limit) ?
            $CFG->theme_snap_bar_limit : local::DEFAULT_COMPLETION_COURSE_LIMIT;
        $lowlimit = $maxcourses - 5;
        $courselimitclass = false;
        if (!empty($currentcourselist['published']['count'])) {
            $coursescount = $currentcourselist['published']['count'];
        } else {
            $coursescount = 0;
        }

        if ($coursescount > $maxcourses) {
            $courselimitclass = 'danger';
            $warningstring = get_string('courselimitstrdanger', 'theme_snap');
        } else if ($coursescount >= $lowlimit && $coursescount <= $maxcourses) {
            $courselimitclass = 'warning';
            $warningstring = get_string('courselimitstrwarning', 'theme_snap', $maxcourses);
        }

        $data = (object) [
            'userpicture' => $picture,
            'fullnamelink' => $fullnamelink,
            'realfullnamelink' => $realfullnamelink,
            'quicklinks' => $quicklinks,
            'coursenav' => $coursenav,
            'currentcourselist' => $currentcourselist,
            'pastcourselist' => $pastcourselist,
            'browseallcourses' => $browseallcourses,
            'updates' => $this->render_callstoaction(),
            'advancedfeeds' => $this->advanced_feeds_enabled()
        ];

        if ($courselimitclass) {
            $data->courselimitclass = $courselimitclass;
            $data->courselimitstr = $warningstring;
        }

        return $this->render_from_template('theme_snap/personal_menu', $data);
    }

    /**
     * Personal menu trigger - a login link or my courses link.
     *
     */
    public function personal_menu_trigger() {
        global $USER;
        $output = '';
        if (!isloggedin() || isguestuser()) {
            if (local::current_url_path() != '/login/index.php') {
                $output .= $this->login_button();
            }
        } else {
            $userpicture = new user_picture($USER);
            $userpicture->link = false;
            $userpicture->alttext = false;
            $userpicture->size = 40;
            $picture = $this->render($userpicture);

            $menu = '<span class="hidden-xs-down">' .get_string('menu', 'theme_snap'). '</span>';
            $linkcontent = $picture.$menu;
            $attributes = array(
                'aria-haspopup' => 'true',
                'class' => 'js-snap-pm-trigger snap-my-courses-menu',
                'id' => 'snap-pm-trigger',
                'aria-controls' => 'snap-pm',
            );
            $output .= html_writer::link('#', $linkcontent, $attributes);
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

    /**
     * Cover image selector.
     * @return bool|null|string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function cover_image_selector() {
        if (has_capability('moodle/course:changesummary', $this->page->context)) {
            $vars = ['accepttypes' => local::supported_coverimage_typesstr()];
            return $this->render_from_template('theme_snap/cover_image_selector', $vars);
        }
        return null;
    }

    /**
     * Cover carousel.
     * @return string
     */
    public function cover_carousel() {
        if (empty($this->page->theme->settings->cover_carousel)) {
            return '';
        }

        $slidenames = array("slide_one", "slide_two", "slide_three");
        $slides = array();
        $i = 0;
        foreach ($slidenames as $slidename) {
            $image = $slidename . '_image';
            $title = $slidename . '_title';
            $subtitle = $slidename . '_subtitle';
            if (!empty($this->page->theme->settings->$image) && !empty($this->page->theme->settings->$title)) {
                $slide = (object) [
                    'index' => $i++,
                    'active' => '',
                    'name' => $slidename,
                    'image' => $this->page->theme->setting_file_url($image, $image),
                    'title' => $this->page->theme->settings->$title,
                    'subtitle' => $this->page->theme->settings->$subtitle
                ];
                $slides[] = $slide;
            }
        }
        $carouselhidecontrols = '';
        $carouselindicatorsbtn = count($slides);
        if ($carouselindicatorsbtn < 2) {
            // Add a class to hide the control buttons when only exists one slide,
            // with two or three the play and pause buttons will be displayed.
            $carouselhidecontrols = 'carouselhidecontrols';
        }
        if (empty($slides)) {
            return '';
        }
        $slides[0]->active = 'active';
        $carouselsronlytext = get_string('covercarouselsronly', 'theme_snap');
        $carouselplaybutton = get_string('covercarouselplaybutton', 'theme_snap');
        $carouselpausebutton = get_string('covercarouselpausebutton', 'theme_snap');
        $covercarousellabel = get_string('covercarousellabel', 'theme_snap');
        $data = ['carouselsronlytext' => $carouselsronlytext,
                'carouselplaybutton' => $carouselplaybutton,
                'carouselpausebutton' => $carouselpausebutton,
                'covercarousellabel' => $covercarousellabel,
                'carouselhidecontrols' => $carouselhidecontrols];
        $data['slides'] = $slides;
        return $this->render_from_template('theme_snap/carousel', $data);
    }

    /**
     * Login background slide images.
     * @return array
     */
    public function login_bg_slides() {
        if (empty($this->page->theme->settings->loginbgimg)) {
            return '';
        }
        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'theme_snap', 'loginbgimg');
        $images = [];

        foreach ($files as $file) {
            if ($file->get_filename() != '.') {
                $images[] = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                    $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(),
                    false)->out(false);
            }
        }
        return $images;
    }

    public function login_carousel_first() {
        if (empty($this->page->theme->settings->loginbgimg)) {
            return '';
        }
        $fs = get_file_storage();
        $files = $fs->get_area_files(\context_system::instance()->id, 'theme_snap', 'loginbgimg');
        foreach ($files as $file) {
            if ($file->get_filename() != '.') {
                return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false)->out(false);
            }
        }
        return ''; // Empty return to avoid errors.
    }


    /**
     * Get page heading.
     *
     * @param string $tag
     * @return string
     */
    public function page_heading($tag = 'h1') {
        global $COURSE;

        $heading = $this->page->heading;

        if ($this->page->pagelayout == 'mypublic' && $COURSE->id == SITEID) {
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
            $heading = format_string($COURSE->fullname);
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
            $url = new moodle_url('/admin/settings.php', ['section' => 'themesettingsnap']);
            $link = html_writer::link($url,
                            get_string('changefullname', 'theme_snap'),
                            ['class' => 'btn btn-secondary btn-sm']);
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
     * Renders custom menu as a navigation bar.
     *
     * @return string
     */
    protected function render_custom_menu(\custom_menu $menu) {
        if (!$menu->has_children()) {
            return '';
        }

        // We need to create this part of HTML here or multiple nav tags will exist for each item.
        $content = '<nav class="navbar navbar-expand-lg navbar-light">';
        $content .= '<ul class="navbar-collapse clearfix snap-navbar-content">';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('theme_snap/custom_menu_item', $context);
        }

        return $content.'</nav>'.'</ul>';
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
            throw new moodle_exception('cannotfindorcreateforum', 'forum');
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

        $counter = 0;
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
            $date    = userdate($discussion->modified, get_string('strftimedatetime', 'langconfig'));

            $message = format_text($message, $discussion->messageformat, ['context' => $context]);

            $readmorebtn = "<a tabindex='0' role='button' aria-expanded='false' aria-controls='news-article-message-id-{$counter}'
             class='btn btn-secondary toggle' href='".$CFG->wwwroot."/mod/forum/discuss.php?d=".$discussion->discussion."'>".
                get_string('readmore', 'theme_snap')."</a>";

            $preview = '';
            $newsimage = '';
            if (!$imagestyle) {
                $preview = html_to_text($message, 0, false);
                $preview = "<div class='news-article-preview'><p>".shorten_text($preview, 200)."</p>
                <p class='text-right'>".$readmorebtn."</p></div>";
            } else {
                $newsimage = "<div class='news-article-image toggle' tabindex='0' role='button'
                aria-expanded='false' aria-controls='news-article-message-id-{$counter}'".$imagestyle.' title="'.
                    get_string('readmore', 'theme_snap').'"></div>';
            }
            $close = get_string('closebuttontitle', 'moodle');

            $newsinner = <<<HTML
    <div class="news-article-inner">
        <div class="news-article-content">
            <h3 class='toggle' aria-expanded="false" aria-controls="news-article-message-id-{$counter}">
                <a role="button" tabindex='0' href="$CFG->wwwroot/mod/forum/discuss.php?d=$discussion->discussion">{$name}</a>
            </h3>
            <em class="news-article-date">{$date}</em>
        </div>
    </div>
HTML;

            if ($counter % 2 === 0) {
                $newsordered = $newsinner . $preview . $newsimage;
            } else {
                $newsordered = $newsimage . $preview . $newsinner;
            }

            $arialabelnews = get_string('arialabelnewsarticle', 'theme_snap');

            $output .= <<<HTML
<div class="news-article clearfix" role="group" tabindex="0" aria-label="$arialabelnews">
    {$newsordered}
    <div id="news-article-message-id-{$counter}" class="news-article-message" tabindex="-1">
        {$message}
        <div><hr><a role="button" tabindex='0' aria-expanded="false" aria-controls="news-article-message-id-{$counter}"
            class="snap-action-icon snap-icon-close toggle" href="#">
        <small>{$close}</small></a></div>
    </div>
</div>
HTML;
            $counter++;
        }
        $actionlinks = html_writer::link(
            new moodle_url('/mod/forum/view.php', array('id' => $cm->id)),
            get_string('morenews', 'theme_snap'),
            ['class' => 'btn btn-secondary',
             'role' => 'button',
             'tabindex' => 0]
        );
        if (forum_user_can_post_discussion($forum, $currentgroup, $groupmode, $cm, $context)) {
            $actionlinks .= html_writer::link(
                new moodle_url('/mod/forum/post.php', array('forum' => $forum->id)),
                get_string('addanewtopic', 'forum'),
                ['class' => 'btn btn-primary',
                    'role' => 'button',
                    'tabindex' => 0]
            );
        }
        $output .= html_writer::end_div();
        $output .= "<br><div class='text-center'>$actionlinks</div>";
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * add in additional classes that are used for Snap
     * get rid of YUI stuff so we can style it with bootstrap
     *
     * @param array $additionalclasses
     * @return array|string
     */
    public function body_css_classes(array $additionalclasses = array()) {
        global $COURSE, $SESSION, $CFG, $USER;

        $classes = parent::body_css_classes($additionalclasses);
        $classes = explode (' ', $classes);

        $classes[] = 'device-type-'.$this->page->devicetypeinuse;

        $forcepasschange = get_user_preferences('auth_forcepasswordchange');
        if (isset($SESSION->justloggedin) && empty($forcepasschange)) {
            $openfixyafterlogin = !empty($this->page->theme->settings->personalmenulogintoggle);
            $onfrontpage = ($this->page->pagetype === 'site-index');
            $onuserdashboard = ($this->page->pagetype === 'my-index');
            if ($openfixyafterlogin && !isguestuser() && ($onfrontpage || $onuserdashboard)) {
                $classes[] = 'snap-pm-open';
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
            'admin-setting-modsettinglti',
            'blocks-campusvue-view',
            'enrol-instances',
            'admin-report-eventlist-index',
        );
        if (in_array($this->page->pagetype, $killyuipages)) {
            $classes = array_diff ($classes, ['yui-skin-sam', 'yui3-skin-sam']);
            $classes[] = 'yui-bootstrapped';
        }

        if (!empty($this->page->url)) {
            $section = $this->page->url->param('section');
        }

        // Add completion tracking class.
        if (!empty($COURSE->enablecompletion)) {
            $classes[] = 'completion-tracking';
        }

        // Add resource display class.
        if (!empty($this->page->theme->settings->resourcedisplay)) {
            $classes[] = 'snap-resource-'.$this->page->theme->settings->resourcedisplay;
        } else {
            $classes[] = 'snap-resource-card';
        }

        // Add theme-snap class so modules can customise css for snap.
        $classes[] = 'theme-snap';

        if (get_config('theme_snap', 'coursepartialrender') && get_config('theme_snap', 'leftnav') == 'top'
            && $COURSE->format == 'topics') {
            $classes[] = 'no-number-toc';
        }

        if (!empty($CFG->allowcategorythemes)) {
            // This duplicates code triggered by allowcategorythemes, so no
            // need to repeat it if that setting is on.
            $catids = array_keys($this->page->categories);
            // Immediate parent category is always output by core code.
            array_shift($catids);
            foreach ($catids as $catid) {
                $classes[] = 'category-' . $catid;
            }
            // Put class category-x on body when loading editcategory page on course.
            // Categories and parent categories are added in ascendant order.
            if (strpos($this->page->url->get_path(), "course/editcategory.php") !== false
                && $this->page->url->get_param('id') !== null) {
                $parentcategories = self::get_parentcategories($this->page->url->get_param('id'));
                foreach ($parentcategories as $category) {
                    $classes[] = 'category-' . $category;
                }
            }

            // Put class category-x on body when loading add new course page.
            // Categories and parent categories are added in ascendant order.
            if (strpos($this->page->url->get_path(), "course/edit.php") !== false
                && $this->page->url->get_param('category') !== null) {
                $parentcategories = self::get_parentcategories($this->page->url->get_param('category'));
                foreach ($parentcategories as $category) {
                    $classes[] = 'category-' . $category;
                }
            }
        }

        // Add page layout.
        $classes[] = 'layout-'.$this->page->pagelayout;

        // Profile based branding.
        $pbbclass = local::get_profile_based_branding_class($USER);
        if (!empty($pbbclass)) {
            $classes[] = $pbbclass;
        }

        // Remove duplicates if necessary.
        $classes = array_unique($classes);

        $classes = implode(' ', $classes);
        return $classes;
    }

    /**
     * Returns all parent categories hierarchy from a category id
     * @param int $id
     * @return array
     * @throws \moodle_exception
     */
    private function get_parentcategories($id) {
        global $DB;
        if ($id == 0) {
            return [];
        }
        $category = $DB->get_record('course_categories', array('id' => $id));
        if (!$category) {
            throw new \moodle_exception('unknowncategory');
        }
        $parentcategoryids = explode('/', trim($category->path, '/'));
        return $parentcategoryids;
    }

    /**
     * Override to add a class to differentiate from other
     * #notice.box.generalbox that have buttons after them,
     * rather than inside them.
     */
    public function confirm($message, $continue, $cancel, array $displayoptions = []) {
        // We need plain styling of confirm boxes on upgrade because we don't know which stylesheet we have (it could be
        // from any previous version of Moodle).
        if ($continue instanceof single_button) {
            $continue->primary = true;
        } else if (is_string($continue)) {
            $continue = new single_button(new moodle_url($continue), get_string('continue'), 'post', true);
        } else if ($continue instanceof moodle_url) {
            $continue = new \single_button($continue, get_string('continue'), 'post', true);
        } else {
            throw new coding_exception(
                'The continue param to $OUTPUT->confirm() must be either a URL (string/moodle_url) '
                . 'or a single_button instance.'
            );
        }

        if ($cancel instanceof single_button) {
            $output = '';
        } else if (is_string($cancel)) {
            $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new \single_button($cancel, get_string('cancel'), 'get');
        } else {
            throw new coding_exception(
                'The cancel param to $OUTPUT->confirm() must be either a URL (string/moodle_url) '
                . 'or a single_button instance.'
            );
        }

        $output = $this->box_start('generalbox snap-continue-cancel', 'notice');
        $output .= html_writer::tag('h4', get_string('confirm'));
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), array('class' => 'buttons'));
        $output .= $this->box_end();
        return $output;
    }

    public function image_url($imagename, $component = 'moodle') {
        // Strip -24, -64, -256  etc from the end of filetype icons so we
        // only need to provide one SVG, see MDL-47082.
        $imagename = \preg_replace('/-\d\d\d?$/', '', $imagename);
        return $this->page->theme->image_url($imagename, $component);
    }

    /**
     * Return feature spot cards html.
     *
     * @return string
     */
    public function feature_spot_cards() {
        $fsnames = array("fs_one", "fs_two", "fs_three");
        $features = array();
        // Note - we are using underscores in the settings to make easier to read.

        foreach ($fsnames as $feature) {
            $title = $feature . '_title';
            $link = $feature . '_title_link';
            $cbopeninnewtab = $feature . '_title_link_cb';
            $text = $feature . '_text';
            $image = $feature . '_image';
            if (!empty($this->page->theme->settings->$title)) {
                $img = '';
                if (!empty($this->page->theme->settings->$image)) {
                    $url = $this->page->theme->setting_file_url($image, $image);
                    $img = '<!--Card image-->
                    <div class="snap-feature-image-wrap">
                        <img class="snap-feature-image" src="' .$url. '" alt="" role="presentation">
                    </div>';
                }
                $features[] = $this->feature_spot_card($this->page->theme->settings->$title,
                    $this->page->theme->settings->$link,
                    $this->page->theme->settings->$cbopeninnewtab,
                    $img,
                    $this->page->theme->settings->$text);
            }

        }

        $fscount = count($features);
        if ($fscount > 0) {
            $fstitle = '';
            if (!empty($this->page->theme->settings->fs_heading)) {
                $fstitle = '<h2 class="snap-feature-spots-heading">' .s($this->page->theme->settings->fs_heading). '</h2>';
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
                $url = new moodle_url('/admin/settings.php?section=themesettingsnap#themesnapfeaturespots');
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
     * @param string $link
     * @param string $cbopeninnewtab
     * @param string $image
     * @param string $text
     * @return string
     */
    protected function feature_spot_card($title, $link, $cbopeninnewtab, $image, $text) {

        $target = '';

        if ($cbopeninnewtab) {
            $target = "target='_blank'";
        }

        // Title with link.
        $linktitle = '<a ' .$target. ' class="snap-feature-link h5" href="' .s($link). '">' .s($title). '</a>';
        // Title without link.
        $nolinktitle = '<h3 class="snap-feature-title h5">' .s($title). '</h3>';
        // Content text for feature spots.
        $fscontenttext =
            '<p class="snap-feature-text">' . format_text($text, FORMAT_MOODLE, ['para' => false]) . '</p>';

        if ($link) {
            $card = '<div class="snap-feature">
                        <div class="snap-feature-block">' .$image.$linktitle.$fscontenttext. '</div>
                    </div>';
        } else {
            $card = '<div class="snap-feature">
                        <div class="snap-feature-block">' .$image.$nolinktitle.$fscontenttext. '</div>
                    </div>';
        }

        return $card;
    }

    /**
     * Return featured courses html.
     * There are intentionally no checks for hidden course status
     * OR current users enrolment status.
     *
     * @return string
     */
    public function render_featured_courses(featured_courses $fc) {
        if (empty($fc->cards)) {
            return '';
        }

        return $this->render_from_template('theme_snap/featured_courses', $fc);
    }

    /**
     * Return snap modchooser modal.
     * @return string
     */
    protected function course_modchooser() {
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $COURSE, $OUTPUT, $USER;
        // Check to see if user can add menus and there are modules to add.
        if (!has_capability('moodle/course:manageactivities', context_course::instance($COURSE->id))
                || !($modnames = get_module_types_names()) || empty($modnames)) {
            return '';
        }
        // Retrieve all modules with associated metadata.
        $sectionreturn = null;

        foreach ($modnames as $module => $name) {
            if (is_callable('mr_off') && mr_off($module, '_MR_MODULES')) {
                unset($modnames[$module]);
            }
        }
        $contentitemservice = new \core_course\local\service\content_item_service(
            new \core_course\local\repository\content_item_readonly_repository()
        );
        $contentitems = $contentitemservice->get_content_items_for_user_in_course($USER, $COURSE);
        $resources = [];
        foreach ($contentitems as $mod) {
            $help = !empty($mod->help) ? $mod->help : '';
            $helptext = format_text($help, FORMAT_MARKDOWN);

            if ($mod->archetype === MOD_ARCHETYPE_RESOURCE) {
                $resources[] = (object) [
                    'name' => $mod->name,
                    'title' => $mod->title,
                    'icon' => ''.$OUTPUT->image_url('icon', $mod->name),
                    'link' => $mod->link .'&section=0', // Section is replaced by js.
                    'help' => $helptext
                ];
            } else if ($mod->archetype !== MOD_ARCHETYPE_SYSTEM) {
                // The name should be 'lti' instead of the module's URL which is the one we're getting.
                $imageurl = $OUTPUT->image_url('icon', $mod->name);
                if (strpos($mod->name, 'lti:') !== false) {
                    $imageurl = $OUTPUT->image_url('icon', 'lti');
                    if (preg_match('/src="([^"]*)"/i', $mod->icon, $matches)) {
                        $imageurl = $matches[1]; // Use the custom icon.
                    }
                }
                $activities[] = (object) [
                    'name' => $mod->name,
                    'title' => $mod->title,
                    'icon' => ''.$imageurl,
                    'link' => $mod->link .'&section=0', // Section is replaced by js.
                    'help' => $helptext
                ];
            }
        }

        return $this->course_activitychooser($COURSE->id);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Build the HTML for the module chooser javascript popup.
     *
     * @param int $courseid The course id to fetch modules for.
     * @return string
     */
    public function course_activitychooser($courseid) {

        if (!$this->page->requires->should_create_one_time_item_now('core_course_modchooser')) {
            return '';
        }

        // Build an object of config settings that we can then hook into in the Activity Chooser.
        $chooserconfig = (object) [
            'tabmode' => get_config('core', 'activitychoosertabmode'),
        ];
        $this->page->requires->js_call_amd('core_course/activitychooser', 'init', [$courseid, $chooserconfig]);

        return '';
    }

    /**
     * Only for Unit testing purposes.
     */
    public function testhelper_course_modchooser() {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            return $this->course_modchooser();
        }
    }

    /**
     * Override parent function so that all courses (except the front page) skip the 'turn editing on' button.
     */
    protected function render_navigation_node(navigation_node $item) {
        global $COURSE, $SITE;

        if ($item->action instanceof moodle_url) {
            // Hide the course 'turn editing on' link.
            $iscoursepath = $item->action->get_path() === '/course/view.php';
            $iseditlink = $item->action->get_param('edit') === 'on';
            $isfrontpage = $item->action->get_param('id') === SITEID;
            if ($iscoursepath && $iseditlink && !$isfrontpage) {
                return '';
            }

            // Remove links for Reports and Question Bank for INT-18042.
            $isreportslink = $item->key === 'coursereports';
            if ($isreportslink) {
                $item->action = null;
            }

        }

        // Bank content link where necessary (Front page - Course page - Category settings).
        $context = context_system::instance();

        $coursecatcontext = $this->page->context->contextlevel === CONTEXT_COURSECAT;

        if ($COURSE->id !== $SITE->id) {
            $context = context_course::instance($COURSE->id);
        }
        if ($coursecatcontext) {
            $context = $this->page->context;
        }

        if (has_capability('moodle/contentbank:access', $context)) {
            if (!in_array('contentbank', $item->get_children_key_list(), true) &&
                ($item->key === 'frontpage' || $item->key === 'courseadmin' || $item->key === 'categorysettings')) {
                $this->add_contentbank_navigation_node($item, $context->id);
            }
        }

        if ($item->key === 'courseadmin') {
            $this->add_switchroleto_navigation_node($item);
        }

        $content = parent::render_navigation_node($item);
        if (strpos($content, 'fa-fw fa-fw')) {
            $content = str_replace('fa-fw fa-fw', 'fa-fw nav-missing-icon', $content);
        }
        return $content;
    }

    /**
     * Adds a content bank link to a navigation node.
     *
     * @param navigation_node $item
     * @param int $contextid
     */
    private function add_contentbank_navigation_node(navigation_node $item, $contextid) {
        $url = new moodle_url('/contentbank/index.php', array('contextid' => $contextid));
        $item->add(get_string('contentbank'), $url, navigation_node::TYPE_CUSTOM, null, 'contentbank', new \pix_icon('brush', ''));
    }

    /**
     * Adds a switch role menu to a navigation node.
     * Inspiration taken from : lib/navigationlib.php
     * https://github.com/moodle/moodle/commit/70b03eff02a261b16130c52aca5cd87ebd810b5e
     *
     * @param navigation_node $item
     */
    private function add_switchroleto_navigation_node(navigation_node $item) {
        $course = $this->page->course;
        $coursecontext = context_course::instance($course->id);
        // Switch roles.
        $roles = array();
        $assumedrole = $this->in_alternative_role();
        if ($assumedrole !== false) {
            $roles[0] = get_string('switchrolereturn');
        }

        if (has_capability('moodle/role:switchroles', $coursecontext)) {
            $availableroles = get_switchable_roles($coursecontext);
            if (is_array($availableroles)) {
                foreach ($availableroles as $key => $role) {
                    if ($assumedrole == (int)$key) {
                        continue;
                    }
                    $roles[$key] = $role;
                }
            }
        }
        if (is_array($roles) && count($roles) > 0) {
            $switchroles = $item->add(get_string('switchroleto'), null, navigation_node::TYPE_CONTAINER, null, 'switchroleto');
            if ((count($roles) == 1 && array_key_exists(0, $roles)) || $assumedrole !== false) {
                $switchroles->force_open();
            }
            foreach ($roles as $key => $name) {
                $url = new moodle_url('/course/switchrole.php', array(
                    'id' => $course->id, 'sesskey' => sesskey(),
                    'switchrole' => $key, 'returnurl' => $this->page->url->out_as_local_url(false)));
                $switchroles->add($name, $url, navigation_node::TYPE_SETTING, null, $key, new \pix_icon('i/switchrole', ''));
            }
        }
    }

    /**
     * Determine whether the user is assuming another role
     * Inspiration taken from : lib/navigationlib.php
     * https://github.com/moodle/moodle/commit/70b03eff02a261b16130c52aca5cd87ebd810b5e
     *
     * This function checks to see if the user is assuming another role by means of
     * role switching. In doing this we compare each RSW key (context path) against
     * the current context path. This ensures that we can provide the switching
     * options against both the course and any page shown under the course.
     *
     * @return bool|int The role(int) if the user is in another role, false otherwise
     */
    public function in_alternative_role() {
        global $USER;

        $course = $this->page->course;
        $coursecontext = context_course::instance($course->id);

        if (!empty($USER->access['rsw']) && is_array($USER->access['rsw'])) {
            if (!empty($this->page->context) && !empty($USER->access['rsw'][$this->page->context->path])) {
                return $USER->access['rsw'][$this->page->context->path];
            }
            foreach ($USER->access['rsw'] as $key => $role) {
                if (strpos($coursecontext->path, $key) === 0) {
                    return $role;
                }
            }
        }
        return false;
    }

    /**
     * Return Snap's logo url for login.mustache
     *
     * @param int $maxwidth not used in Snap.
     * @param int $maxheight not used in Snap.
     * @return moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        global $CFG;
        if (empty($this->page->theme->settings->logo)) {
            return false;
        }

        // Following code copied from  theme->setting_file_url but without the
        // bit that strips the protocol from the url.

        $itemid = theme_get_revision();
        $filepath = $this->page->theme->settings->logo;
        $syscontextid = context_system::instance()->id;

        $url = moodle_url::make_file_url("$CFG->httpswwwroot/pluginfile.php", "/$syscontextid/theme_snap/logo/$itemid".$filepath);
        return $url;
    }

    /**
     * Render intelliboard links in personal menu.
     * @return string
     */
    protected function render_intelliboard() {
        $o = '';
        $links = '';

        // Bail if no intelliboard.
        if (!get_config('local_intelliboard')) {
            return $o;
        }

        // Intelliboard adds links to the flatnav we use to check wich links to output.
        $nav = $this->page->navigation->find('myprofile', navigation_node::TYPE_ROOTNODE);
        $navlist = $nav->get_children_key_list();
        // Student dashboard link.
        if (in_array("intelliboard_student", $navlist, true)) {
            $node = $nav->get("intelliboard_student");
            $links .= $this->render_intelliboard_link($node->get_content(), $node->action(), 'intelliboard_learner');
        }

        // Instructor dashboard link.
        if (in_array("intelliboard_instructor", $navlist, true)) {
            $node = $nav->get("intelliboard_instructor");
            $links .= $this->render_intelliboard_link($node->get_content(), $node->action(), 'intelliboard');
        }

        // Competency dashboard link.
        if (in_array("intelliboard_competency", $navlist, true)) {
            $node = $nav->get("intelliboard_competency");
            $links .= $this->render_intelliboard_link($node->get_content(), $node->action(), 'intelliboard_competencies');
        }

        // No links to display.
        if (!$links) {
            return $o;
        }

        $intelliboardheading = get_string('intelliboardroot', 'local_intelliboard');
        $o = '<h2>' .$intelliboardheading. '</h2>';
        $o .= '<div id="snap-personal-menu-intelliboard">'
                .$links.
                '</div>';

        return $o;
    }

    /**
     * Render intelliboard link in personal menu.
     * @param string $name of the link.
     * @param moodle_url $url of the link.
     * @param string $icon icon sufix.
     * @return string
     */
    public function render_intelliboard_link($name, $url, $icon) {
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $OUTPUT;
        $iconurl = $OUTPUT->image_url($icon, 'theme');
        $img = '<img class="svg-icon" role="presentation" src="'.$iconurl.'">';
        $o = '<a href=" '.$url.' ">'.$img.s($name).'</a><br>';
        return $o;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Renders a wrap of the boost core notification popup area, which includes messages and notification popups
     * @return string notification popup area.
     */
    protected function render_notification_popups() {
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $CFG, $OUTPUT;

        $navoutput = '';
        if (\core_component::get_component_directory('local_intellicart') !== null) {
            require_once(__DIR__ . '/../../../../local/intellicart/lib.php');
            $navoutput .= local_intellicart_render_navbar_output($OUTPUT);
        }
        // We only want the notifications bell, not the messages badge so temporarilly disable messaging to exclude it.
        $messagingenabled = $CFG->messaging;
        $CFG->messaging = false;
        $navoutput .= message_popup_render_navbar_output($OUTPUT);
        $CFG->messaging = $messagingenabled;
        if (empty($navoutput)) {
            return '';
        }
        return $navoutput;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Render intellicart link in personal menu.
     * @return string
     */
    protected function render_intellicart() {
        // @codingStandardsIgnoreStart
        // Core renderer has not $output attribute, but code checker requires it.
        global $OUTPUT;
        $o = '';
        $link = '';

        // Prevent if no intellicart.
        if (\core_component::get_component_directory('local_intellicart') === null) {
            return $o;
        }

        // Intellicart adds a link to the flatnav.
        $nav = $this->page->navigation->find('myprofile', navigation_node::TYPE_ROOTNODE);
        $navlist = $nav->get_children_key_list();

        // Student dashboard link.
        if (in_array("intellicart_dashboard", $navlist, true)) {
            $node = $nav->get("intellicart_dashboard");
            $iconurl = $OUTPUT->image_url('intelliboard', 'theme');
            $img = '<img class="svg-icon" role="presentation" src="'.s($iconurl).'">';
            $link .= '<a href=" '. $node->action() .' ">'.$img.s($node->get_content()).'</a><br>';
        }

        // No links to display.
        if (!$link) {
            return $o;
        }

        $intellicartheading = get_string('intellicart', 'local_intellicart');
        $o = '<h2>' .$intellicartheading. '</h2>';
        $o .= '<div id="snap-personal-menu-intellicart">'
            .$link.
            '</div>';

        return $o;
        // @codingStandardsIgnoreEnd
    }

    /**
     * This renders the navbar.
     * Uses bootstrap compatible html.
     * @param string $coverimage
     */
    public function snapnavbar($coverimage = '') {
        global $COURSE, $CFG;

        require_once($CFG->dirroot.'/course/lib.php');

        $breadcrumbs = '';
        $courseitem = null;
        $attrs['class'] = '';
        if (!empty($coverimage)) {
            $attrs['class'] .= ' mast-breadcrumb';
        }
        $snapmycourses = html_writer::link(new moodle_url('/my/courses.php'), get_string('menu', 'theme_snap'), $attrs);
        $filteredbreadcrumbs = $this->remove_duplicated_breadcrumbs($this->page->navbar->get_items());
        foreach ($filteredbreadcrumbs as $item) {
            $item->hideicon = true;

            // Add Breadcrumb links to all users types.
            if ($item->key === 'myhome') {
                $breadcrumbs .= '<li class="breadcrumb-item">';
                $breadcrumbs .= html_writer::link(new moodle_url('/my'), get_string($item->key), $attrs);
                $breadcrumbs .= '</li>';
                continue;
            }

            if ($item->key === 'home') {
                $breadcrumbs .= '<li class="breadcrumb-item">';
                $breadcrumbs .= html_writer::link(new moodle_url('/'), get_string($item->key), $attrs);
                $breadcrumbs .= '</li>';
                continue;
            }

            // Replace my courses none-link with link to snap personal menu.
            if ($item->key === 'mycourses') {
                $breadcrumbs .= '<li class="breadcrumb-item">' .$snapmycourses. '</li>';
                continue;
            }

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

            // Only output breadcrumb items which have links.
            if ($item->action !== null) {
                $attr = [];
                if (!empty($coverimage)) {
                    $attr = ['class' => 'mast-breadcrumb'];
                }
                if (!is_string($item->action) && !empty($item->action->url)) {
                    $link = html_writer::link($item->action->url, $item->text, $attr);
                } else {
                    $link = html_writer::link($item->action, $item->text, $attr);
                }
                $breadcrumbs .= '<li class="breadcrumb-item">' .$link. '</li>';
            }
        }

        if (!empty($breadcrumbs)) {
            return '<ol class="breadcrumb">' .$breadcrumbs .'</ol>';
        }
    }

    /**
     * Renders a div that is only shown when there are configured custom menu items.
     *
     * @return string
     */
    public function custom_menu_spacer() {
        global $CFG;
        $spacer = '';

        if (!empty($CFG->custommenuitems)) {
            $spacer  = '<div class="snap-custom-menu-spacer"></div>';

            // Style to fix the block settings menu when custom menu is active.
            $css = '#page-content .block_settings.state-visible div.card-body {margin-top: 3em;}';
            $css .= '#page-admin-purgecaches #notice, #notice.snap-continue-cancel {margin-top: 1.2em;}';

            $spacer .= "<style> {$css} </style>";
        }
        return $spacer;
    }

    public function secure_layout_language_menu() {
        if (get_config('core', 'langmenuinsecurelayout')) {
            return $this->lang_menu();
        } else {
            return '';
        }
    }

    /**
     * Wrapper for header elements and create the necessary elements for content bank in Snap.
     * Taken from core in lib/outputrenderers.php, full_header() function.
     *
     * @return string.
     */
    public function snap_content_bank() {
        $header = new stdClass();
        $header->headeractions = $this->page->get_header_actions();
        return $this->render_from_template('core/full_header', $header);
    }

    /**
     * Advanced course management options for My Courses page in Snap.
     *
     * @return string.
     */
    public function snap_my_courses_management_options() {

        $coursecat = core_course_category::user_top();
        $coursemanagemenu = [];
        if ($coursecat && ($category = core_course_category::get_nearest_editable_subcategory($coursecat, ['create']))) {
            // The user has the capability to create course.
            $coursemanagemenu['newcourseurl'] = new moodle_url('/course/edit.php', ['category' => $category->id]);
        }
        if ($coursecat && ($category = core_course_category::get_nearest_editable_subcategory($coursecat, ['manage']))) {
            // The user has the capability to manage the course category.
            $coursemanagemenu['manageurl'] = new moodle_url('/course/management.php', ['categoryid' => $category->id]);
        }
        if ($coursecat) {
            $category = core_course_category::get_nearest_editable_subcategory($coursecat, ['moodle/course:request']);
            if ($category && $category->can_request_course()) {
                $coursemanagemenu['courserequesturl'] = new moodle_url('/course/request.php', ['categoryid' => $category->id]);

            }
        }

        if (!empty($coursemanagemenu)) {
            // Render the course management menu.
            $dropdown = $this->render_from_template('my/dropdown', $coursemanagemenu);
            $coursemanageoptions = "<div class='snap-page-my-courses-options d-flex'>";
            $coursemanageoptions .= $dropdown;
            $coursemanageoptions .= "</div>";

            return $coursemanageoptions;
        }
    }

    /**
     * Gets the subdomain to use to link to the Open LMS site.
     * @return string
     */
    public function get_poweredby_subdomain() {
        // Currently supported subdomains.
        $subdomains = [
            'es' => 'es',
            'fr' => 'fr',
            'ja' => 'jp',
            'pt_br' => 'br',
        ];

        if (isset($subdomains[current_language()])) {
            return $subdomains[current_language()];
        }
        // Default subdomain.
        return 'www';
    }

    /**
     * @param $pathurl string.
     *
     * @return bool $path
     */
    public function get_path_hiddentoc($pathurl = false): bool {

        $path = false;
        $listhidden = $this->listhidden ?? [];

        if (!empty($pathurl)) {
            if (in_array($pathurl, $listhidden)) {
                $path = true;
            }
        }
        return $path;
    }

    /**
     * When there are two or more breadcrumbs with the same name, remove the others and just leave one.
     * @param $breadcrumbs array.
     * @return array
     */
    public function remove_duplicated_breadcrumbs($breadcrumbs): array {
        $breadcrumbskeys = array();
        $filtereditems = array_filter($breadcrumbs, function($item) use (&$breadcrumbskeys) {
            $text = $item->text instanceof lang_string ? $item->text->out() : $item->text;
            if (array_key_exists($text, $breadcrumbskeys)) {
                return false;
            }
            $breadcrumbskeys[$text] = $item->key;
            return true;
        });
        return $filtereditems;
    }

    /**
     * My Courses navigation link.
     *
     */
    public function my_courses_nav_link() {
        $output = '';
        if (!isloggedin() || isguestuser()) {
            return $output;
        }
        $menu = '<span class="hidden-xs-down">' .get_string('menu', 'theme_snap'). '</span>';
        $attributes = array(
            'aria-haspopup' => 'true',
            'class' => 'js-snap-pm-trigger snap-my-courses-menu snap-my-courses-link',
            'id' => 'snap-pm-trigger',
            'aria-controls' => 'snap-pm',
        );
        $output .= html_writer::link('#', $menu, $attributes);
        return $output;
    }

    /**
     * User menu navigation dropdown.
     *
     */
    public function user_menu_nav_dropdown() {
        $output = '';
        if (!isloggedin() || isguestuser()) {
            if (local::current_url_path() != '/login/index.php') {
                $output .= $this->login_button();
            }
        } else {
            $primary = new primary($this->page);
            $data = $primary->get_user_menu($this);
            $preferencesposition = array_search('preferences,moodle', array_column($data['items'], 'titleidentifier'));

            if ($preferencesposition) {
                $additionallinks = array();

                // My account link.
                if ((has_capability('moodle/site:config', context_system::instance())) &&
                    (\core_component::get_component_directory('local_myaccount') !== null) &&
                    is_callable('mr_on') &&
                    mr_on("myaccount", "_MR_LOCAL")) {
                    $myaccount = new stdClass();
                    $myaccount->itemtype = 'link';
                    $myaccount->url = new moodle_url('/local/myaccount/view.php?controller=default&action=view', array(
                        'id' => 'snap-pm-myaccount'
                    ));
                    $myaccount->link = $myaccount->itemtype == 'link';
                    $myaccount->title = get_string('myaccount', 'local_myaccount');
                    $myaccount->titleidentifier = 'myaccount,local_myaccount';
                    $additionallinks[] = $myaccount;
                }

                // Dashboard link.
                $dashboardlink = new stdClass();
                $dashboardlink->itemtype = 'link';
                $dashboardlink->url = new moodle_url('/my', array(
                    'id' => 'snap-pm-dashboard'
                ));
                $dashboardlink->link = $dashboardlink->itemtype == 'link';
                $dashboardlink->title = get_string('myhome');
                $dashboardlink->titleidentifier = 'myhome,moodle';
                $additionallinks[] = $dashboardlink;

                // Course catalogue link.
                if (is_callable('mr_on') && mr_on('catalogue', '_MR_LOCAL')) {
                    $localcatalogue = new stdClass();
                    $localcatalogue->itemtype = 'link';
                    $localcatalogue->url = new moodle_url('/local/catalogue/index.php', array(
                        'id' => 'snap-pm-course-catalogue'
                    ));
                    $localcatalogue->link = $localcatalogue->itemtype == 'link';
                    $localcatalogue->title = get_string('pluginname', 'local_catalogue');
                    $localcatalogue->titleidentifier = 'pluginname,local_catalogue';
                    $additionallinks[] = $localcatalogue;
                }
                // Program catalogue link.
                if (is_callable('mr_on') && mr_on('programs', '_MR_ENROL')) {
                    $programs = new stdClass();
                    $programs->itemtype = 'link';
                    $programs->url = new moodle_url('/enrol/programs/catalogue/index.php', array(
                        'id' => 'snap-pm-program-catalogue'
                    ));
                    $programs->link = $programs->itemtype == 'link';
                    $programs->title = get_string('catalogue', 'enrol_programs');
                    $programs->titleidentifier = 'catalogue,enrol_programs';
                    $additionallinks[] = $programs;
                }
                // My programs link.
                if (is_callable('mr_on') && mr_on('myprograms', '_MR_BLOCKS')) {
                    $myprograms = new stdClass();
                    $myprograms->itemtype = 'link';
                    $myprograms->url = new moodle_url( '/enrol/programs/my/index.php', array(
                        'id' => 'snap-pm-my-programs'
                    ));
                    $myprograms->link = $myprograms->itemtype == 'link';
                    $myprograms->title = get_string('pluginname', 'block_myprograms');
                    $myprograms->titleidentifier = 'pluginname,block_myprograms';
                    $additionallinks[] = $myprograms;
                }

                if (count($additionallinks)) {
                    $dividerstart = new stdClass();
                    $dividerstart->divider = true;
                    $dividerend = new stdClass();
                    $dividerend->divider = true;
                    $additionallinks[] = $dividerend;
                    array_unshift( $additionallinks, $dividerstart );
                    array_splice( $data['items'], $preferencesposition, 1, $additionallinks );
                }
            }

            $output .= $this->render_from_template('core/user_menu', $data);
        }
        return $output;
    }
}
