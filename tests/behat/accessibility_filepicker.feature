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
Feature: When adding a submission in an assignment, file picker options should exists as buttons.

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
      | activity | course | idnumber | name             |
      | assign   | C1     | assign1  | Test assignment1 |

  @javascript
  Scenario: Filepicker options needs to exists as buttons and be operable with space
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Test assignment1"
    And I click on "Add submission" "button"
    And "button#addbtn" "css_element" should exist
    And "button#createfolderbtn" "css_element" should exist
    And "button#addbtn" "css_element" should exist
    And "button#displayiconsbtn" "css_element" should exist
    And "button#displaydetailsbtn" "css_element" should exist
    And "button#displaytreebtn" "css_element" should exist
