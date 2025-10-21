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
# @copyright  Copyright (c) 2018 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, course layout cannot be changed to all course on one section.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | theme |
      | Course 1 | C1 | 0 | 1 | |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Teacher sees a warning message and is unable to choose incorrect course layout.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I click on "#id_courseformathdr" "css_element"
    Then I should see "Due to its design language, \"Show all sections on one page\" isn't available in Snap."
    And I click on "[name=\"coursedisplay\"]" "css_element"
    Then I should see "Show all sections on one page (Disabled)"

  @javascript
  Scenario: When there is a Course with "Show all sections" in snap, the student does not see the error when entering the section with subsections
    # "Show all sections" is coursedisplay = 0, "Show one section per page" is coursedisplay = 1
    Given the following "courses" exist:
      | fullname | shortname| category | format | coursedisplay |
      | Course 2 | C2       | 0        | topics | 0             |
    And the following "course enrolments" exist:
      | user | course | role    |
      | student1 | C2 | student |
    And the following "activities" exist:
      | activity   | name        | course | idnumber    | section |
      | subsection | Subsection1 | C2     | Subsection1 | 0       |
      | assign     | Assign1     | C2     | assign11    | 0       |
    And I log in as "student1"
    And I am on "Course 2" course homepage
    Then I wait until the page is ready
    And I should see "Introduction"
    And I should see "Assign1"