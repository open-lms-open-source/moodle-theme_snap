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
    Behat\Mink\Element\NodeElement,
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
     * Process givens array
     * @param array $givens
     * @return array
     */
    protected function process_givens_array(array $givens) {
        $givens = array_map(function($given){
            if (is_string($given)) {
                return new Given($given);
            } else if ($given instanceof Given) {
                return $given;
            } else {
                throw new coding_exception('Given must be a string or Given instance');
            }
        }, $givens);
        return $givens;
    }

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
     * @Given /^I log in as "(?P<username_string>(?:[^"]|\\")*)" \(theme_snap\)$/
     * @param string $username
     * @param bool $andkeepmenuopen
     */
    public function i_log_in_with_snap_as($username, $andkeepmenuopen = false) {

        $session = $this->getSession();
        
        // Go back to front page.
        $session->visit($this->locate_path('/'));

        if ($this->running_javascript()) {
            // Wait for the homepage to be ready.
            $session->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
        }

        /** @var behat_general $general */
        $general = behat_context_helper::get('behat_general');
        $general->i_click_on(get_string('login'), 'link');
        $general->assert_page_not_contains_text(get_string('logout'));

        /** @var behat_forms $form */
        $form = behat_context_helper::get('behat_forms');
        $form->i_set_the_field_to(get_string('username'), $this->escape($username));
        $form->i_set_the_field_to(get_string('password'), $this->escape($username));
        $form->press_button(get_string('login'));

        if (!$andkeepmenuopen) {
            $showfixyonlogin = get_config('theme_snap', 'personalmenulogintoggle');
            if ($showfixyonlogin) {
                $general->i_click_on('#fixy-close', 'css_element');
            }
        }
     }

    /**
     * Logs in the user but doesn't auto close personal menu.
     * There should exist a user with the same value as username and password.
     *
     * @Given /^I log in as "(?P<username_string>(?:[^"]|\\")*)", keeping the personal menu open$/
     * @param string $username
     */
    public function i_log_in_and_keep_personal_menu_open($username) {
        $this->i_log_in_with_snap_as($username, true);
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
     * @param int $section
     * @Given /^I go to single course section (\d+)$/
     */
    public function i_go_to_single_course_section($section) {
        $generalcontext = behat_context_helper::get('behat_general');
        $generalcontext->wait_until_the_page_is_ready();
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
     * @param int $section
     * @Given /^I go to course section (\d+)$/
     */
    public function i_go_to_course_section($section) {
        $generalcontext = behat_context_helper::get('behat_general');
        $generalcontext->wait_until_the_page_is_ready();
        $session = $this->getSession();
        $currenturl = $session->getCurrentUrl();
        if (stripos($currenturl, 'course/view.php') === false) {
            throw new ExpectationException('Current page is not a course page!', $session);
        }
        $session->executeScript('location.hash = "'.'section-'.$section.'";');
        $this->i_wait_until_is_visible('#section-'.$section, 'css_element');
    }

    /**
     * @param string
     * @return array
     * @Given  /^I can see course "(?P<course>(?:[^"]|\\")*)" in all sections mode$/
     */
    public function i_can_see_course_in_all_sections_mode($course) {
        $givens = [
            'I open the personal menu',
            'I follow "'.$course.'"',
            'I wait until the page is ready',
            'I go to single course section 1',
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
     * @Given  /^I log out \(theme_snap\)$/
     */
    public function i_log_out() {
        $this->i_open_the_personal_menu();

        /** @var behat_general $general */
        $general = behat_context_helper::get('behat_general');
        $general->i_click_on('#fixy-logout', 'css_element');
    }

    /**
     * @param string $coursename
     * @Given /^I create a new section in course "(?P<coursename>(?:[^"]|\\")*)"$/
     * @return array
     */
    public function i_create_a_new_section_in_course($coursename) {
        $givens = [
            'I open the personal menu',
            'I follow "'.$coursename.'"',
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
        // Only attempt to open the personal menu if its not already open.
        if (!$node->isVisible()) {
            /* @var $generalcontext behat_general */
            $generalcontext = behat_context_helper::get('behat_general');
            $generalcontext->i_click_on('.snap-my-courses-menu', 'css_element');
        }
    }

    /**
     * Checks that the provided node is visible.
     *
     * @throws ExpectationException
     * @param NodeElement $node
     * @param int $timeout
     * @param null|ExpectationException $exception
     * @return bool
     */
    protected function is_node_visible(NodeElement $node,
                                       $timeout = self::EXTENDED_TIMEOUT,
                                       ExpectationException $exception = null) {

        // If an exception isn't specified then don't throw an error if visibility can't be evaluated.
        $dontthrowerror = empty($exception);

        // Exception for timeout checking visibility.
        $msg = 'Something went wrong whilst checking visibility';
        $exception = new ExpectationException($msg, $this->getSession());

        $visible = false;

        try {
            $visible = $this->spin(
                function ($context, $args) {
                    if ($args->isVisible()) {
                        return true;
                    }
                    return false;
                },
                $node,
                $timeout,
                $exception,
                true
            );
        } catch (Exception $e) {
            if (!$dontthrowerror) {
                throw $exception;
            }
        }
        return $visible;
    }

    /**
     * Clicks link with specified id|title|alt|text.
     *
     * @When /^I follow visible link "(?P<link_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $link
     */
    public function click_visible_link($link) {
        $linknode = $this->find_link($link);
        if (!$linknode) {
            $msg = 'The "' . $linknode->getXPath() . '" xpath node could not be found';
            throw new ExpectationException($msg, $this->getSession());
        }

        // See if the first node is visible and if so click it.
        if ($this->is_node_visible($linknode)) {
            $linknode->click();
            return;
        }

        // The first node on the page isn't visible so we are going to have to get all nodes with the same xpath.
        // Extract xpath from the first node we found.
        $xpath = $linknode->getXpath();
        $matches = [];
        if (preg_match_all('|^\(//html/(.*)(?=\)\[1\]$)|', $xpath, $matches) !== false) {
            $xpath = $matches[1][0];
        } else {
            throw new coding_exception('Failed to extract xpath from '.$xpath);
        }

        // Now get all nodes.
        $linknodes = $this->find_all('xpath', $xpath);

        // Cycle through all nodes and if just one of them is visible break loop.
        foreach ($linknodes as $node) {
            if ($node === $linknode) {
                // We've already tested the first node, skip it.
                continue;
            }
            $visible = $this->is_node_visible($node, self::REDUCED_TIMEOUT);
            if ($visible) {
                break;
            }
        }

        if (!$visible) {
            // Oh dear, none of the links were visible.
            $msg = 'At least one node should be visible for the xpath "' . $node->getXPath();
            throw new ExpectationException($msg, $this->getSession());
        }

        // Hurray, we found a visible link - let's click it!
        $node->click();
    }


    /**
     * List steps required for adding a date restriction
     * @param int $datetime
     * @param string $savestr
     * @return array
     */
    protected function add_date_restriction($datetime, $savestr) {

        $year = date('Y', $datetime);
        $month = date('n', $datetime);
        $day = date('j', $datetime);

        $givens = [
            'I expand all fieldsets',
            'I click on "Add restriction..." "button"',
            '"Add restriction..." "dialogue" should be visible',
            'I click on "Date" "button" in the "Add restriction..." "dialogue"',
            'I set the field "day" to "'.$day.'"',
            // Need to be extra-specific about the month select as there's
            // other selects with that same label on the page.
            'I set the field with xpath "//select[@name=\'x[month]\']" to "'.$month.'"',
            'I set the field "year" to "'.$year.'"',
            'I press "'.$savestr.'"',
            'I wait until the page is ready'
        ];

        return $givens;
    }

    /**
     * Restrict a course section by date.
     * @param int $section
     * @param string $date
     * @Given /^I restrict course section (?P<section_int>(?:\d+)) by date to "(?P<date_string>(?:[^"]|\\")*)"$/
     */
    public function i_restrict_course_section_by_date($section, $date) {
        $datetime = strtotime($date);

        $givens = [
            'I go to course section '.$section,
            'I follow visible link "Edit Topic"',
            'I wait until ".snap-form-advanced" "css_element" is visible',
            'I set the field "name" to "Topic '.$date.' '.$section.'"',
        ];

        $givens = array_merge($givens, $this->add_date_restriction($datetime, 'Save changes'));

        return $this->process_givens_array($givens);
    }

    /**
     * Restrict a course asset by date.
     * @param string $assettitle
     * @param string $date
     * @Given /^I restrict course asset "(?P<asset_string>(?:[^"]|\\")*)" by date to "(?P<date_string>(?:[^"]|\\")*)"$/
     */
    public function i_restrict_asset_by_date($assettitle, $date) {
        $datetime = strtotime($date);

        $givens = [
            'I follow asset link "'.$assettitle.'"',
            'I click on "#admin-menu-trigger" "css_element"',
            'I wait until ".block_settings.state-visible" "css_element" is visible',
            'I navigate to "Edit settings" node in "Assignment administration"'
        ];

        $givens = array_merge($givens, $this->add_date_restriction($datetime, 'Save and return to course'));

        return $this->process_givens_array($givens);
    }

    /**
     * Check conditional date message in given element.
     * @param string $date
     * @param string $element
     * @param string $selectortype
     * @Given /^I should see available from date of "(?P<date_string>(?:[^"]|\\")*)" in "(?P<element_string>(?:[^"]|\\")*)" "(?P<locator_string>(?:[^"]|\\")*)"$/
     */
    public function i_should_see_available_from_in_element($date, $element, $selectortype) {
        $datetime = strtotime($date);

        $date = userdate($datetime,
            get_string('strftimedate', 'langconfig'));

        $givens = [
            'I should see "Available from" in the "'.$element.'" "'.$selectortype.'"',
            'I should see "'.$date.'" in the "'.$element.'" "'.$selectortype.'"',
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * Check conditional date message does not exist in given element.
     * @param string $date
     * @param string $element
     * @param string $selectortype
     * @Given /^I should not see available from date of "(?P<date_string>(?:[^"]|\\")*)" in "(?P<element_string>(?:[^"]|\\")*)" "(?P<locator_string>(?:[^"]|\\")*)"$/
     */
    public function i_should_not_see_available_from_in_element($date, $element, $selectortype) {
        $datetime = strtotime($date);

        $date = userdate($datetime,
            get_string('strftimedate', 'langconfig'));

        $givens = [
            'I should not see "Available from" in the "'.$element.'" "'.$selectortype.'"',
            'I should not see "'.$date.'" in the "'.$element.'" "'.$selectortype.'"',
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * Check conditional date message in nth asset within section x.
     * @param string $date
     * @param string $nthasset
     * @param int $section
     * @Given /^I should see available from date of "(?P<date_string>(?:[^"]|\\")*)" in the (?P<nthasset_string>(?:\d+st|\d+nd|\d+rd|\d+th)) asset within section (?P<section_int>(?:\d+))$/
     */
    public function i_should_see_available_from_in_asset($date, $nthasset, $section) {
        $nthasset = intval($nthasset);
        $elementselector = '#section-'.$section.' li.snap-asset:nth-of-type('.$nthasset.')';
        return $this->i_should_see_available_from_in_element($date, $elementselector, 'css_element');
    }

    /**
     * Check conditional date message not in nth asset within section x.
     * @param string $date
     * @param string $nthasset
     * @param int $section
     * @Given /^I should not see available from date of "(?P<date_string>(?:[^"]|\\")*)" in the (?P<nthasset_string>(?:\d+st|\d+nd|\d+rd|\d+th)) asset within section (?P<section_int>(?:\d+))$/
     */
    public function i_should_not_see_available_from_in_asset($date, $nthasset, $section) {
        $nthasset = intval($nthasset);
        $elementselector = '#section-'.$section.' li.snap-asset:nth-of-type('.$nthasset.')';
        return $this->i_should_not_see_available_from_in_element($date, $elementselector, 'css_element');
    }

    /**
     * Check conditional date message in section.
     * @param string $date
     * @param int $section
     * @Given /^I should see available from date of "(?P<date_string>(?:[^"]|\\")*)" in section (?P<section_int>(?:\d+))$/
     */
    public function i_should_see_available_from_in_section($date, $section) {
        $elementselector = '#section-'.$section.' > div.content > div.snap-restrictions-meta';
        return $this->i_should_see_available_from_in_element($date, $elementselector, 'css_element');
    }

    /**
     * Check conditional date message not in section.
     * @param string $date
     * @param int $section
     * @Given /^I should not see available from date of "(?P<date_string>(?:[^"]|\\")*)" in section (?P<section_int>(?:\d+))$/
     */
    public function i_should_not_see_available_from_in_section($date, $section) {
        $elementselector = '#section-'.$section.' > div.content > div.snap-restrictions-meta';
        return $this->i_should_not_see_available_from_in_element($date, $elementselector, 'css_element');
    }


    /**
     * @param string $text
     * @param int $tocitem
     * @Given /^I should see "(?P<text_string>(?:[^"]|\\")*)" in TOC item (?P<tocitem_int>(?:\d+))$/
     */
    public function i_should_see_in_toc_item($text, $tocitem) {
        $tocitem++; // Ignore introduction item.
        $givens = [
            'I should see "'.$text.'" in the "#chapters li:nth-of-type('.$tocitem.')" "css_element"'
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * @param string $text
     * @param int $tocitem
     * @Given /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in TOC item (?P<tocitem_int>(?:\d+))$/
     */
    public function i_should_not_see_in_toc_item($text, $tocitem) {
        $tocitem++; // Ignore introduction item.
        $givens = [
            'I should not see "'.$text.'" in the "#chapters li:nth-of-type('.$tocitem.')" "css_element"'
        ];
        return $this->process_givens_array($givens);
    }

    /**
     * Open an assignment or resource based on title.
     *
     * @param string $assettitle
     * @throws ExpectationException
     * @Given /^I follow asset link "(?P<assettitle>(?:[^"]|\\")*)"$/
     */
    public function i_follow_asset_link($assettitle) {
        $xpath = '//a/span[contains(.,"'.$assettitle.'")]';

        // Now get all nodes.
        $linknodes = $this->find_all('xpath', $xpath);

        // Cycle through all nodes and if just one of them is visible break loop.
        foreach ($linknodes as $node) {
            $visible = $this->is_node_visible($node, self::REDUCED_TIMEOUT);
            if ($visible) {
                break;
            }
        }

        if (!$visible) {
            // Oh dear, none of the links were visible.
            $msg = 'At least one node should be visible for the xpath "' . $node->getXPath();
            throw new ExpectationException($msg, $this->getSession());
        }

        // Hurray, we found a visible link - let's click it!
        $node->click();
    }

    /**
     * @param string $title
     * @Given /^I can see an input with the value "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function i_can_see_input_with_value($value) {
        $this->i_wait_until_is_visible('input[value="'.$value.'"]', 'css_element');
    }

    /**
     * @Given /^course page should be in edit mode$/
     */
    public function course_page_should_be_in_edit_mode() {
        /* @var $generalcontext behat_general */
        $generalcontext = behat_context_helper::get('behat_general');
        $generalcontext->assert_element_not_contains_text('Test assignment1', '#section-1', 'css_element');
        $generalcontext->ensure_element_exists('.block_news_items a.toggle-display', 'css_element');
        $this->i_can_see_input_with_value('Turn editing off');
    }

    /**
     * @Given /^I follow the page heading course link$/
     */
    public function i_follow_the_page_heading_course_link() {
        /** @var behat_general $helper */
        $helper = behat_context_helper::get('behat_general');
        $helper->i_click_on('#page-mast a', 'css_element');
    }

    /**
     * @Given /^I cannot follow the page heading$/
     */
    public function i_cannot_follow_the_page_heading() {
        $this->ensure_element_exists('#page-mast', 'css_element');
        $this->ensure_element_does_not_exist('#page-mast a', 'css_element');
    }

    /**
     * @param string $shortname
     * @Given /^Favorite toggle exists for course "(?P<shortname>(?:[^"]|\\")*)"$/
     */
    public function favorite_toggle_exists_for_course($shortname) {
        /* @var behat_general $general */
        $general = behat_context_helper::get('behat_general');
        $general->should_exist('.courseinfo[data-shortname="'.$shortname.'"] .favoritetoggle[aria-pressed="false"]', 'css_element');
    }

    /**
     * @param string $shortname1
     * @param string $shortname2
     * @Given /^Course card "(?P<shortname1>(?:[^"]|\\")*)" appears before "(?P<shortname2>(?:[^"]|\\")*)"$/
     */
    public function course_card_appears_before($shortname1, $shortname2) {
        /* @var behat_general $general */
        $general = behat_context_helper::get('behat_general');

        $preelement = '.courseinfo[data-shortname="'.$shortname1.'"]';
        $postelement = '.courseinfo[data-shortname="'.$shortname2.'"]';

        $general->should_appear_before($preelement, 'css_element', $postelement, 'css_element');
    }

    /**
     * @param string $shortname
     * @Given /^Course card "(?P<shortname>(?:[^"]|\\")*)" is favorited$/
     */
    public function course_is_favorited($shortname) {
        /* @var behat_general $general */
        $general = behat_context_helper::get('behat_general');
        $general->should_exist('.courseinfo[data-shortname="'.$shortname.'"] .favoritetoggle[aria-pressed="true"]', 'css_element');
    }

    /**
     * @param string $shortname
     * @Given /^Course card "(?P<shortname>(?:[^"]|\\")*)" is not favorited$/
     */
    public function course_is_not_favorited($shortname) {
        /* @var behat_general $general */
        $general = behat_context_helper::get('behat_general');
        $general->should_not_exist('.courseinfo[data-shortname="'.$shortname.'"] .favoritetoggle[aria-pressed="true"]', 'css_element');
    }

    /**
     * @param string $shortname
     * @Given /^I toggle course card favorite "(?P<shortname>(?:[^"]|\\")*)"$/
     */
    public function i_toggle_course_card_favorite($shortname) {
        /* @var behat_general $general */
        $general = behat_context_helper::get('behat_general');
        $general->i_click_on('.courseinfo[data-shortname="'.$shortname.'"] button.favoritetoggle', 'css_element');
    }
    
    /**
     * Follow the link which is located inside the personal menu.
     *
     * @When /^I follow "(?P<link>(?:[^"]|\\")*)" in the mobile personal menu$/
     * @param string $link we look for
     */
    public function i_follow_in_the_mobile_menu($link) {
        $node = $this->get_node_in_container('link', $link, 'css_element', '#fixy-mobile-menu');
        $this->ensure_node_is_visible($node);
        $node->click();
    }

    /**
     * Sends a message to the specified user from the logged user. The user full name should contain the first and last names.
     *
     * @Given /^I send "(?P<message_contents_string>(?:[^"]|\\")*)" message to "(?P<user_full_name_string>(?:[^"]|\\")*)" user \(theme_snap\)$/
     * @param string $messagecontent
     * @param string $userfullname
     */
    public function i_send_message_to_user($messagecontent, $userfullname) {
        /** @var behat_forms $form */
        $form = behat_context_helper::get('behat_forms');
        
        /* @var behat_general $general */
        $general = behat_context_helper::get('behat_general');

        $this->getSession()->visit($this->locate_path('message'));
        $form->i_set_the_field_to(get_string('searchcombined', 'message'), $this->escape($userfullname));
        $general->i_click_on('input[name="combinedsubmit"]', 'css_element');
        $general->click_link( $this->escape(get_string('sendmessageto', 'message', $userfullname)));
        $form->i_set_the_field_to('id_message', $this->escape($messagecontent));
        $general->i_click_on('#id_submitbutton', 'css_element');        
    }

    /**
     * @Given /^the message processor "(?P<processorname_string>(?:[^"]|\\")*)" is enabled$/
     * @param string $processorname
     */
    public function i_enable_message_processor($processorname) {
        global $DB;
        $DB->set_field('message_processors', 'enabled', '1', array('name' => $processorname));
    }

    /**
     * @Given /^the message processor "(?P<processorname_string>(?:[^"]|\\")*)" is disabled$/
     * @param string $processorname
     */
    public function i_disable_message_processor($processorname) {
        global $DB;
        $DB->set_field('message_processors', 'enabled', '0', array('name' => $processorname));
    }

    /**
     * @Given /^I am on the course "(?P<shortname_string>(?:[^"]|\\")*)"$/
     * @param string $shortname
     */
    public function i_am_on_the_course($shortname) {
        global $DB;
        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname]);
        $this->getSession()->visit($this->locate_path('/course/view.php?id='.$courseid));
    }
}
