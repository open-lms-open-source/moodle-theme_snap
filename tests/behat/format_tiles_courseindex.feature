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
# Tests that clicking a label (anchor link) from the course index in Snap + tiles
#
# @package   theme_snap
# @copyright Copyright (c) 2026 Open LMS
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @javascript
Feature: Clicking a label from the course index in tiles format dismisses the overlay

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname    | shortname | format | numsections |
      | Course Test | C1        | tiles  | 2           |
    And the following "activities" exist:
      | activity | name       | intro              | course | idnumber | section |
      | page     | Test page  | Page description   | C1     | page1    | 1       |
      | label    | Test label | Label description  | C1     | label1   | 1       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | config                 | value    | plugin       |
      | assumedatastoreconsent | 1        | format_tiles |
      | reopenlastsection      | 0        | format_tiles |
      | usejavascriptnav       | 1        | format_tiles |
      | modalmodules           | page     | format_tiles |
      | modalresources         | pdf,html | format_tiles |

  Scenario: Student - clicking a label from the course index while a tile is open dismisses the overlay
    Given I log in as "student1"
    And I am on the course main page for "C1"
    And I change window size to "large"
    And I click on "a.tile-link[data-section='1']" "css_element"
    And I wait until the page is ready
    Then "#format_tiles_overlay" "css_element" should be visible
    When I click on "nav#courseindex a.courseindex-link[data-anchor='true']" "css_element"
    And I wait until the page is ready
    Then "#format_tiles_overlay" "css_element" should not be visible

  Scenario: Teacher (editing off) - clicking a label from the course index while a tile is open dismisses the overlay
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I change window size to "large"
    And I click on "a.tile-link[data-section='1']" "css_element"
    And I wait until the page is ready
    Then "#format_tiles_overlay" "css_element" should be visible
    When I click on "nav#courseindex a.courseindex-link[data-anchor='true']" "css_element"
    And I wait until the page is ready
    Then "#format_tiles_overlay" "css_element" should not be visible
