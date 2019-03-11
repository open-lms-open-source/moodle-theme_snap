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
# Tests for availability of course tools section.
#
# @package   theme_snap
# @copyright Copyright (c) 2019 Blackboard Inc.
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, a course tools section is available and it should display correctly
  the grade information about the student.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | teacher        |
    And the following "activities" exist:
      | activity | course | idnumber | name  | intro                         | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | A1    | Test assignment description 1 | 1                                   |

  @javascript
  Scenario: Course tools should show a default symbol when the student does not have any grade.
    Given I log in as "student1"
      And I open the personal menu
      And I am on "Course 1" course homepage
      And I follow "Course Dashboard"
      And I should see "-" in the ".progressbar-text" "css_element"
      And I log out

  @javascript
  Scenario: Course tools should display the student grade with the same amount of decimals as Gradebook.
  Given I log in as "student1"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Introduction"
    And I should see "A1"
    And I follow "Not Submitted"
    And I follow "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student1 submission |
    And I press "Save changes"
    And I follow "Submit assignment"
    And I press "Continue"
    And I log out
   Then I log in as "student2"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Introduction"
    And I should see "A1"
    And I follow "Not Submitted"
    And I follow "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission |
    And I press "Save changes"
    And I follow "Submit assignment"
    And I press "Continue"
    And I log out
   Then I log in as "teacher1"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I grade the assignment "A1" in course "C1" as follows:
      | username | grade       | feedback                 |
      | student1 | 50.32973    | I'm the teacher feedback |
      | student2 | 50.756      | I'm the teacher feedback |
    And I log out
        # By default, Gradebook displays grades with two decimals numbers.
   Then I log in as "student1"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50.33%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50.33 %" in the "td.column-percentage" "css_element"
    And I log out
   Then I log in as "student2"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50.76%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50.76 %" in the "td.column-percentage" "css_element"
    And I log out
   Then I log in as "admin"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I follow "Gradebook"
    And I follow "Setup"
    And I follow "Course grade settings"
    And I set the field "Overall decimal points" to "0"
    And I click on "Save changes" "button"
    And I log out
   Then I log in as "student1"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50 %" in the "td.column-percentage" "css_element"
    And I log out
   Then I log in as "student2"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "51%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "51 %" in the "td.column-percentage" "css_element"
    And I log out
   Then I log in as "admin"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I follow "Gradebook"
    And I follow "Setup"
    And I follow "Course grade settings"
    And I set the field "Overall decimal points" to "3"
    And I click on "Save changes" "button"
    And I log out
    Then I log in as "student1"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50.330%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50.330 %" in the "td.column-percentage" "css_element"
    And I log out
    Then I log in as "student2"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50.756%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50.756 %" in the "td.column-percentage" "css_element"
    And I log out
   Then I log in as "admin"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I follow "Gradebook"
    And I follow "Setup"
    And I follow "Course grade settings"
    And I set the field "Overall decimal points" to "4"
    And I click on "Save changes" "button"
    And I log out
   Then I log in as "student1"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50.3297%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50.3297 %" in the "td.column-percentage" "css_element"
    And I log out
   Then I log in as "student2"
    And I open the personal menu
    And I am on "Course 1" course homepage
    And I follow "Course Dashboard"
    And I should see "50.7560%" in the ".progressbar-text" "css_element"
    And I follow "Gradebook"
    And I should see "50.7560 %" in the "td.column-percentage" "css_element"
    And I log out