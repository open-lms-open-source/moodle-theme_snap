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

use theme_snap\webservice\ws_course_section_progress;
use core_external\external_function_parameters;
use core_external\external_single_structure;

/**
 * Test Course Section Progress web service
 * @author    Dayana Pardo
 * @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webservice_ws_course_section_progress extends \advanced_testcase {

    public function test_service_parameters() {
        $params = ws_course_section_progress::service_parameters();
        $this->assertTrue($params instanceof external_function_parameters);
    }

    public function test_service_returns() {
        $returns = ws_course_section_progress::service_returns();
        $this->assertTrue($returns instanceof external_single_structure);
    }

    public function test_service() {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => true]);
        $user = $this->getDataGenerator()->create_user();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id,
            $course->id,
            $studentrole->id);

        // Get section 1 (section 0 is general section).
        $section1 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);

        // Create activities in section 1 with completion tracking.
        $assign1 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'section' => 1,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $assign2 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'section' => 1,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $this->setUser($user);

        // Test initial state - no activities completed.
        $result = ws_course_section_progress::service(
            $section1->id,
            $user->id,
            $course->id,
        );

        $this->assertEquals(0, $result['sections'][0]['completed']);
        $this->assertEquals(2, $result['sections'][0]['total']);
        $this->assertTrue($result['sections'][0]['hasprogress']);
        $this->assertFalse($result['sections'][0]['iscomplete']);

        // Mark one activity as complete.
        $cmassign1 = get_coursemodule_from_id('assign', $assign1->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign1, COMPLETION_COMPLETE, $user->id);

        $result = ws_course_section_progress::service(
            $section1->id,
            $user->id,
            $course->id,
        );

        $this->assertEquals(1, $result['sections'][0]['completed']);
        $this->assertEquals(2, $result['sections'][0]['total']);
        $this->assertTrue($result['sections'][0]['hasprogress']);
        $this->assertFalse($result['sections'][0]['iscomplete']);

        // Mark second activity as complete - section should be complete.
        $cmassign2 = get_coursemodule_from_id('assign', $assign2->cmid);
        $completion->update_state($cmassign2, COMPLETION_COMPLETE, $user->id);

        $result = ws_course_section_progress::service(
            $section1->id,
            $user->id,
            $course->id,
        );

        $this->assertEquals(2, $result['sections'][0]['completed']);
        $this->assertEquals(2, $result['sections'][0]['total']);
        $this->assertTrue($result['sections'][0]['hasprogress']);
        $this->assertTrue($result['sections'][0]['iscomplete']);

        $result = ws_course_section_progress::service(
            $section1->id,
            $user->id,
            $course->id,
        );

        // Result should not change since we only consider activities with completion enabled.
        $this->assertEquals(2, $result['sections'][0]['completed']);
        $this->assertEquals(2, $result['sections'][0]['total']);
        $this->assertTrue($result['sections'][0]['hasprogress']);
        $this->assertTrue($result['sections'][0]['iscomplete']);

        // Test section with no activities with completion tracking.
        $section2 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 2]);

        $result = ws_course_section_progress::service(
            $section2->id,
            $user->id,
            $course->id,
        );

        $this->assertEquals(0, $result['sections'][0]['completed']);
        $this->assertEquals(0, $result['sections'][0]['total']);
        $this->assertFalse($result['sections'][0]['hasprogress']);
        $this->assertFalse($result['sections'][0]['iscomplete']);
    }
}