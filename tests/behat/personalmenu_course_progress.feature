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
# @copyright  Copyright (c) 2016 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @theme_snap_personalmenu
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu which features a
  list of courses they are enrolled in with a progress bar indication completion (if completion tracking is enabled).

  Background:
    Given the following "courses" exist:
      | fullname        | shortname | category | groupmode | visible |
      | Course 1        | C1        | 0        | 1         | 1       |
      | Course 2        | C2        | 0        | 1         | 1       |
      | Course 3        | C3        | 0        | 1         | 1       |
      | Course 4        | C4        | 0        | 1         | 1       |
      | Course 5        | C5        | 0        | 1         | 1       |
      | Course 6        | C6        | 0        | 1         | 1       |
      | Course 7        | C7        | 0        | 1         | 1       |
      | Course 8        | C8        | 0        | 1         | 1       |
      | Course 9        | C9        | 0        | 1         | 1       |
      | Course 10       | C10       | 0        | 1         | 1       |
      | Course 11       | C11       | 0        | 1         | 1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student2  | 1        | student2@example.com |
      | student3 | Student3  | 1        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role            |
      | student1 | C1     | student         |
      | student2 | C1     | student         |
      | student2 | C2     | student         |
      | student2 | C3     | student         |
      | student2 | C4     | student         |
      | student2 | C5     | student         |
      | student2 | C6     | student         |
      | student3 | C1     | student         |
      | student3 | C2     | student         |
      | student3 | C3     | student         |
      | student3 | C4     | student         |
      | student3 | C5     | student         |
      | student3 | C6     | student         |
      | student3 | C7     | student         |
      | student3 | C8     | student         |
      | student3 | C9     | student         |
      | student3 | C10    | student         |
      | student3 | C11    | student         |
      | teacher1 | C1     | editingteacher  |
    And the following config values are set as admin:
      | theme_snap_bar_limit | 10 |

  @javascript
  Scenario: Completion progress shows only when enabled and with tracked activities
    Given completion tracking is "Enabled" for course "C1"
    And I log in as "student1"
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

  @javascript
  Scenario: Completion progress shows only when enabled and with tracked activities
    Given the following config values are set as admin:
      | enablecompletion   | 1 |
    And completion tracking is "Enabled" for course "C1"
    And the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | completion | completionview |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1          | 1              |
    And I log in as "student1"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    Then I should see "Course 1"
    And I should see "Progress: 0 / 1"
    And I should not see "Course limit reached progress bar will not be displayed."
    And I should not see "If more than {$a} courses are listed progress bar will not be displayed"

  @javascript
  Scenario: Completion progress shows only when enabled and with tracked activities
    Given the following config values are set as admin:
      | enablecompletion   | 1 |
    And completion tracking is "Enabled" for course "C1"
    And the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | completion | completionview |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1          | 1              |
    And I log in as "student2"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    Then I should see "Course 1"
    And I should see "Progress: 0 / 1"
    And I should not see "Course limit reached progress bar will not be displayed."
    And I should see "If more than 10 courses are listed progress bar will not be displayed"

  @javascript
  Scenario: Completion progress shows only when enabled and with tracked activities
    Given the following config values are set as admin:
      | enablecompletion   | 1 |
    And completion tracking is "Enabled" for course "C1"
    And the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | completion | completionview |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1          | 1              |
    And I log in as "student3"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    Then I should see "Course 1"
    And I should not see "Progress: 0 / 1"
    And I should see "Course limit reached progress bar will not be displayed."
    And I should not see "If more than 10 courses are listed progress bar will not be displayed"