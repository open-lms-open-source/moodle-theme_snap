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
# @author     Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
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
      | activity | course               | idnumber | name             | intro                         | section |
      | assign   | C1                   | assign1  | assignment1 | Test assignment description 1 | 0       |

  @javascript
  Scenario: Tabindex -1 exists for unnecessary focus order in the course dashboard.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "#snap-course-wrapper .toc-footer a:nth-child(2)" "css_element"
    And the "tabindex" attribute of "//aside[@id='block-region-side-pre']//a[@class='sr-only sr-only-focusable']" "xpath_element" should contain "-1"

  @javascript
  Scenario: Focus should be over the input with an error after submitting a form with a required field in blank.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "//li[@id='section-0']//div[@class='content']//div[@class='col-sm-6 snap-modchooser']//a" "xpath_element"
    And I click on "div.tab-pane.row.text-center.fade.active.in div:nth-child(5) a" "css_element"
    And I click on "Save and display" "button"
    Then the focused element is "input.form-control.is-invalid" "css_element"

  @javascript
  Scenario: On mobile view, submit buttons should appear after the advance form at the bottom of the form.
    Given I change window size to "658x852"
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Edit \"assignment1\""
    Then "div[role=main] .mform div.snap-form-required fieldset > div.form-group.fitem" "css_element" should appear after "div[role=main] .mform div.snap-form-advanced" "css_element"


