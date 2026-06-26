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

namespace theme_snap;

defined('MOODLE_INTERNAL') || die();

/**
 * All business logic for the theme_snap_toc_hidden feature.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class toc_hidden {

    /**
     * Return all cmids hidden from the Snap TOC for the given course.
     *
     * @param int $courseid
     * @return int[]
     */
    public static function get_cmids_for_course(int $courseid): array {
        global $DB;

        if ($courseid <= 0) {
            return [];
        }

        $sql = "SELECT h.cmid
                  FROM {theme_snap_toc_hidden} h
                  JOIN {course_modules} cm ON cm.id = h.cmid
                 WHERE cm.course = :courseid";

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        return array_values(array_map(static fn($r) => (int) $r->cmid, $records));
    }

    /**
     * Insert a toc_hidden record, skipping silently if it already exists.
     *
     * @param int $cmid
     * @param int|null $timecreated Defaults to now.
     */
    public static function insert(int $cmid, ?int $timecreated = null): void {
        global $DB;

        if ($DB->record_exists('theme_snap_toc_hidden', ['cmid' => $cmid])) {
            return;
        }

        $DB->insert_record('theme_snap_toc_hidden', [
            'cmid'        => $cmid,
            'timecreated' => $timecreated ?? time(),
        ]);
    }

    /**
     * Called from the course_module_created event handler.
     *
     * @param int    $newcmid    objectid from the course_module_created event.
     * @param string $modulename other['name'] from the same event.
     */
    public static function maybe_copy_on_duplicate(int $newcmid, string $modulename): void {
        global $DB;

        // Guard: only proceed if the name ends with the duplication suffix.
        $duplicatesuffix = get_string('duplicatedmodule', 'moodle', '');
        if (!str_ends_with($modulename, $duplicatesuffix)) {
            return;
        }

        $cm = $DB->get_record('course_modules', ['id' => $newcmid], 'id, section');
        if (!$cm) {
            return;
        }

        $section = $DB->get_record('course_sections', ['id' => $cm->section], 'id, sequence');
        if (!$section || empty($section->sequence)) {
            return;
        }

        $sequence = array_values(array_filter(array_map('intval', explode(',', $section->sequence))));
        $pos      = array_search($newcmid, $sequence, true);

        // No predecessor to copy from.
        if ($pos === false || $pos === 0) {
            return;
        }

        $sourcecmid = $sequence[$pos - 1];

        if (!$DB->record_exists('theme_snap_toc_hidden', ['cmid' => $sourcecmid])) {
            return;
        }

        self::insert($newcmid);
    }
}
