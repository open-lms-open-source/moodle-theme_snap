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
 * Snap message badge renderer.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use html_writer;

require_once($CFG->dirroot.'/message/output/badge/renderer.php');

class message_badge_renderer extends \message_badge_renderer {

    /**
     * The javascript module used by the presentation layer
     *
     * @return array
     */
    public function get_js_module() {
        return array(
            'name'     => 'message_badge',
            'fullpath' => '/theme/snap/javascript/badge.js',
            'requires' => array(
                'base',
                'node',
                'event',
                'overlay',
                'json-parse',
                'io',
                'moodle-core-popuphelp',
            ),
            'strings' => array(
                array('ok', 'moodle'),
                array('erroroccur', 'debug'),
                array('genericasyncfail', 'message_badge'),
                array('loading', 'theme_snap'),
                array('messageread', 'theme_snap'),
                array('close', 'form'), // For help icons.
            )
        );
    }

    /**
     * Override parent function - we don't want inconsistency for mobile devices!
     * @return bool
     */
    public function is_mobile() {
        return false;
    }

    /**
     * Render the badge element (message count)
     *
     * @param null $userid
     * @return string
     */
    public function badge($userid = null) {
        global $USER, $DB, $COURSE, $PAGE;

        // Only for logged in folks and when we are enabled.
        if (!isset($USER->message_badge_disabled)) {
            if (mr_off('badge', 'message') or !isloggedin() or isguestuser()) {
                $USER->message_badge_disabled = true;
            } else {
                $USER->message_badge_disabled = $DB->record_exists('message_processors', array('name' => 'badge', 'enabled' => 0));
            }
        }
        if ($USER->message_badge_disabled) {
            return '';
        }
        if ($this->is_mobile()) {
            return $this->mobile($userid);
        }

        $repo       = new \message_output_badge_repository_message();
        $forwardurl = new moodle_url('/message/output/badge/view.php', array('action' => 'forward', 'courseid' => $COURSE->id));
        $total      = $repo->count_user_unread_messages($userid);

        $PAGE->requires->js_init_call(
            'M.snap_message_badge.init_badge',
            array($forwardurl->out(false), $COURSE->id),
            false,
            $this->get_js_module()
        );

        if (!empty($total)) {
            $countdiv = html_writer::tag('span', $total, array('id' => html_writer::random_id(), 'class' => 'message_badge_count'));
        } else {
            $countdiv = '';
        }

        return $countdiv;
    }

    /**
     * title for messages list
     * @return string
     */
    public function messagestitle() {
        $title = html_writer::tag(
            'div',
            html_writer::tag('h2', get_string('alerts', 'message_badge')),
            array('class' => 'message_badge_title')
        );

        return (html_writer::tag('div', $title, array('id' => html_writer::random_id(), 'class' => 'yui3-widget-hd')));
    }

    /**
     * Render a single message
     *
     * @param \message_output_badge_model_message $message
     * @return string
     */
    public function render_message_output_badge_model_message(\message_output_badge_model_message $message) {
        global $COURSE;

        $text = $this->message_text($message);
        $urls = $this->message_urls($message);
        $pic  = $this->message_user_picture($message);

        if ($this->is_mobile()) {
            return html_writer::link(
                new moodle_url('/message/output/badge/view.php',
                    array('action' => 'read', 'courseid' => $COURSE->id, 'messageid' => $message->id)
                ),
                $pic.$text
            );
        }
        $haslong = 'true';
        if (trim(strip_tags($text)) == trim(strip_tags($this->message_text($message, false)))) {
            $haslong = 'false';
        }
        $content = html_writer::tag('div', $text.$urls, array('class' => 'message_badge_message_content'));
        return html_writer::tag('div', $pic.$content,
            array('id' => html_writer::random_id('message'),
                'messageid' => $message->id,
                'class' => 'message_badge_message',
                'data-has-full-message' => $haslong
            )
        );
    }

    /**
     * Render messages
     *
     * @param \message_output_badge_model_message[] $messages
     * @return string
     */
    public function messages(array $messages) {
        global $COURSE;

        $messagehtml = array();
        foreach ($messages as $message) {
            $messagehtml[] = $this->render($message);
        }
        if ($this->is_mobile()) {
            if (empty($messages)) {
                return html_writer::link(
                    new moodle_url('/course/view.php', array('id' => $COURSE->id)),
                    get_string('nomorealerts', 'message_badge'),
                    array('data-role' => 'button', 'data-icon' => 'home')
                );
            }
            if (!empty($this->page->theme->settings->mswatch)) {
                $showswatch = $this->page->theme->settings->mswatch;
            } else {
                $showswatch = '';
            }
            if ($showswatch == 'lightblue') {
                $dtheme = 'b';
            } else if ($showswatch == 'darkgrey') {
                $dtheme = 'a';
            } else if ($showswatch == 'black') {
                $dtheme = 'a';
            } else if ($showswatch == 'lightgrey') {
                $dtheme = 'c';
            } else if ($showswatch == 'mediumgrey') {
                $dtheme = 'd';
            } else if ($showswatch == 'glassy') {
                $dtheme = 'j';
            } else if ($showswatch == 'yellow') {
                $dtheme = 'e';
            } else if ($showswatch == 'verydark') {
                $dtheme = 'a';
            } else if ($showswatch == 'mrooms') {
                $dtheme = 'm';
            } else {
                $dtheme = 'm';
            }
            return html_writer::alist(
                $messagehtml,
                array(
                    'data-role' => 'listview',
                    'data-inset' => 'true',
                    'data-theme' => $dtheme,
                    'class' => 'message_badge_mobile_messages'
                )
            );
        }
        if (!empty($messagehtml)) {
            $hide = ' message_badge_hidden';
        } else {
            $hide = '';
        }
        $messagehtml  = implode('', $messagehtml);
        $messagehtml .= html_writer::tag(
            'div',
            get_string('nomorealerts', 'message_badge'),
            array('class' => "message_badge_empty$hide")
        );

        $bddiv   = html_writer::tag(
            'div',
            $messagehtml,
            array('id' => html_writer::random_id(), 'class' => 'yui3-widget-bd message_badge_messages')
        );

        $ftdiv   = html_writer::tag('div', '', array('id' => html_writer::random_id(), 'class' => 'yui3-widget-ft'));
        // GT MOD 2014-05-12 removed message_badge_hidden.
        $overlay = html_writer::tag(
            'div',
            $bddiv.$ftdiv,
            array('id' => html_writer::random_id(), 'class' => 'message_badge_overlay')
        );

        return html_writer::tag('div', $overlay, array('id' => html_writer::random_id(), 'class' => 'message_badge_container'));
    }
}
