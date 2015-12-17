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
 * Settings link renderable.
 * @author    gthomas2
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\renderables;

defined('MOODLE_INTERNAL') || die();

class settings_link implements \renderable {

    /**
     * @var int $instanceid
     */
    public $instanceid;

    /**
     * @var bool $output - are we ok to output the settings block.
     */
    public $output = false;

    /**
     * @throws coding_exception
     */
    function __construct() {
        global $PAGE, $COURSE;

        // Page path blacklist for admin menu.
        $adminblockblacklist = ['/user/profile.php'];
        if (in_array($PAGE->url->get_path(), $adminblockblacklist)) {
            return;
        }

        // Admin users always see the admin menu with the exception of blacklisted pages.
        // The admin menu shows up for other users if they are a teacher in the current course.
        if (!is_siteadmin()) {
            // We don't want students to see the admin menu ever.
            $canmanageacts = has_capability('moodle/course:manageactivities', $PAGE->context);
            $isstudent = !$canmanageacts && !is_role_switched($COURSE->id);
            if ($isstudent) {
                return;
            }
        }

        if (!$PAGE->blocks->is_block_present('settings')) {
            // Throw error if on front page or course page.
            // (There are pages that don't have a settings block so we shouldn't throw an error on those pages).
            if (strpos($PAGE->pagetype, 'course-view') === 0 || $PAGE->pagetype === 'site-index') {
                debugging('Settings block was not found on this page', DEBUG_DEVELOPER);
            }
            return;
        }

        // Core Moodle API appears to be missing a 'get block by name' function.
        // Cycle through all regions and block instances until we find settings.
        foreach ($PAGE->blocks->get_regions() as $region) {
            foreach ($PAGE->blocks->get_blocks_for_region($region) as $block) {
                if (isset($block->instance) && $block->instance->blockname == 'settings') {
                    $this->instanceid = $block->instance->id;
                    break 2;
                }
            }
        }
        
        if (!has_capability('moodle/block:view', \context_block::instance($this->instanceid))) {
            return;
        }

        $this->output = true;
    }
}