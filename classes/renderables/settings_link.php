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
        if (!$PAGE->blocks->is_block_present('settings')) {
            return;
        }
        $canmanageacts = has_capability('moodle/course:manageactivities', $PAGE->context);
        $isstudent = !$canmanageacts && !is_role_switched($COURSE->id);
        if ($isstudent && $PAGE->pagetype != 'user-profile') {
            return;
        }

        // Find the settings block.
        // Core Moodle API appears to be missing a 'get block by name' function.
        if (!$PAGE->blocks->is_block_present('settings')) {
            debugging('Settings block was not found on this page', DEBUG_DEVELOPER);
            return;
        }

        foreach ($PAGE->blocks->get_regions() as $region) {
            foreach ($PAGE->blocks->get_blocks_for_region($region) as $block) {
                if (isset($block->instance) && $block->instance->blockname == 'settings') {
                    $this->instanceid = $block->instance->id;
                    continue 2;
                }
            }
        }

        if (!has_capability('moodle/block:view', \context_block::instance($this->instanceid))) {
            return;
        }

        $this->output = true;
    }
}