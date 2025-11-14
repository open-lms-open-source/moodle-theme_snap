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
use theme_snap\webservice\ws_course_toc_progressbar;
use core_external\external_function_parameters;
use core_external\external_single_structure;

/**
 * Test Course TOC progress bar web service
 * @author    Daniel Cifuentes
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class webservice_ws_course_toc_progressbar extends \advanced_testcase {

    public function test_service_parameters() {
        $params = ws_course_toc_progressbar::service_parameters();
        $this->assertTrue($params instanceof external_function_parameters);
    }

    public function test_service_returns() {
        $returns = ws_course_toc_progressbar::service_returns();
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

        $assign1 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $assign2 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $this->setUser($user);

        $result = ws_course_toc_progressbar::service(
            $user->id,
            $course->id,
        );

        // No activity has been marked as completed.
        $this->assertEquals("Progress: 0/2", $result["courseprogress"]);
        $this->assertEquals(0, $result["progresspercentage"]);

        $cmassign = get_coursemodule_from_id('assign', $assign1->cmid);
        $completion = new completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $user->id);

        $result = ws_course_toc_progressbar::service(
            $user->id,
            $course->id,
        );

        // 1 activity was marked as completed.
        $this->assertEquals("Progress: 1/2", $result["courseprogress"]);
        $this->assertEquals(50, $result["progresspercentage"]);

        // Add new activity with completion disabled.
        $assign3 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_NONE,
        ]);

        $result = ws_course_toc_progressbar::service(
            $user->id,
            $course->id,
        );

        // Result should not change since we are only considering activities with completion enabled.
        $this->assertEquals("Progress: 1/2", $result["courseprogress"]);
        $this->assertEquals(50, $result["progresspercentage"]);


        // Add new activity with completion enabled.
        $assign4 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $result = ws_course_toc_progressbar::service(
            $user->id,
            $course->id,
        );

        // Result should change. Now we have 3 activities with completion enabled.
        $this->assertEquals("Progress: 1/3", $result["courseprogress"]);
        $this->assertEquals(33.0, $result["progresspercentage"]);

        // Delete 1 activity.
        $cmassign = get_coursemodule_from_id('assign', $assign4->cmid);
        course_delete_module($cmassign->id);

        $result = ws_course_toc_progressbar::service(
            $user->id,
            $course->id,
        );

        // Result should change. Now we have 2 activities with completion enabled.
        $this->assertEquals("Progress: 1/2", $result["courseprogress"]);
        $this->assertEquals(50, $result["progresspercentage"]);
    }
}
