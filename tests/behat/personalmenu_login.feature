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
# Tests for personal menu display on initial login.
#
# @package    theme_snap
# @author     2016 Guy Thomas <gthomas@moodlerooms.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, optionally open the personal menu on login

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And I am on site homepage

  @javascript
  Scenario: User logs in and sees the primary menu
    Given I follow "Log in"
    And I set the field "username" to "teacher1"
    And I set the field "password" to "teacher1"
    And I press "Log in"
    Then "#primary-nav" "css_element" should be visible

  @javascript
  Scenario: User logs in and does not see the primary menu, if option turned off
    Given the following config values are set as admin:
      | personalmenulogintoggle | 0 | theme_snap |
    Given I follow "Log in"
    And I set the field "username" to "teacher1"
    And I set the field "password" to "teacher1"
    And I press "Log in"
    Then "#primary-nav" "css_element" should not be visible

  @javascript
  Scenario: User logs in as guest, no personal menu or login dropdown visible
    Given I follow "Log in"
    And I set the field "username" to "guest"
    And I set the field "password" to "guest"
    And I press "Log in"
    Then "#primary-nav" "css_element" should not exist
    And "#username" "css_element" should not be visible
    And "#password" "css_element" should not be visible
