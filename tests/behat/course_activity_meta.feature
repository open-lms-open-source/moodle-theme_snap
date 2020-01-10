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
# @copyright  Copyright (c) 2017 Blackboard Inc.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course
Feature: When the moodle theme is set to Snap, students see meta data against course activities.

  Background:
    Given the following config values are set as admin:
      | enableoutcomes | 1 |
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | theme |
      | Course 1 | C1 | 0 | 1 | |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |

  @javascript
  Scenario Outline: Student sees correct meta data against course activities
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate         |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##tomorrow##    |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##next week##   |
      | assign   | C1     | assign3  | Test assignment3 | Test assignment 3 | 1                                   | 1                               | 1       | ##yesterday##   |
    And I log in as "admin"
    And the following config values are set as admin:
      | coursepartialrender | <Option> | theme_snap |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"
    And assignment entitled "Test assignment1" shows as not submitted in metadata
    And assignment entitled "Test assignment1" is not overdue in metadata
    And assignment entitled "Test assignment1" does not have feedback metadata
    And assignment entitled "Test assignment2" shows as not submitted in metadata
    And assignment entitled "Test assignment2" is not overdue in metadata
    And assignment entitled "Test assignment2" does not have feedback metadata
    And assignment entitled "Test assignment3" shows as not submitted in metadata
    And assignment entitled "Test assignment3" is overdue in metadata
    And assignment entitled "Test assignment3" does not have feedback metadata
    And I am on activity "assign" "Test assignment1" page
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And assignment entitled "Test assignment1" shows as submitted in metadata
    And assignment entitled "Test assignment2" shows as not submitted in metadata
    And assignment entitled "Test assignment3" shows as not submitted in metadata
    And deadline for assignment "Test assignment3" in course "C1" is extended to "##next week##" for "student1"
    And I reload the page
    And assignment entitled "Test assignment3" is not overdue in metadata
    And I log out
    And I log in as "teacher1"
    And I grade the assignment "Test assignment1" in course "C1" as follows:
      | username | grade | feedback                 |
      | student1 | 50    | I'm the teacher feedback |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"
    And assignment entitled "Test assignment1" has feedback metadata
    And assignment entitled "Test assignment2" does not have feedback metadata
    And assignment entitled "Test assignment3" does not have feedback metadata
    And Activity "assign" "Test assignment1" is deleted
    And Activity "assign" "Test assignment2" is deleted
    And Activity "assign" "Test assignment3" is deleted
    Examples:
      | Option     |
      | 0          |
      | 1          |

  @javascript
  Scenario Outline: Student that belongs to a specific group sees correct meta data against course activities
    And the following "users" exist:
      | username | firstname | lastname | email         |
      | student3 | Student   | 3 | student3@example.com |
      | student4 | Student   | 4 | student4@example.com |
    And the following "course enrolments" exist:
      | user | course | role    |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And the following "groups" exist:
      | name     | course | idnumber |
      | G1       | C1     | GI1      |
      | G2       | C1     | GI2      |
    And the following "group members" exist:
      | user     | group |
      | student1 | GI1   |
      | student2 | GI1   |
      | student3 | GI2   |
      | student4 | GI2   |
    And I log in as "admin"
    And the following config values are set as admin:
      | coursepartialrender | <Option> | theme_snap |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    # Create assignment 1.
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                  | Test assign  |
      | Description                      | Description  |
      | Online text                      | 1            |
      | Group mode                       | 1            |
      | Students submit in groups        | Yes          |
      | Require all group members submit | No           |
    And I should see "Test assign"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assign"
    And assignment entitled "Test assign" shows as not submitted in metadata
    And I am on activity "assign" "Test assign" page
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And assignment entitled "Test assign" shows as submitted in metadata
    And I log out
    # Now we login as student2 and it must appear as submitted since is in the same group with student1.
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assign"
    And assignment entitled "Test assign" shows as submitted in metadata
    And I log out
    Examples:
      | Option     |
      | 0          |
      | 1          |

  @javascript
  Scenario Outline: Student sees correct feedback with multiple outcomes configured
    Given I log in as "admin"
    And the following config values are set as admin:
      | coursepartialrender | <Option> | theme_snap |
    And I log out
    Then I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Legacy outcomes" in current page administration
    And I follow "Edit outcomes"
    And I press "Add a new outcome"
    And I follow "Add a new scale"
    And I set the following fields to these values:
      | Name | 1337dom scale |
      | Scale | Noob, Nub, 1337, HaXor |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Legacy outcomes" in current page administration
    And I follow "Edit outcomes"
    And I press "Add a new outcome"
    And I set the following fields to these values:
      | Full name | M8d skillZ! |
      | Short name | skillZ! |
      | Scale | 1337dom scale |
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
      | M8d skillZ! | 1 |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I click on "//a[@class='mod-link']//p[text()='Test assignment name']" "xpath_element"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student1 submission |
    And I press "Save changes"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I click on "//a[@class='mod-link']//p[text()='Test assignment name']" "xpath_element"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student2 submission |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I click on "//a[@class='mod-link']//p[text()='Test assignment name']" "xpath_element"
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the following fields to these values:
      | Grade out of 100 | 50.0 |
      | M8d skillZ! | 1337 |
      | Feedback comments | I'm the teacher first feedback |
    And I press "Save changes"
    And I press "Ok"
    And I click on "Edit settings" "link"
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "View all submissions" in current page administration
    Then I click on "Quick grading" "checkbox"
    And I set the field "User grade" to "60.0"
    And I press "Save all quick grading changes"
    And I should see "The grade changes were saved"
    And I press "Continue"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And assignment entitled "Test assignment name" has feedback metadata
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And assignment entitled "Test assignment name" does not have feedback metadata
    Examples:
      | Option     |
      | 0          |
      | 1          |
