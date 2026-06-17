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
 * Unit tests for theme_snap\toc_hidden.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \theme_snap\toc_hidden
 */
class toc_hidden_test extends snap_base_test {

    /**
     * Create a course with two page activities in section 1 and return their cmids.
     * The section sequence is set to [$cmid1, $cmid2] in that order.
     *
     * @return array{course: \stdClass, cmid1: int, cmid2: int}
     */
    private function create_course_with_two_pages(): array {
        global $DB;

        $dg     = $this->getDataGenerator();
        $course = $dg->create_course(['numsections' => 1, 'initsections' => 1]);

        $page1 = $dg->create_module('page', ['course' => $course->id, 'section' => 1]);
        $page2 = $dg->create_module('page', ['course' => $course->id, 'section' => 1]);

        // Ensure the section sequence is exactly page1, page2 (generator usually
        // appends in creation order, but we make it explicit).
        $sectionid = $DB->get_field('course_sections', 'id',
            ['course' => $course->id, 'section' => 1]);
        $DB->set_field('course_sections', 'sequence', $page1->cmid . ',' . $page2->cmid,
            ['id' => $sectionid]);

        return ['course' => $course, 'cmid1' => (int) $page1->cmid, 'cmid2' => (int) $page2->cmid];
    }

    /** Insert a raw toc_hidden record directly (bypasses toc_hidden::insert guard). */
    private function raw_insert(int $cmid, int $timecreated = 0): void {
        global $DB;
        $DB->insert_record('theme_snap_toc_hidden', [
            'cmid'        => $cmid,
            'timecreated' => $timecreated ?: time(),
        ]);
    }

    /** Return the duplication name suffix, e.g. " (copy)". */
    private function dup_suffix(): string {
        return get_string('duplicatedmodule', 'moodle', '');
    }

    public function test_get_cmids_for_course_returns_empty_for_invalid_id(): void {
        $this->resetAfterTest();

        $this->assertSame([], toc_hidden::get_cmids_for_course(0));
        $this->assertSame([], toc_hidden::get_cmids_for_course(-1));
    }

    public function test_get_cmids_for_course_returns_empty_when_no_records(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->assertSame([], toc_hidden::get_cmids_for_course($course->id));
    }

    public function test_get_cmids_for_course_returns_correct_cmids(): void {
        $this->resetAfterTest();

        ['course' => $course, 'cmid1' => $cmid1, 'cmid2' => $cmid2] =
            $this->create_course_with_two_pages();

        $this->raw_insert($cmid1);

        $result = toc_hidden::get_cmids_for_course($course->id);
        $this->assertCount(1, $result);
        $this->assertContains($cmid1, $result);
        $this->assertNotContains($cmid2, $result);
    }

    public function test_get_cmids_for_course_does_not_leak_across_courses(): void {
        $this->resetAfterTest();

        $dg      = $this->getDataGenerator();
        $course1 = $dg->create_course();
        $course2 = $dg->create_course();

        $page1 = $dg->create_module('page', ['course' => $course1->id, 'section' => 0]);
        $page2 = $dg->create_module('page', ['course' => $course2->id, 'section' => 0]);

        $this->raw_insert((int) $page1->cmid);
        $this->raw_insert((int) $page2->cmid);

        $result1 = toc_hidden::get_cmids_for_course($course1->id);
        $result2 = toc_hidden::get_cmids_for_course($course2->id);

        $this->assertContains((int) $page1->cmid, $result1);
        $this->assertNotContains((int) $page2->cmid, $result1);

        $this->assertContains((int) $page2->cmid, $result2);
        $this->assertNotContains((int) $page1->cmid, $result2);
    }

    public function test_insert_creates_record(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid1' => $cmid1] = $this->create_course_with_two_pages();

        toc_hidden::insert($cmid1);

        $this->assertTrue($DB->record_exists('theme_snap_toc_hidden', ['cmid' => $cmid1]));
    }

    public function test_insert_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid1' => $cmid1] = $this->create_course_with_two_pages();

        toc_hidden::insert($cmid1);
        toc_hidden::insert($cmid1); // second call must not throw or duplicate.

        $this->assertCount(
            1,
            $DB->get_records('theme_snap_toc_hidden', ['cmid' => $cmid1])
        );
    }

    public function test_insert_respects_custom_timecreated(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid1' => $cmid1] = $this->create_course_with_two_pages();
        $ts = mktime(0, 0, 0, 1, 1, 2020);

        toc_hidden::insert($cmid1, $ts);

        $record = $DB->get_record('theme_snap_toc_hidden', ['cmid' => $cmid1]);
        $this->assertEquals($ts, (int) $record->timecreated);
    }

    public function test_maybe_copy_on_duplicate_does_nothing_without_suffix(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid1' => $cmid1, 'cmid2' => $cmid2] = $this->create_course_with_two_pages();
        $this->raw_insert($cmid1);

        // Name has no duplication suffix.
        toc_hidden::maybe_copy_on_duplicate($cmid2, 'Page');

        $this->assertFalse($DB->record_exists('theme_snap_toc_hidden', ['cmid' => $cmid2]));
    }

    public function test_maybe_copy_on_duplicate_copies_when_predecessor_is_hidden(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid1' => $cmid1, 'cmid2' => $cmid2] = $this->create_course_with_two_pages();
        // Mark the source (predecessor) as hidden.
        $this->raw_insert($cmid1);

        // Simulate duplication: cmid2 is right after cmid1 in the section sequence.
        toc_hidden::maybe_copy_on_duplicate($cmid2, 'Page' . $this->dup_suffix());

        $this->assertTrue($DB->record_exists('theme_snap_toc_hidden', ['cmid' => $cmid2]));
    }

    public function test_maybe_copy_on_duplicate_does_nothing_when_predecessor_is_not_hidden(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid2' => $cmid2] = $this->create_course_with_two_pages();
        // cmid1 is NOT in toc_hidden.

        toc_hidden::maybe_copy_on_duplicate($cmid2, 'Page' . $this->dup_suffix());

        $this->assertFalse($DB->record_exists('theme_snap_toc_hidden', ['cmid' => $cmid2]));
    }

    public function test_maybe_copy_on_duplicate_does_nothing_when_new_cm_is_first_in_section(): void {
        global $DB;
        $this->resetAfterTest();

        ['cmid1' => $cmid1] = $this->create_course_with_two_pages();
        // cmid1 is at position 0 in the sequence — no predecessor.

        toc_hidden::maybe_copy_on_duplicate($cmid1, 'Page' . $this->dup_suffix());

        $this->assertFalse($DB->record_exists('theme_snap_toc_hidden', ['cmid' => $cmid1]));
    }
}
