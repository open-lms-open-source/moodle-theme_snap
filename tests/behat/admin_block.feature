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
# Tests for visibility of admin block by user type and page.
#
# @package    theme_snap
# @copyright  2015 Guy Thomas <gthomas@moodlerooms.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, the admin block will only be shown when appropriate.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
      | thememobile | snap |
      | defaulthomepage | 1 |
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
  Scenario: Student does not see admin block on any page.
    Given I log in with snap as "student1"
    # Check site page.
    And I am on site homepage
   Then "#admin-menu-trigger" "css_element" should not exist
    # Check dashboard page.
    And I am on homepage
   Then "#admin-menu-trigger" "css_element" should not exist
    And I follow "Menu"
    And I follow "Course 1"
   Then "#admin-menu-trigger" "css_element" should not exist
    And I follow "Menu"
    And I follow "View your profile"
   Then "#admin-menu-trigger" "css_element" should not exist

  @javascript
  Scenario: Teacher does not see admin block on any page, except course page.
    Given I log in with snap as "teacher1"
    # Check site page.
    And I am on site homepage
    Then "#admin-menu-trigger" "css_element" should not exist
    # Check dashboard page.
    And I am on homepage
    Then "#admin-menu-trigger" "css_element" should not exist
    And I follow "Menu"
    And I follow "Course 1"
    Then "#admin-menu-trigger" "css_element" should exist
    And I follow "Menu"
    And I follow "View your profile"
    Then "#admin-menu-trigger" "css_element" should not exist

  @javascript
  Scenario: Admin sees admin block on all pages, except profile page.
    Given I log in with snap as "admin"
    # Check site page.
    And I am on site homepage
    Then "#admin-menu-trigger" "css_element" should exist
    # Check dashboard page.
    And I am on homepage
    Then "#admin-menu-trigger" "css_element" should exist
    And I follow "Menu"
    And I follow "Course 1"
    Then "#admin-menu-trigger" "css_element" should exist
    And I follow "Menu"
    And I follow "View your profile"
    Then "#admin-menu-trigger" "css_element" should not exist