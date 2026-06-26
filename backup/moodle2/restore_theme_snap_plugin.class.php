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
 * Restore plugin for theme_snap — restores TOC hidden activity settings.
 *
 * This file is discovered automatically by Moodle's restore machinery whenever
 * theme_snap is installed on the destination site.
 *
 * @package   theme_snap
 * @category  backup
 * @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore plugin class for theme_snap.
 */
class restore_theme_snap_plugin extends restore_theme_plugin {

    /**
     * Pending entries collected during XML parsing.
     *
     * @var array[]
     */
    private array $pendingentries = [];

    /**
     * Declares the XML path(s) this plugin is interested in.
     *
     * @return restore_path_element[]
     */
    public function define_course_plugin_structure() {
        return [
            new restore_path_element(
                'snap_toc_hidden_module',
                $this->get_pathfor('/toc_hidden_modules/toc_hidden_module')
            ),
        ];
    }

    /**
     * Collects one <toc_hidden_module> element for deferred processing.
     *
     * @param array|object $data The element data from the backup XML.
     */
    public function process_snap_toc_hidden_module($data) {
        $data = (object) $data;

        $this->pendingentries[] = [
            'cmid'        => (int) $data->cmid,
            'timecreated' => isset($data->timecreated) ? (int) $data->timecreated : time(),
        ];
    }

    /**
     * Resolves pending entries and inserts theme_snap_toc_hidden records.
     *
     */
    public function after_restore_course() {
        foreach ($this->pendingentries as $entry) {
            // Resolve old cmid → new cmid via Moodle's ID mapping table.
            // Returns 0/false if the activity was excluded from this restore.
            $newcmid = $this->get_mappingid('course_module', $entry['cmid']);
            if (!$newcmid) {
                continue;
            }

            \theme_snap\toc_hidden::insert((int) $newcmid, $entry['timecreated']);
        }
    }
}
