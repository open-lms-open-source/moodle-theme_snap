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
# Tests for Snap personal menu.
#
#
# @package    theme_snap
# @author     Guillermo Alvarez
# @copyright  Blackboard Ltd 2017
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, students can open a personal menu which features a link to
  see feedback of their assignments on a course.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: The user should see the feedback link on the personal menu when he has a graded assignment.
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                         | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section |
      | assign   | C1     | assign1  | Test assignment1     | Test assignment description 1 | 1                                   | 1                               | 1       |
    And I log in as "student1" (theme_snap)
    And I open the personal menu
    And I should not see "Feedback available" in the ".courseinfo" "css_element"
    And I follow "Course 1"
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"
    And I follow "Not Submitted"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out (theme_snap)
    And I log in as "teacher1" (theme_snap)
    And I open the personal menu
    And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
    # The above waits until the snap personal menu column is loaded.
    Then I should see "1 of 1 Submitted, 1 Ungraded"
    And I follow "Test assignment1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Student 1" "table_row"
    When I set the following fields to these values:
      | Grade out of 100  | 50                       |
      | Feedback comments | I'm the teacher feedback |
    And I press "Save changes"
    And I press "Ok"
    And I follow "Test assignment1"
    And I log out (theme_snap)
    Then I log in as "student1" (theme_snap)
    And I open the personal menu
    And I should see "Feedback available" in the ".courseinfo" "css_element"