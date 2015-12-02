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
 * Steps definitions for behat theme.
 *
 * @package   theme_snap
 * @category  test
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given;

/**
 * Choice activity definitions.
 *
 * @package   theme_snap
 * @category  test
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_snap extends behat_base {

    /**
     * Waits until the provided element selector is visible.
     *
     * @Given /^I wait until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" is visible$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function i_wait_until_is_visible($element, $selectortype) {
        $this->ensure_element_is_visible($element, $selectortype);
    }

    /**
     * Logs in the user. There should exist a user with the same value as username and password.
     *
     * @Given /^I log in with snap as "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_log_in_with_snap_as($username) {

        // Go back to front page.
        $this->getSession()->visit($this->locate_path('/'));

        // Generic steps (we will prefix them later expanding the navigation dropdown if necessary).
        $steps = array(
            new Given('I click on "' . get_string('login') . '" "link"'),
            new Given('I wait until "#loginbtn" "css_element" is visible'),
            new Given('I set the field "' . get_string('username') . '" to "' . $this->escape($username) . '"'),
            new Given('I set the field "' . get_string('password') . '" to "'. $this->escape($username) . '"'),
            new Given('I press "' . get_string('login') . '"')
        );

        // If Javascript is disabled we have enough with these steps.
        if (!$this->running_javascript()) {
            return $steps;
        }

        // Wait for the homepage to be ready.
        $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);

        return $steps;
    }

    /**
     * @param string $fixturefilename this is a filename relative to the snap fixtures folder.
     * @param string $input
     *
     * @Given /^I upload file "(?P<fixturefilename_string>(?:[^"]|\\")*)" using input "(?P<input_string>(?:[^"]|\\")*)"$/
     */
    public function i_upload_file_using_input($fixturefilename, $input) {
        global $CFG;
        $fixturefilename = clean_param($fixturefilename, PARAM_FILE);
        //$filepath = $CFG->themedir.'/snap/tests/fixtures/'.$fixturefilename;
        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$fixturefilename;
        $file = $this->find('css', $input);
        $file->attachFile($filepath);
    }
}
