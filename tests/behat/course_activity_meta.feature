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

@theme @theme_snap
Feature: When the moodle theme is set to Snap, students see meta data against course activities.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | theme |
      | Course 1 | C1 | 0 | 1 | |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Student sees correct meta data against course activities
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate         |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##tomorrow##    |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##next week##   |
      | assign   | C1     | assign3  | Test assignment3 | Test assignment 3 | 1                                   | 1                               | 1       | ##yesterday##   |
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
    And I follow "Not Submitted"
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
