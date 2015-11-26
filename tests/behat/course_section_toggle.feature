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
# Tests for toggle course section visibility in non edit mode in snap.
#
# @package    theme_snap
# @copyright  2015 Guy Thomas <gthomas@moodlerooms.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, teachers can toggle the visibility of course sections in read mode and
  edit mode.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1 | 0 | topics |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | admin | C1 | editingteacher |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: In read mode, teacher hides section.
    Given I log in with snap as "teacher1"
    And I follow "Menu"
    And I follow "Course"
    And I wait until the page is ready
    And I follow "Topic 2"
   Then "#section-2" "css_element" should exist
    And "#section-2.hidden" "css_element" should not exist
    And I click on "#section-2 .editing_showhide" "css_element"
   Then "#section-2.hidden" "css_element" should exist

  @javascript
  Scenario: In read mode, student cannot hide section.
    Given I log in with snap as "student1"
    And I follow "Menu"
    And I follow "Course"
    And I wait until the page is ready
    And I follow "Topic 2"
    Then ".section-2 .editing_showhide" "css_element" should not exist
