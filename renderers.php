<?php
// This file is part of the custom Moodle Snap theme
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
 * Renderer overrides
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

require_once(__DIR__.'/renderers/snap_shared.php');
require_once(__DIR__.'/renderers/core_renderer.php');
require_once(__DIR__.'/renderers/course_renderer.php');
require_once(__DIR__.'/renderers/course_management_renderer.php');
require_once(__DIR__.'/renderers/course_format_topics_renderer.php');
require_once(__DIR__.'/renderers/course_format_weeks_renderer.php');

// Include badge renderer if it should be.
if (file_exists($CFG->dirroot.'/message/output/badge/renderer.php')) {
    require_once(__DIR__.'/renderers/message_badge_renderer.php');
}
