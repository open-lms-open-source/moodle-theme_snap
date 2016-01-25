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
# @package    theme_snap
# @copyright  Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @_bug_phantomjs
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu which features a
  grades / grading column showing them things that have recently had feedback or have recently been submitted.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
      | thememobile | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: No assignments submitted or graded.
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment description 1 | 1 |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment description 2 | 1 |
    And I log in with snap as "teacher1"
    And I follow "Menu"
   Then I should see "You have no submissions to grade."
    And I follow "Log out"
    And I log in with snap as "student1"
    And I follow "Menu"
    And I should see "You have no recent feedback."
    And I should see "My grades"

  @javascript
  Scenario: 1 out of 2 assignments are submitted by student and graded by teacher.
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment description 1 | 1 | 1 | 1 |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment description 2 | 1 | 1 | 1 |

    And I log in with snap as "student1"
    And I follow "Menu"
    And I should see "My grades"
    And I follow "Course"
    And I wait until the page is ready
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"

    # Note - we can not follow assignments or anything that is searchable using the course quick search tool.
    # This is because everything that is searchable lives as a hidden link towards to the top of the DOM.
    # Hidden links break "I follow" instructions for visible links with the same text further down the DOM.
    # Swapped "Test assignment1" follow link for "Not Submitted" which is less specific but achieves the same
    # https://tracker.moodle.org/browse/MDL-51669

    #And I follow "Test assignment1"

    And I follow "Not Submitted"
   When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I follow "Menu"
    And I follow "Log out"
    And I log in with snap as "teacher1"
    And I follow "Menu"
    And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
    # The above waits until the snap personal menu column is loaded.
   Then I should see "1 of 1 Submitted, 1 Ungraded"
    And I follow "Course 1 / Test assignment1"
    And I follow "View/grade all submissions"
    And I click on "Grade Student 1" "link" in the "Student 1" "table_row"
   When I set the following fields to these values:
      | Grade out of 100 | 50 |
      | Feedback comments | I'm the teacher feedback |
    And I press "Save changes"
    And I follow "Menu"
    And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
    # The above waits until the snap personal menu column is loaded.
   Then I should see "You have no submissions to grade."
    And I follow "Log out"
    And I log in with snap as "student1"
    And I follow "Menu"
    And I should see "Test assignment1" in the "#snap-personal-menu-graded" "css_element"
    And I should see "My grades"