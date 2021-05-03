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
# Tests for personal menu course feedback in course cards.
#
# @package    theme_snap
# @copyright  Copyright (c) 2017 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_personalmenu
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu where they can find
  a list of courses they are enrolled in with a feedback available or grade.

  Background:
    Given the following "courses" exist:
      | fullname        | shortname | category | groupmode | visible |
      | Course 1        | C1        | 0        | 1         | 1       |
    And the following "users" exist:
      | username | firstname    | lastname | email                |
      | student1 | Student      | 1        | student1@example.com |
      | teacher1 | Teacher      | 1        | teacher1@example.com |

  @javascript
  Scenario Outline: Enrolled courses show grade in personal menu when enabled.
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following config values are set as admin:
      | showcoursegradepersonalmenu | <showgrades> | theme_snap |
    And I log in as "student1"
    And I open the personal menu
    Then I should see "Course 1"
    And "a.coursegrade" "css_element" should not exist
    And I close the personal menu
    And the following "activities" exist:
      | activity | course | idnumber | name              | intro                       |
      | assign   | C1     | assign1  | Test assignment 1 | Test assignment description |
    And I grade the assignment "Test assignment 1" in course "C1" as follows:
      | username | grade |
      | student1 | 70    |
    When I open the personal menu
    Then <finalstep>
    Examples:
    | showgrades | finalstep                                                |
    | 1          | I should see "70" in the "div.coursegrade" "css_element" |
    | 0          | "div.coursegrade" "css_element" should not exist         |
