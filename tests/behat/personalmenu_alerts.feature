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
# @copyright  Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu which features an
  alerts column showing them things that have recently happened (depending on how the message outputs have been
  configured).

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
  Scenario: Alerts are visible in personal menu when opened after messages are sent and the message processor is enabled.
    Given the message processor "badge" is disabled
    And the following config values are set as admin:
      | personalmenulogintoggle | 0 | theme_snap |
    And I log in as "student1" (theme_snap)
    And I open the personal menu
    # The alerts section should not be present if the message processor is not enabled.
    Then ".alert_stream" "css_element" should not exist
    And the message processor "badge" is enabled
    And I am on site homepage
    And I open the personal menu
    Then ".alert_stream" "css_element" should exist
    And I wait until ".message_badge_empty" "css_element" is visible
    Then I should see "You have no unread alerts."
    And I send "Test message!" message to "Teacher 1" user (theme_snap)
    And I log out (theme_snap)
    And I log in as "teacher1" (theme_snap)
    And ".message_badge_count" "css_element" should exist
    And I open the personal menu
    And I wait until ".message_badge_message_text" "css_element" is visible
    And I should see "New message from Student 1"

  @javascript
  Scenario: Alerts are visible in personal menu on login after messages are sent and the message processor is enabled.
    Given the message processor "badge" is disabled
    And I log in as "student1", keeping the personal menu open
    # The alerts section should not be present if the message processor is not enabled.
    Then ".alert_stream" "css_element" should not exist
    And the message processor "badge" is enabled
    And I log out (theme_snap)
    And I log in as "student1", keeping the personal menu open
    Then ".alert_stream" "css_element" should exist
    And I wait until ".message_badge_empty" "css_element" is visible
    Then I should see "You have no unread alerts."
    And I send "Test message!" message to "Teacher 1" user (theme_snap)
    And I log out (theme_snap)
    And I log in as "teacher1", keeping the personal menu open
    And I wait until ".message_badge_message_text" "css_element" is visible
    And I should see "New message from Student 1"

  @javascript @testing
  Scenario: Alerts are visible in personal menu when user is on course page warning no guest access.
    Given the message processor "badge" is enabled
    And the following config values are set as admin:
      | personalmenulogintoggle | 0 | theme_snap |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student2 | Student | 2 | student1@example.com |
    And I am on the course "C1"
    And I set the following fields to these values:
      | Username | student2 |
      | Password | student2 |
    And I press "Log in"
    And I should see "You can not enrol yourself in this course"
    And I open the personal menu
    Then ".alert_stream" "css_element" should exist
    And I wait until ".message_badge_empty" "css_element" is visible
    Then I should see "You have no unread alerts."

