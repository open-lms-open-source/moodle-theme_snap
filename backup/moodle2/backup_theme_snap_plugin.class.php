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
 * Backup plugin for theme_snap — persists TOC hidden activity settings.
 *
 * Moodle's backup system calls this plugin at course level for every installed
 * theme.  We collect all theme_snap_toc_hidden rows whose cmid belongs to the
 * course being backed up and embed them in the course backup XML.
 * @package   theme_snap
 * @category  backup
 * @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup plugin class for theme_snap.
 *
 * Provides the course-level plugin structure that includes every
 * theme_snap_toc_hidden record for the course being backed up.
 */
class backup_theme_snap_plugin extends backup_theme_plugin {

    /**
     * Returns the structure to attach to the course element.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {

        $plugin = $this->get_plugin_element();

        $snapdata = new backup_nested_element($this->get_recommended_name());

        // Repeating element: one entry per hidden activity.
        $tochiddenmodules = new backup_nested_element('toc_hidden_modules');
        $tochiddenmodule  = new backup_nested_element(
            'toc_hidden_module',
            ['id'],
            ['cmid', 'timecreated']
        );

        // Build the XML tree.
        $plugin->add_child($snapdata);
        $snapdata->add_child($tochiddenmodules);
        $tochiddenmodules->add_child($tochiddenmodule);

        // Source: all toc_hidden rows for modules in this course.
        $tochiddenmodule->set_source_sql(
            "SELECT t.id, t.cmid, t.timecreated
               FROM {theme_snap_toc_hidden} t
               JOIN {course_modules} cm ON cm.id = t.cmid
              WHERE cm.course = :courseid",
            ['courseid' => backup::VAR_COURSEID]
        );

        // Register cmid as a course_module reference so the restore layer can
        // map old IDs to new ones automatically.
        $tochiddenmodule->annotate_ids('course_module', 'cmid');

        return $plugin;
    }
}
