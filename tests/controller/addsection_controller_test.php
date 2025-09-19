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
 * addsection_controller Tests
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\controller;

use advanced_testcase;

/**
 * Test for addsection_controller.
 *
 * @coversDefaultClass \theme_snap\controller\addsection_controller
 */
class addsection_controller_test extends advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Scenario: Course with subsections (component=mod_subsection).
     * Validate that adding a section creates only ONE new, normal section.
     *
     * @covers ::addsection_action
     */
    public function test_addsection_action_with_subsections() {
        global $PAGE, $DB;

        //Create base course with 2 initial sections, section 0 and section 1.
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $PAGE->set_context(\context_course::instance($course->id));

        // Create teacher user
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        // Manually insert subsection into course_sections table.
        $DB->insert_record('course_sections', (object)[
            'course' => $course->id,
            'section' => 2,
            'name' => 'SUBSECTION',
            'summary' => '',
            'summaryformat' => FORMAT_HTML,
            'sequence' => '',
            'visible' => 1,
            'component' => 'mod_subsection'
        ]);

        // Prepare mock POST parameters.
        $draftid = file_get_unused_draft_itemid();
        $_POST['draftitemid'] = $draftid;
        $_POST['newsection'] = 'Additional section';
        $_POST['summary'] = ['text' => 'Summary', 'format' => FORMAT_HTML];
        $_POST['sesskey'] = sesskey();

        // Execute action and catch exception thrown by redirect()
        $controller = new addsection_controller();
        $thrown = false;
        ob_start();
        try {
            $controller->addsection_action();
        } catch (\moodle_exception $e) {
            $thrown = true;
        }
        ob_end_clean();

        // Confirm that the exception was thrown.
        $this->assertTrue($thrown, 'Redirect() was expected to throw an exception in PHPUnit');

        // Retrieve only normal sections (component IS NULL).
        $sections = $DB->get_records('course_sections', [
            'course' => $course->id,
            'component' => null
        ]);

        $lastsection = end($sections);

        $this->assertEquals('Additional section', $lastsection->name);
        $this->assertEquals('Summary', $lastsection->summary);

        // Verify that multiple extra sections were not created
        $normalsections = array_filter($sections, function($s) {
            return empty($s->component);
        });

        $this->assertCount(3, $normalsections, 'There should be only 3 normal sections (2 initial + 1 new)');
    }
}
