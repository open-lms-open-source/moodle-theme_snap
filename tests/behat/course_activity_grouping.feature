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
# @package    theme_snap
# @copyright  Copyright (c) 2026 Open LMS.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course
Feature: When the moodle theme is set to Snap, activities display their assigned grouping on the course page.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | initsections |
      | Course 1 | C1        | 0        | 1         | 1            |
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | GI1      |
    And the following "groupings" exist:
      | name         | course | idnumber |
      | TestGrouping | C1     | GGI1     |
    And the following "grouping groups" exist:
      | grouping | group |
      | GGI1     | GI1   |
    And the following "activities" exist:
      | activity | course | idnumber | name        | intro             | section | groupmode | grouping |
      | assign   | C1     | assign1  | Test assign | Test description | 1       | 1         | GGI1     |

  @javascript
  Scenario: Teacher sees the assigned grouping on the activity card in the course page
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Section 1"
    And I wait until the page is ready
    Then I should see "TestGrouping" in the ".snap-grouping-tag" "css_element"
    And I log out
