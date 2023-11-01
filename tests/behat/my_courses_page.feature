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

@theme @theme_snap @theme_snap_personalmenu
Feature: Users can access to the My Courses page in Snap.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | student1  | Student    | 1         | student1@example.com  |
      | teacher1 | Teacher     | 1         | teacher1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | student1  | C1      | student         |
      | teacher1 | C1       | editingteacher  |
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | duedate   |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | ##today## |
    And the following config values are set as admin:
      | defaulthomepage | 3 |
    And the following "permission overrides" exist:
      | capability            | permission | role  | contextlevel | reference |
      | moodle/course:request | Allow      | user  | System       |           |

  @javascript
  Scenario: User can access to course management options in Snap's My Courses page.
    Given I log in as "admin"
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
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should exist
    Then I click on ".snap-page-my-courses-options .btn-group" "css_element"
    And I should not see "New course"
    And I should not see "Manage courses"
    And I should see "Request a course"
    And I follow "Request a course"
    And I should see "Details of the course you are requesting"
    And I log in as "admin"
    And the following config values are set as admin:
      | enablecourserequests | 0 |
    And I log out
    And I log in as "student1"
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should not exist

  @javascript
  Scenario: User can see the Snap feeds using the new layout in my courses.
    And I log in as "admin"
    And I should see "Browse all courses"
    And I should see "Course overview"
    And I should see "Snap feeds"
    And I click on ".snap-page-my-courses-options" "css_element"
    And I should see "New course"
    And I should see "Manage courses"
    And I log in as "teacher1"
    And I should see "Course overview"
    And I should see "Snap feeds"
    And I should see "Today"
    And I am on "Course 1" course homepage
    Then I follow "Edit \"Test assignment1\""
    And I expand all fieldsets
    And I set the following fields to these values:
      | Due date | ##tomorrow## |
    And I press "Save and return to course"
    And I log in as "teacher1"
    And I should see "Tomorrow"

  @javascript
  Scenario: User can see the Snap feeds items based on the Snap settings.
    And I log in as "teacher1"
    And I should see "Course overview"
    And I should see "Snap feeds"
    And I should see "Deadlines"
    And I should see "Grading"
    And I should see "Messages"
    And I should see "Forum posts"
    Then the following config values are set as admin:
      | deadlinestoggle | 0 | theme_snap  |
      | feedbacktoggle  | 0  | theme_snap |
    And I log in as "teacher1"
    And I should see "Snap feeds"
    And I should not see "Deadlines"
    And I should not see "Grading"
    And I should see "Messages"
    And I should see "Forum posts"
    Then the following config values are set as admin:
      | messagestoggle   | 0  | theme_snap  |
      | forumpoststoggle | 0  | theme_snap  |
    And I log in as "teacher1"
    And I should not see "Snap feeds"
    And I should not see "Deadlines"
    And I should not see "Grading"
    And I should not see "Messages"
    And I should not see "Forum posts"

  @javascript
  Scenario: User can disable personal menu to redirect to the My Courses page with header button.
    Given I log in as "admin"
    And "#snap-pm" "css_element" should not be visible
    And I follow "My Courses"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should exist
    Then the following config values are set as admin:
      | personalmenuenablepersonalmenu | 1 | theme_snap  |
    And I log in as "admin"
    Then "#snap-pm" "css_element" should be visible
    Then the following config values are set as admin:
      | personalmenulogintoggle | 0 | theme_snap  |
    And I log in as "admin"
    Then "#snap-pm" "css_element" should not be visible
    And I follow "My Courses"
    Then "#snap-pm" "css_element" should be visible
    And "#page-my-index .page-mycourses" "css_element" should not exist