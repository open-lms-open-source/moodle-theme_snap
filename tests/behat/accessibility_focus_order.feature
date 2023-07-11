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
# Tests for toggle course section visibility in non edit mode in snap.
#
# @package    theme_snap
# @author     Rafael Becerra rafael.becerrarodriguez@openlms.net
# @copyright  Copyright (c) 2019 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
# Some scenarios will be testing AX through special steps depending on the needed rules.
# https://github.com/dequelabs/axe-core/blob/v3.5.5/doc/rule-descriptions.md#best-practices-rules.
# Focusable elements of hidden elements: cat.name-role-value, wcag412.
# Forms: cat.forms, wcag21aa, wcag135.
Feature: Check that the correct tab order and focus exists for the page.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
    And the following "activities" exist:
      | activity | course               | idnumber | name        | intro                         | section |
      | assign   | C1                   | assign1  | assignment1 | Test assignment description 1 | 0       |

  @javascript @accessibility
  Scenario: Tabindex -1 exists for unnecessary focus order in the course dashboard.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And the "tabindex" attribute of "//aside[@id='block-region-side-pre']//a[@class='sr-only sr-only-focusable']" "xpath_element" should contain "-1"
    And the page should meet "cat.name-role-value, wcag412" accessibility standards

  @javascript @accessibility
  Scenario: Focus should be over the input with an error after submitting a form with a required field in blank.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I add a "Assignment" to section "0"
    And I click on "Save and display" "button"
    # To indicate that the form has failed.
    Then "#id_error_name" "css_element" should be visible
    # Fire a second form save to check that the input with the error is indeed focused.
    And I click on "Save and display" "button"
    Then the focused element is "input.form-control.is-invalid" "css_element"
    And the page should meet "cat.forms, wcag21aa, wcag135" accessibility standards

  @javascript
  Scenario: On mobile view, submit buttons should appear after the advance form at the bottom of the form.
    Given I change window size to "658x852"
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Edit \"assignment1\""
    Then "div[role=main] .mform div.snap-form-advanced > div.form-group.fitem [data-fieldtype='group']" "css_element" should appear after the "div[role=main] .mform div.snap-form-advanced div.collapsible-actions" "css_element"


