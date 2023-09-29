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
# @author     2016 Guy Thomas
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_personalmenu
Feature: When the moodle theme is set to Snap,
          users can open and close the personal menu,
          and optionally open the personal menu on login

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And I am on site homepage

  @javascript
  Scenario: User logs in and does not see the personal menu, if option turned off
    Given the following config values are set as admin:
      | personalmenulogintoggle | 0 | theme_snap |
    Given I follow "Log in"
    And I set the field "username" to "teacher1"
    And I set the field "password" to "teacher1"
    And I press "Log in"
    Then "#snap-pm" "css_element" should not be visible

  @javascript
  Scenario: User logs in as guest, no personal menu or login dropdown visible
    Given I follow "Log in"
    And I set the field "username" to "guest"
    And I set the field "password" to "guest"
    And I press "Log in"
    Then "#snap-pm" "css_element" should not be visible
    And "#username" "css_element" should not be visible
    And "#password" "css_element" should not be visible

  @javascript
  Scenario: User logs in and sees the personal menu, then closes it and re-opens without changing section
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | teacher1  | C1     | editingteacher |
    And I follow "Log in"
    And I set the field "username" to "teacher1"
    And I set the field "password" to "teacher1"
    And I press "Log in"
    Then "#snap-pm" "css_element" should be visible
    And I am on "Course 1" course homepage
    And I follow "Introduction"
    And "#section-0" "css_element" should be visible
    And I follow "Topic 1"
    And "#section-1" "css_element" should be visible
    And I follow "My Courses"
    Then "#snap-pm" "css_element" should be visible
    And I follow "Close"
    And "#snap-pm" "css_element" should not be visible
    And "#section-1" "css_element" should be visible
    And "#section-0" "css_element" should not be visible

  @javascript
  Scenario: User logs in and sees the personal menu on site homepage, if that setting used
    Given the following config values are set as admin:
      | defaulthomepage | 0 |
    And I follow "Log in"
    And I set the field "username" to "teacher1"
    And I set the field "password" to "teacher1"
    And I press "Log in"
    Then "#snap-pm" "css_element" should be visible
    And I follow "Close"
    Then "#page-site-index #page-header" "css_element" should be visible

  @javascript
  Scenario: User accesses a course and is prompted to log in, does not see personal menu
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | teacher1  | C1     | editingteacher |
    And I am on homepage
    When I follow "Courses"
    And I am on "Course 1" course homepage
    # The above will trigger a redirect to the login page.
    And I wait until ".snap-log-in-loading-spinner" "css_element" is not visible
    And I set the field "username" to "teacher1"
    And I set the field "password" to "teacher1"
    And I press "Log in"
    Then "#snap-pm" "css_element" should not be visible
    And "#section-0" "css_element" should be visible
    And I am on site homepage
    And "#snap-pm" "css_element" should not be visible
    And I am on homepage
    And "#snap-pm" "css_element" should not be visible

  @javascript
  Scenario: User sees the home page that its configured in navigation settings.
    Given the following config values are set as admin:
      | defaulthomepage         | 0 |            |
      | personalmenulogintoggle | 0 | theme_snap |
    And I log in as "admin"
    Then "#page-site-index" "css_element" should be visible
    And I click on "#snap-home" "css_element"
    Then "#page-site-index" "css_element" should be visible
    And the following config values are set as admin:
      | defaulthomepage | 1 |
    And I log in as "admin"
    Then "#page-my-index" "css_element" should be visible
    And I click on "#snap-home" "css_element"
    Then "#page-my-index" "css_element" should be visible
    And the following config values are set as admin:
      | defaulthomepage | 3 |
    And I log in as "admin"
    Then "#page-my-index.page-mycourses" "css_element" should be visible
    And I click on "#snap-home" "css_element"
    Then "#page-my-index.page-mycourses" "css_element" should be visible
    And the following config values are set as admin:
      | defaulthomepage | 2 |
    And I open the personal menu
    And I click on "#snap-pm-preferences" "css_element"
    And I follow "Start page"
    And I set the field with xpath "//select[@name='defaulthomepage']" to "Home"
    And I press "Save changes"
    And I log in as "admin"
    Then "#page-site-index" "css_element" should be visible
    And I click on "#snap-home" "css_element"
    Then "#page-site-index" "css_element" should be visible
    And I open the personal menu
    And I click on "#snap-pm-preferences" "css_element"
    And I follow "Start page"
    And I set the field with xpath "//select[@name='defaulthomepage']" to "Dashboard"
    And I press "Save changes"
    And I log in as "admin"
    Then "#page-my-index" "css_element" should be visible
    And I click on "#snap-home" "css_element"
    Then "#page-my-index" "css_element" should be visible
    And I open the personal menu
    And I click on "#snap-pm-preferences" "css_element"
    And I follow "Start page"
    And I set the field with xpath "//select[@name='defaulthomepage']" to "My courses"
    And I press "Save changes"
    And I log in as "admin"
    Then "#page-my-index.page-mycourses" "css_element" should be visible
    And I click on "#snap-home" "css_element"
    Then "#page-my-index.page-mycourses" "css_element" should be visible

  @javascript
  Scenario: After login, admin user sees the expected links in the personal menu.
    Given I log in as "admin"
    And I open the personal menu
    Then I should see "Profile"
    Then I should see "My Account"
    Then I should see "Dashboard"
    Then I should see "Grades"
    Then I should see "Preferences"
    Then I should see "Course catalogue"
    Then I should see "Program catalogue"
    Then I should see "My programs"
    Then I should see "Switch role to..."
    Then I should see "Log out"
