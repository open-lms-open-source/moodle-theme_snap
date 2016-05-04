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
# Tests for course favoriting in the Snap personal menu.
#
# @package    theme_snap
# @copyright  Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, students and teachers favorite courses in the personal menu.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
      | Course 2 | C2 | 0 | 1 |
      | Course 3 | C3 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student1 | C2     | student        |
      | student1 | C3     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | teacher1 | C3     | editingteacher |

  @javascript
  Scenario: User can favorite / unfavorite courses.
    Given I log in as "student1" (theme_snap)
    And I open the personal menu
    Then Favorite toggle exists for course "C1"
    And Favorite toggle exists for course "C2"
    And Favorite toggle exists for course "C3"
    And Course card "C1" appears before "C2"
    And Course card "C2" appears before "C3"
    And I toggle course card favorite "C3"
    Then Course card "C3" is favorited
    And I reload the page
    And I open the personal menu
    Then Course card "C3" appears before "C1"
    And Course card "C1" appears before "C2"
    # Log out and log in as teacher (make sure they can't see students favorites)
    And I log out (theme_snap)
    And I log in as "teacher1" (theme_snap)
    And I open the personal menu
    Then Favorite toggle exists for course "C1"
    And Favorite toggle exists for course "C2"
    And Favorite toggle exists for course "C3"
    And Course card "C1" is not favorited
    And Course card "C2" is not favorited
    And Course card "C3" is not favorited
    # Test favoriting / unfavoriting
    And I toggle course card favorite "C2"
    Then Course card "C2" is favorited
    And I toggle course card favorite "C2"
    Then Course card "C2" is not favorited

