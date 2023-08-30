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
# along with Moodle. If not, see <http://www.gnu.org/licenses/>.
#
# Test for Snap's My courses page
#
# @package    theme_snap
# @autor      Daniel Cifuentes
# @copyright  Copyright (c) 2023 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Users can access to the My Courses page in Snap.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | student1  | Student    | 1         | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | student1  | C1      | student         |
    And the following config values are set as admin:
      | defaulthomepage | 3 |
    And the following "permission overrides" exist:
      | capability            | permission | role  | contextlevel | reference |
      | moodle/course:request | Allow      | user  | System       |           |


  @javascript
  Scenario: User can access to course management options in Snap's My Courses page.
    Given I log in as "admin"
    And I close the personal menu
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should exist
    Then I click on ".snap-page-my-courses-options .btn-group" "css_element"
    And I should see "New course"
    And I should see "Manage courses"
    And I should not see "Request a course"
    And I follow "New course"
    And I should see "Add a new course"
    And I click on "#snap-home" "css_element"
    And I click on ".snap-page-my-courses-options .btn-group" "css_element"
    And I follow "Manage courses"
    And I should see "Manage course categories and courses"
    And I log out
    And I log in as "student1"
    And I close the personal menu
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should exist
    Then I click on ".snap-page-my-courses-options .btn-group" "css_element"
    And I should not see "New course"
    And I should not see "Manage courses"
    And I should see "Request a course"
    And I follow "Request a course"
    And I should see "Details of the course you are requesting"
    And I log out
    And the following config values are set as admin:
      | enablecourserequests | 0 |
    And I log in as "student1"
    And I close the personal menu
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should not exist