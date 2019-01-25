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
# @copyright  Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @_bug_phantomjs
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu which shows a
  grades / grading column showing them things that have recently had feedback or have recently been submitted.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | groupmodeforce |
      | Course 1 | C1        | 0        | 1         | 0              |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
      | teacher3 | Teacher   | 3        | teacher3@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | teacher3 | C1     | teacher        |
      | student1 | C1     | student        |

  @javascript
  Scenario: No assignments submitted or graded.
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                         | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment1     | Test assignment description 1 | 1                                   |
      | assign   | C1     | assign2  | Test assignment2     | Test assignment description 2 | 1                                   |
    And I log in as "teacher1"
    And I open the personal menu
   Then I should see "You have no submissions to grade."
    And I follow "Log out"
    And I log in as "student1"
    And I open the personal menu
    And I should see "You have no recent feedback."
    And I should see "Feedback"

  @javascript
  Scenario: 1 out of 2 assignments are submitted by student and graded by teacher.
    Given the following "activities" exist:
      | activity | course | idnumber | name                 | intro                       | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment description 1 | 1 | 1 | 1 |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment description 2 | 1 | 1 | 1 |

    And I log in as "student1"
    And I open the personal menu
    And I should see "Feedback"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"

    # Note - we can not follow assignments or anything that is searchable using the course quick search tool.
    # This is because everything that is searchable lives as a hidden link towards to the top of the DOM.
    # Hidden links break "I follow" instructions for visible links with the same text further down the DOM.
    # Swapped "Test assignment1" follow for "Not Submitted" which is less specific but achieves the same
    # https://tracker.moodle.org/browse/MDL-51669

    #And I follow "Test assignment1"

    And I follow "Not Submitted"
   When I follow "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I follow "Submit assignment"
    And I press "Continue"
    And I log out
    And I log in as "teacher1"
    And I open the personal menu
    And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
    # The above waits until the snap personal menu column is loaded.
   Then I should see "1 of 1 Submitted, 1 Ungraded"
    And I grade the assignment "Test assignment1" in course "C1" as follows:
      | username | grade | feedback                 |
      | student1 | 50    | I'm the teacher feedback |
    And I close the personal menu
    And I open the personal menu
    And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
    # The above waits until the snap personal menu column is loaded.
   Then I should see "You have no submissions to grade."
    And I follow "Log out"
    And I log in as "student1"
    And I open the personal menu
    And I should see "Test assignment1" in the "#snap-personal-menu-graded" "css_element"
    And I should see "Feedback"

    @javascript
    Scenario: Show grading in the personal menu only to the teachers with the proper access to the courses or the groups.
      When I log in as "admin"
      And I close the personal menu
      And I navigate to "Define roles" node in "Site administration > Users > Permissions"
      And I follow "Non-editing teacher"
      And I set the following system permissions of "Teacher" role:
        | capability                                   | permission |
        | moodle/site:accessallgroups                  | Prevent    |
        | moodle/course:viewhiddenactivities           | Prevent    |
        | moodle/course:ignoreavailabilityrestrictions | Prevent    |
      And I log out
      And I log in as "teacher1"
      And I am on "Course 1" course homepage with editing mode on

      Given the following "groups" exist:
        | name     | course | idnumber |
        | G1       | C1     | GI1      |
        | G2       | C1     | GI2      |
      And the following "group members" exist:
        | user     | group |
        | student1 | GI1   |
        | teacher2 | GI1   |
        | teacher3 | GI2   |

      # Set restriction to assignment 1.
      And I add a "Assignment" to section "1" and I fill the form with:
        | Assignment name     | A1 |
        | Description         | x  |
        | Online text         | 1  |
        | Group mode          | 1  |
      And I should see "A1"
      And I follow "Edit \"A1\""
      And I expand all fieldsets
      And I click on "Add restriction..." "button"
      And I click on "Group" "button" in the "Add restriction..." "dialogue"
      Then ".availability-item .availability-eye img" "css_element" should exist
      And I click on ".availability-item .availability-eye img" "css_element"
      And I set the field with xpath "//select[@name='id']" to "G1"
      And I press "Save and display"
      And I am on "Course 1" course homepage with editing mode on

      # Set restriction to assignment 2.
      And I add a "Assignment" to section "1" and I fill the form with:
        | Assignment name     | A2 |
        | Description         | x  |
        | Online text         | 1  |
        | Group mode          | 1  |
      And I should see "A2"
      And I follow "Edit \"A2\""
      And I expand all fieldsets
      And I click on "Add restriction..." "button"
      And I click on "Group" "button" in the "Add restriction..." "dialogue"
      And I click on ".availability-item .availability-eye img" "css_element"
      And I set the field with xpath "//select[@name='id']" to "G2"
      And I press "Save and display"
      And I log out

      #Log as student from group 1 to submit an assignment.
      And I log in as "student1"
      And I open the personal menu
      And I should see "Feedback"
      And I am on "Course 1" course homepage
      And I follow "Topic 1"
      And I should see "A1"
      And I follow "Not Submitted"
      When I follow "Add submission"
      And I set the following fields to these values:
        | Online text | I'm the student submission |
      And I press "Save changes"

      # Check as teacher from group 2 that aren't submissions to grade.
      And I log out
      And I log in as "teacher3"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "You have no submissions to grade."
      # Check as teacher from group 1 that exists one submission to grade.
      And I log out
      And I log in as "teacher2"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "1 of 1 Submitted, 1 Ungraded"
      # Check as teacher with full permissions that exists one submission to grade.
      And I log out
      And I log in as "teacher1"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
      Then I should see "1 of 1 Submitted, 1 Ungraded"

    @javascript
    Scenario: Grading in the personal menu should show the correct information depending of teachers group capabilities.
     When I log in as "admin"
      And I close the personal menu
      And I navigate to "Define roles" node in "Site administration > Users > Permissions"
      And I follow "Non-editing teacher"
      And I set the following system permissions of "Teacher" role:
        | capability                                   | permission |
        | moodle/site:accessallgroups                  | Prevent    |
        | moodle/course:viewhiddenactivities           | Prevent    |
        | moodle/course:ignoreavailabilityrestrictions | Prevent    |
      And I log out
      And I log in as "teacher1"
      And I am on "Course 1" course homepage with editing mode on

    Given the following "users" exist:
        | username | firstname | lastname | email                |
        | student2 | Student   | 2        | student2@example.com |
      And the following "course enrolments" exist:
        | user     | course | role           |
        | student2 | C1     | student        |
      And the following "groups" exist:
        | name     | course | idnumber |
        | G1       | C1     | GI1      |
        | G2       | C1     | GI2      |
      And the following "group members" exist:
        | user     | group |
        | student1 | GI1   |
        | student2 | GI2   |
        | teacher2 | GI1   |
        | teacher3 | GI2   |

      # Create assignment 1.
      And I add a "Assignment" to section "1" and I fill the form with:
        | Assignment name           | A1   |
        | Description               | x    |
        | Online text               | 1    |
        | Group mode                | 1    |
        | Students submit in groups | Yes  |
      And I should see "A1"
      And I log out

      # Login as student from group 1 to submit an assignment.
      And I log in as "student1"
      And I open the personal menu
      And I am on "Course 1" course homepage
      And I follow "Topic 1"
      And I should see "A1"
      And I follow "Not Submitted"
     When I follow "Add submission"
      And I set the following fields to these values:
        | Online text | I'm the student1 submission |
      And I press "Save changes"
      And I log out

      And I log in as "teacher2"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "1 of 1 Submitted, 1 Ungraded"
      And I log out

      And I log in as "teacher3"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
      Then I should see "You have no submissions to grade."
      And I log out

      And I log in as "teacher1"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "1 of 2 Submitted, 1 Ungraded"
      And I log out

      #Log as student from group 2 to submit an assignment.
      And I log in as "student2"
      And I open the personal menu
      And I am on "Course 1" course homepage
      And I follow "Topic 1"
      And I should see "A1"
      And I follow "Not Submitted"
     When I follow "Add submission"
      And I set the following fields to these values:
        | Online text | I'm the student2 submission |
      And I press "Save changes"
      And I log out

      And I log in as "teacher2"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "1 of 1 Submitted, 1 Ungraded"
      And I log out

      And I log in as "teacher3"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "1 of 1 Submitted, 1 Ungraded"
      And I log out

      And I log in as "teacher1"
      And I open the personal menu
      And I wait until "#snap-personal-menu-grading[data-content-loaded=\"1\"]" "css_element" is visible
     Then I should see "2 of 2 Submitted, 2 Ungraded"
      And I log out