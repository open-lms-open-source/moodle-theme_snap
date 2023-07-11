# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
#
# Tests for aria-label and attributes regarding accessibility.
#
# @package    theme_snap
# @autor      Oscar Nadjar
# @copyright  Copyright (c) 2019 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
# Some scenarios will be testing AX through special steps depending on the needed rules.
# https://github.com/dequelabs/axe-core/blob/v3.5.5/doc/rule-descriptions.md#best-practices-rules.
# Aria attributes: cat.aria, wcag412 tags.
# Unique attributes, mainly ID's: cat.parsing, wcag411 tags.
Feature: Elements for Snap should have the proper aria attributes.

  Background:
    Given the following config values are set as admin:
      | enableglobalsearch | true |
    Given the following "courses" exist:
      | fullname | shortname | category | format | enablecompletion |
      | Course 1 | C1        | 0        | topics | 1                |
      | Course 2 | C2        | 0        | topics | 1                |
      | Course 3 | C3        | 0        | topics | 1                |
      | Course 4 | C4        | 0        | topics | 1                |
      | Course 5 | C5        | 0        | topics | 1                |
      | Course 6 | C6        | 0        | topics | 1                |
      | Course 7 | C7        | 0        | topics | 1                |
      | Course 8 | C8        | 0        | topics | 1                |
      | Course 9 | C9        | 0        | topics | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course               | idnumber | name             | intro                         | section | assignsubmission_onlinetext_enabled | completion | completionview |
      | assign   | C1                   | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   | 1          | 0              |
      | assign   | Acceptance test site | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   | 0          | 0              |
      | assign   | C1                   | assign2  | Test assignment2 | Test assignment description 2 | 1       | 1                                   | 1          | 0              |

  @javascript
  # This is a scenario for a core view.
  Scenario: All calendar's anchors must contain the aria-label attribute
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And I click on ".snap-asset .snap-edit-asset" "css_element"
    And the "aria-label" attribute of "#id_allowsubmissionsfromdate_calendar" "css_element" should contain "Calendar"
    And the "aria-label" attribute of "#id_cutoffdate_calendar" "css_element" should contain "Calendar"
    And the "aria-label" attribute of "#id_gradingduedate_calendar" "css_element" should contain "Calendar"
    And the "aria-label" attribute of "#id_duedate_calendar" "css_element" should contain "Calendar"

  @javascript @accessibility
  Scenario: Elements in front page must comply with the accessibility standards.
    Given I log in as "admin"
    And the following config values are set as admin:
    | linkadmincategories | 0 |
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Appearance" node
    And I expand "Themes" node
    And I follow "Snap"
    And I follow "Featured courses"
    And I set the field with xpath "//div[@class='form-text defaultsnext']//input[@id='id_s_theme_snap_fc_one']" to "1"
    And I set the field with xpath "//div[@class='form-text defaultsnext']//input[@id='id_s_theme_snap_fc_two']" to "2"
    And I set the field with xpath "//div[@class='form-text defaultsnext']//input[@id='id_s_theme_snap_fc_three']" to "3"
    And I set the field with xpath "//div[@class='form-text defaultsnext']//input[@id='id_s_theme_snap_fc_four']" to "4"
    And I set the field with xpath "//*[@id='id_s_theme_snap_fc_browse_all']" to "1"
    And I press "Save changes"
    And I am on site homepage
    And the page should meet "cat.aria, wcag412" accessibility standards
    And the page should meet "cat.parsing, wcag411" accessibility standards

  @javascript @accessibility
  Scenario: Elements in personal menu must comply with the accessibility standards.
    Given I log in as "admin"
    And I am on site homepage
    And I open the personal menu
    # New ID's for personal menu elements are for the most used elements. This ID's are being established in accessibility.js AMD file.
    And the page should meet "cat.aria, wcag412" accessibility standards
    And the page should meet "cat.parsing, wcag411" accessibility standards

  @javascript @accessibility
  Scenario: Elements in course main view must comply with the accessibility standards.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And the page should meet "cat.aria, wcag412" accessibility standards
    And the page should meet "cat.parsing, wcag411" accessibility standards

  @javascript @accessibility
  Scenario: Elements in course dashboard must comply with the accessibility standards.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I follow "Course Dashboard"
    And the page should meet "cat.aria, wcag412" accessibility standards
    And the page should meet "cat.parsing, wcag411" accessibility standards

  @javascript @accessibility
  Scenario: When an activity have a restriction, the lock icon should have the needed aria attributes.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    And I click on "//a[i[contains(@title, 'Test assignment1')]]" "xpath_element"
    And I wait until the page is ready
    And I click on "//fieldset[@id=\"id_availabilityconditionsheader\"]" "xpath_element"
    And I click on "//button[text()=\"Add restriction...\"]" "xpath_element"
    And I click on "//button[@id=\"availability_addrestriction_grade\"]" "xpath_element"
    And I set the field with xpath "//span[@class=\"pr-3\"][text()=\"Grade\"]//following-sibling::span//select" to "Test assignment2"
    Then I click on "//input[@id=\"id_submitbutton2\"]" "xpath_element"
    And I wait until the page is ready
    And the page should meet "cat.aria, wcag412" accessibility standards
    And the page should meet "cat.parsing, wcag411" accessibility standards

  @javascript @accessibility
  Scenario: Check accessibility on the Snap settings page.
    Given I log in as "admin"
    And the following config values are set as admin:
      | linkadmincategories | 0 |
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Appearance" node
    And I expand "Themes" node
    And I follow "Snap"
    # We need to test it in each view or else it will fail.
    # Cover display.
    And I follow "Cover display"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Personal menu.
    And I follow "Personal menu"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Feature spots.
    And I follow "Feature spots"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Featured courses.
    And I follow "Featured courses"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Course display.
    And I follow "Course display"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Social media.
    And I follow "Social media"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Navigation bar.
    And I follow "Navigation bar"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Category color.
    And I follow "Category color"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # Profile based branding.
    And I follow "Profile based branding"
    And the page should meet "cat.aria, wcag412" accessibility standards
    # H5P Custom CSS.
    And I follow "H5P Custom CSS"
    And the page should meet "cat.aria, wcag412" accessibility standards
