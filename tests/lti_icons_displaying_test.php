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
 * LTI icons displaying correctly in Snap.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2020 Open LMS. (http://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap;
defined('MOODLE_INTERNAL') || die();
use theme_snap\output\core_renderer;

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');
/**
 * LTI icons displaying correctly in Snap.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2020 Open LMS. (http://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_icons_displaying_test extends \advanced_testcase {
    /**
     * Setup for each test.
     */
    protected function setUp():void {
        global $CFG;
        $CFG->theme = 'snap';
        $this->resetAfterTest(true);
    }

    /**
     * Tests that both possible LTI icons are displayed in Snap.
     */
    public function test_lti_icons_are_displayed() {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');
        $type->coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;

        $type2 = new \stdClass();
        $type2->state = LTI_TOOL_STATE_CONFIGURED;
        $type2->name = "Test tool two";
        $type2->description = "Example description";
        $type2->clientid = "Test client ID two";
        $type2->baseurl = $this->getExternalTestFileUrl('/test.html');
        $type2->coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $type2->icon = 'http://www.example.com/1/example_folder/exampleicon.jpg';

        $config = new \stdClass();
        $typeid = lti_add_type($type, $config);
        $type2id = lti_add_type($type2, $config);

        $renderer = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);
        $html = $renderer->testhelper_course_modchooser();

        // @codingStandardsIgnoreLine
        $ltiregularicon = '<img src="https://www.example.com/moodle/theme/image.php/_s/snap/lti/1/icon" class="svg-icon" alt="" role="presentation"><br>Test tool';
        $shouldbecontained = strpos($html, $ltiregularicon);
        $this->assertNotEmpty($shouldbecontained);

        // @codingStandardsIgnoreLine
        $lticustomicon = '<img src="http://www.example.com/1/example_folder/exampleicon.jpg" class="svg-icon" alt="" role="presentation"><br>Test tool two';
        $shouldbecontained = strpos($html, $lticustomicon);
        $this->assertNotEmpty($shouldbecontained);
    }
}
