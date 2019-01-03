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
# @copyright  Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, students and teachers can find in their personal menu a list of deadlines
  for activities and the submission / attempt status thereof.

  Background:
    Given the following config values are set as admin:
      | allowcoursethemes | 1 |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | theme | enablecompletion |
      | Course 1 | C1        | 0        | 1         |       | 0                 |
      | Course 2 | C2        | 0        | 1         | snap  | 1                 |

    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
      | student1 | C1 | student |
      | student1 | C2 | student |
      | student2 | C2 | student |

  @javascript
  Scenario: Student sees correct submission status against deadlines when 1 out of 2 assignments are submitted by student.
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate         |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##tomorrow##    |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##next week##   |
    And I log in as "student1"
    And I open the personal menu
    And I should see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:nth-of-type(2)" "css_element"
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"
    And I follow "Not Submitted"
    When I follow "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I follow "Submit assignment"
    And I press "Continue"
    And I open the personal menu
    And I should see "Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should not see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:nth-of-type(2)" "css_element"

  @javascript
  Scenario: Teacher sees no submission status data against deadlines.
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate       |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##tomorrow##  |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##next week## |
    And I log in as "teacher1"
    And I open the personal menu
    And I should not see "Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should not see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should not see "Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:nth-of-type(2)" "css_element"
    And I should not see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:nth-of-type(2)" "css_element"

  @javascript
  Scenario: Student sees correct submission status when the platform theme is different from snap and the course is forced to snap
    Given the following config values are set as admin:
      | theme | clean |
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate                      |
      | assign   | C2     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##tomorrow##    |
      | assign   | C2     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##next week##   |
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I follow "Topic 1"
    And I wait until "#section-1" "css_element" is visible
    And I should see "Test assignment1"
    And I follow "Not Submitted"
    When I follow "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student submission |
    And I press "Save changes"
    And I follow "Submit assignment"
    And I press "Continue"
    And I open the personal menu
    And I should see "Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should not see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:first-of-type" "css_element"
    And I should see "Not Submitted" in the "#snap-personal-menu-deadlines div.snap-media-object:nth-of-type(2)" "css_element"

  @javascript
  Scenario: Extended deadline dates take priority over deadlines.
    Given the following config values are set as admin:
      | theme | snap |
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate       |
      | assign   | C2     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##yesterday## |
      | assign   | C2     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##tomorrow##  |
    And deadline for assignment "Test assignment1" in course "C2" is extended to "##next week##" for "student1"
    And I log in as "student1"
    And I open the personal menu
    And I see a personal menu deadline of "##next week##" for "Test assignment1"
    And I see a personal menu deadline of "##tomorrow##" for "Test assignment2"
    And I log out
    # Make sure student2 doesn't see the extension.
    And I log in as "student2"
    And I open the personal menu
    And I do not see a personal menu deadline of "##next week##" for "Test assignment1"
    And I see a personal menu deadline of "##tomorrow##" for "Test assignment2"

  @javascript
  Scenario: Expected completed on activities that do not have due date are shown on deadlines
    Given the following "activities" exist:
      | activity    | name          | intro                       | course | idnumber   | section | completionexpected | duedate |
      | assign      | Assignment 1  | Test assign description 1   | C2     | assign1    | 0       | ##tomorrow##       | ##next week## |
      | forum       | Forum 1       | Test forum description      | C2     | forum1     | 0       | ##tomorrow##       |               |
      | quiz        | Quiz 1        | Test quiz description       | C2     | quiz1      | 0       | ##tomorrow##       |               |
    And I log in as "teacher1"
    And I am on "Course 2" course homepage with editing mode on
    # Set completion for Assignment 1.
    Then I follow "Edit \"Assignment 1\""
    And I expand all fieldsets
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met |
      | id_completionview   | 1                                                 |
      | id_completionexpected_enabled | 1 |
    And I press "Save and return to course"
    # Set completion for Forum 1.
    And I follow "Edit \"Forum 1\""
    And I expand all fieldsets
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met |
      | completionpostsenabled    | 1 |
      | id_completionexpected_enabled | 1 |
    And I press "Save and return to course"
    # Set completion for Quiz 1.
    Then I follow "Edit \"Quiz 1\""
    And I expand all fieldsets
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met |
      | id_completionview   | 1                                                 |
      | id_completionexpected_enabled | 1 |
    And I press "Save and return to course"
    And I log out
    Given I log in as "student2"
    And I open the personal menu
    And I see a personal menu deadline of "##tomorrow##" for "Assignment 1"
    And I see a personal menu deadline of "##next week##" for "Assignment 1"
    And I see a personal menu deadline of "##tomorrow##" for "Forum 1"
    And I see a personal menu deadline of "##tomorrow##" for "Quiz 1"