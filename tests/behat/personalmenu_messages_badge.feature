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
# Tests for Snap personal menu alerts.
#
# @package    theme_snap
# @copyright  Copyright (c) 2017 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @theme_snap_messages_badge
Feature: When the moodle theme is set to Snap, students and teachers can see the badge have a counter with the amount
  of messages received when alerts are disabled

  Background:
    Given I am using Joule
    And the following config values are set as admin:
      | theme | snap |
    And the following config values are set as admin:
      | message_provider_moodle_instantmessage_loggedoff | badge | message |
    And the following "courses" exist:
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
  Scenario: Message badge count is shown when alerts are enabled for a user in snap.
    Given the message processor "badge" is enabled
    And I change viewport size to "large"
    And I log in as "student1" (theme_snap)
    And I send "Test message!" message to "Teacher 1" user (theme_snap)
    And I log out (theme_snap)
    And I log in as "teacher1" (theme_snap)
    Then ".message_badge_count" "css_element" should exist
    # Placeholder should not exist.
    Then ".conversation_badge_count" "css_element" should not exist

  @javascript
  Scenario: Conversation badge count is shown when alerts are disabled for a user in snap.
    Given the message processor "badge" is disabled
    # Placeholder should exist but be hidden.
    And I log in as "teacher1" (theme_snap)
    Then ".conversation_badge_count.hidden" "css_element" should exist
    And I log out (theme_snap)
    And I change viewport size to "large"
    And I log in as "student1" (theme_snap)
    And I send "Test message!" message to "Teacher 1" user (theme_snap)
    And I log out (theme_snap)
    And I log in as "teacher1" (theme_snap)
    # Placeholder should exist but not be hidden.
    Then ".conversation_badge_count" "css_element" should exist

  @javascript
  Scenario: No badge count is shown when alerts are disabled and snap messages setting is disabled for a user in snap.
    Given the message processor "badge" is disabled
    And the following config values are set as admin:
      | messagestoggle | 0 | theme_snap |
    And I log in as "teacher1" (theme_snap)
    Then ".conversation_badge_count.hidden" "css_element" should not exist