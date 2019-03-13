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


@theme @theme_snap
Feature: When the Moodle theme is set to Snap, core notifications messages should have a specific aria attribute to
  screen readers functionality.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
      | student1  | Student    | 1         | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
      | student1  | C1      | student         |
    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Open Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum type | Standard forum for general use |
      | Description | Test forum description |
    And I log out
    
  @javascript
  Scenario: Error notification should have close dialog as aria-label attribute to be accessible
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "li.modtype_quiz a.mod-link" "css_element"
    And the "aria-label" attribute of "div.alert-danger button.close" "css_element" should contain "Close"

  @javascript
  Scenario: Success notification should have close dialog as aria-label attribute to be accessible
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I click on "li.modtype_hsuforum a.mod-link" "css_element"
    And I wait until the page is ready
    And I create the following inline discussions:
      | subject            | message                       |
      | Test discussion 1  | Test discussion 1 description |
    And I should see "Your post was successfully added."
    And the "aria-label" attribute of "div.alert-success button.close" "css_element" should contain "Close"

