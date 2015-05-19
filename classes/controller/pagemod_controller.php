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

namespace theme_snap\controller;

defined('MOODLE_INTERNAL') || die();

/**
 * Deadlines Controller.
 * Handles requests regarding user deadlines and other CTAs.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pagemod_controller extends controller_abstract {
    /**
     * Do any security checks needed for the passed action
     *
     * @param string $action
     */
    public function require_capability($action) {
        $pagecmid = required_param('pagecmid', PARAM_INT);
        $cm = get_coursemodule_from_id('page', $pagecmid);
        if (!$cm) {
            error('Invalid pageid '.$pagecmid);
        }
        $context = \context_module::instance($cm->id);

        switch($action) {
            case 'get_page':
                require_capability('mod/page:view', $context);
                break;
             default:
                require_capability('mod/page:view', $context);
        }
    }

    /**
     * Read page
     *
     * @throws \coding_exception
     * @return stdClass
     */
    private function read_page() {
        $pagecmid = required_param('pagecmid', PARAM_INT);
        $cm = get_coursemodule_from_id('page', $pagecmid);
        $course = get_course($cm->course);
        $page = \theme_snap\local::get_page_mod($cm);
        $context = \context_module::instance($cm->id);

        // Trigger module instance viewed event.
        $event = \mod_page\event\course_module_viewed::create(array(
            'objectid' => $page->id,
            'context' => $context
        ));
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('page', $page);
        $event->trigger();

        return $page;
    }

    /**
     * Get the user's deadlines.
     *
     * @return string
     */
    public function get_page_action() {
        $page = $this->read_page();

        return json_encode(array(
            'html' => $page->content
        ));
    }

    /**
     * Mark page as read
     *
     * @return string
     */
    public function read_page_action() {
        $page = $this->read_page();

        return json_encode(array(
            'id' => $page->id
        ));
    }

}
