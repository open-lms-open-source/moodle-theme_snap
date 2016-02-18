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

use Behat\Behat\Context\Step\Given,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

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
     * @Given /^I upload file "(?P<fixturefilename_string>(?:[^"]|\\")*)" to section "(?P<section>(?:[^"]|\\")*)"$/
     */
    public function i_upload_file($fixturefilename, $section = 1) {
        global $CFG;
        $fixturefilename = clean_param($fixturefilename, PARAM_FILE);
        //$filepath = $CFG->themedir.'/snap/tests/fixtures/'.$fixturefilename;
        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$fixturefilename;
        $input = '#snap-drop-file-'.$section;
        $file = $this->find('css', $input);
        $file->attachFile($filepath);
    }

    /**
     * Bypass javascript attributed to link and just go straight to href.
     * @param string $link
     *
     * @Given /^Snap I follow link "(?P<link>(?:[^"]|\\")*)"$/
     */
    public function i_follow_href($link) {
        $el = $this->find_link($link);
        $href = $el->getAttribute('href');
        $this->getSession()->visit($href);
    }

    /**
     * @param int $section
     * @Given /^I go to course section (\d+)$/
     */
    public function i_go_to_course_section($section) {
        $currenturl = $this->getSession()->getCurrentUrl();
        if (stripos($currenturl, 'course/view.php') === false) {
            throw new ExpectationException('Current page is not a course page!', $this->getSession());
        }
        if (strpos($currenturl, '?') !== false) {
            $glue = '&';
        } else {
            $glue = '?';
        }
        $newurl = $currenturl.$glue.'section='.$section;
        $this->getSession()->visit($newurl);
    }

    /**
     * @param string
     * @return array
     * @Given  /^I can see course "(?P<course>(?:[^"]|\\")*)" in all sections mode$/
     */
    public function i_can_see_course_in_all_sections_mode($course) {
        $givens = [
            'I follow "Menu"',
            'Snap I follow link "'.$course.'"',
            'I wait until the page is ready',
            'I go to course section 1',
            '".section-navigation.navigationtitle" "css_element" should not exist',
            # In the above, .section-navigation.navigationtitle relates to the element on the page which contains the single
            # section at a time navigation. Visually you would see a link on the left entitled "General" and a link on the right
            # enitled "Topic 2"
            # This test ensures you do not see those elements. If you swap to clean theme in a single section mode at a time
            # course you will see that navigation after clicking on topic 1.
        ];
        $givens = array_map(function($given){
            return new Given($given);
        }, $givens);
        return $givens;
    }

    /**
     * @param string
     * @return array
     * @Given  /^Snap I log out$/
     */
    public function i_log_out() {
        $givens = [
            'I follow "Menu"',
            'I wait until ".btn.logout" "css_element" is visible',
            'I follow "Log out"',
            'I wait until the page is ready'
        ];
        $givens = array_map(function($given){
            return new Given($given);
        }, $givens);
        return $givens;
    }

    /**
     * @param string $coursename
     * @Given /^I create a new section in course "(?P<coursename>(?:[^"]|\\")*)"$/
     * @return array
     */
    public function i_create_a_new_section_in_course($coursename) {
        $givens = [
            'I open the personal menu',
            'Snap I follow link "'.$coursename.'"',
            'I follow "Create a new section"',
            'I set the field "Title" to "New section title"',
            'I click on "Create section" "button"'
        ];
        $givens = array_map(function($a) {return new Given($a);}, $givens);
        return $givens;
    }

    /**
     * I follow "Menu" fails randomly on occasions, this custom step is an alternative to resolve that issue.
     * It also avoids a failure if the menu is already open.
     * @Given /^I open the personal menu$/
     */
    public function i_open_the_personal_menu() {
        $node = $this->find('css', '#primary-nav');
        $this->getSession()->executeScript("window.scrollTo(0, 0);");
        if (!$node->isVisible()) {
            return [new Given('I click on "#js-personal-menu-trigger" "css_element"')];
        } else {
            // Already open.
            return null;
        }
    }
}
