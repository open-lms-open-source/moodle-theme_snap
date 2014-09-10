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
 * Theme upgrade
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */

function xmldb_theme_snap_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014080400) {
        if (get_config('core', 'theme') == 'snap') {
            set_config('deadlinestoggle', 0, 'theme_snap');
            set_config('messagestoggle', 0, 'theme_snap');
        }
        upgrade_plugin_savepoint(true, 2014080400, 'theme', 'snap');
    }

    if ($oldversion < 2014090900) {
        if (get_config('core', 'theme') == 'snap') {
            set_config('coursefootertoggle', 0, 'theme_snap');
        }
        upgrade_plugin_savepoint(true, 2014090900, 'theme', 'snap');
    }

    return true;
}
