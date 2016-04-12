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
use local_geniusws\navigation;

defined('MOODLE_INTERNAL') || die();

class bb_dashboard_link implements \renderable {

    /**
     * @var bool $output - are we ok to output the settings block.
     */
    public $output = false;

    /**
     * @throws coding_exception
     */
    function __construct() {
        global $USER;

        if (!class_exists('local_geniusws\navigation')) {
            return;
        }

        if (!navigation::dashboard_link_viewable($USER)) {
            return;
        }

        $this->output = true;
    }
}
