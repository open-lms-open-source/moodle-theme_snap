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
# Tests for Snap personal menu conversation badge count.
#
# @package    theme_snap
# @copyright  Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, students and teachers have a conversation badge count and messages section.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Snap user can see conversation count and messages.
    Given I log in as "teacher1"
    Then ".conversation_badge_count.hidden" "css_element" should exist
    And I log out
    And I change viewport size to "large"
    And I log in as "student1"
    And I send "Test message!" message to "Teacher 1" user
    And I log out
    And I log in as "teacher1"
    Then ".conversation_badge_count" "css_element" should exist
    And I open the personal menu
    And I should see "Test message!" in the "#snap-personal-menu-messages" "css_element"

  @javascript
  Scenario: No badge count is shown when snap messages setting is disabled for a user in snap.
    Given the following config values are set as admin:
      | messagestoggle | 0 | theme_snap |
    And I log in as "teacher1"
    Then ".conversation_badge_count.hidden" "css_element" should not exist
    And I open the personal menu
    And "#snap-personal-menu-messages" "css_element" should not exist
