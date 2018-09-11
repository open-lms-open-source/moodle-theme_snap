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
# Tests for course progress in the Snap personal menu.
#
# @package    theme_snap
# @copyright  Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu which features a
  list of courses they are enrolled in with a progress bar indication completion (if completion tracking is enabled).

  Background:
    Given the following "courses" exist:
      | fullname        | shortname | category | groupmode | visible |
      | Course 1        | C1        | 0        | 1         | 1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role            |
      | student1 | C1     | student         |
      | teacher1 | C1     | editingteacher  |

  @javascript
  Scenario Outline: Completion progress shows only when enabled and with tracked activities
    Given completion tracking is "Enabled" for course "C1"
    And I log in as "<username>"
    And I am on the course main page for "C1"
    And I should not see "Progress: 0 / 0"
    And the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | completion | completionview |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1          | 1              |
    And I reload the page
    And I open the personal menu
    And I wait for the personal menu to be loaded
    Then I should see "Course 1"
    And I should see "Progress: 0 / 1"
    And I am on "Course 1" course homepage
    And I go to course section 1
    And I mark the activity "Test assignment" as complete
    And I reload the page
    And I open the personal menu
    And I wait for the personal menu to be loaded
    Then I should see "Progress: 1 / 1"
    Given completion tracking is "Disabled" for course "C1"
    And I reload the page
    And I open the personal menu
    And I wait for the personal menu to be loaded
    Then I should not see "Progress:"
    Given completion tracking is "Enabled" for course "C1"
    And the following config values are set as admin:
      | enablecompletion   | 0 |
    And I reload the page
    Then I should not see "Progress:"
  Examples:
    | username |
    | student1 |
    | teacher1 |
