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
# Tests Snap Button for creating subsections.
#
# @package    theme_snap
# @author     Dayana Pardo <dayana.pardo@openlms.net>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course
Feature: The Snap button for creating subsections works with editing turned off.
  As an admin

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | theme | initsections | numsections |
      | Course 1 |    C1     |    0     |     1     |       |       0      |      1      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name                   | course | idnumber    | section | duedate          |
      | subsection | Subsection1            | C1     | Subsection1 | 0       |                  |
      | assign     | Assign1 in Subsection1 | C1     | assign11    | 2       | ## 2026-05-01 ## |
    And I enable "subsection" "mod" plugin

  @javascript
  Scenario: The teacher can see the create subsections button, with editing turned off.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode off
    Then I wait until the page is ready
    And I should see "Subsection1"
    And ".btn-add-subsection" "css_element" should exist
    Then I click on "#snap-create-subsection-0 .btn-add-subsection" "css_element"
    And I should see "New subsection"

  @javascript
  Scenario: The snap button to create subsections is only visible if the user has the mod/subsection:addinstance capability.
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | mod/subsection:addinstance  | Prevent   | editingteacher | Course       | C1        |
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode off
    Then I wait until the page is ready
    And ".btn-add-subsection" "css_element" should not exist
    And I log out
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | mod/subsection:addinstance  | Allow      | editingteacher | Course       | C1        |
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode off
    Then I wait until the page is ready
    And ".btn-add-subsection" "css_element" should exist

  Scenario: The snap button to create subsections is only visible if the module is enabled.
    Given I log in as "admin"
    And I disable "subsection" "mod" plugin
    And I am on "Course 1" course homepage
    And I should not see "Add a subsection"

  Scenario: Subsections must display a trove of course info, an assignment with a due date must show it.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode off
    And I should see "Subsection1"
    And I should see "Assign1 in Subsection1"
    Then I should see "Due 1 May 2026"