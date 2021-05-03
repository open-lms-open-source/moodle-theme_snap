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
# @author     Sebastian Gracia
# @copyright  Copyright (c) 2019 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_personalmenu
Feature: A student should not see any recent forum activity
  on his personal menu if he is not part of the posted group

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
      | student2 | Student   | 2        | student2@example.com  |
      | student3 | Student   | 3        | student3@example.com  |
      | student4 | Student   | 4        | student4@example.com  |
    And the following "courses" exist:
      | fullname | shortname  | category  |
      | Course 1 | C1         | 0         |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "groups" exist:
      | name    | course  | idnumber |
      | Group 1 | C1      | G1       |
      | Group 2 | C1      | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
      | student3 | G2    |
      | student4 | G2    |
    And the following "activities" exist:
      | activity      | name                   | intro                | course | idnumber     | groupmode |
      | forum         | Test forum name        | Test forum name      | C1     | forum        | 1         |
      | hsuforum      | Test hsuforum name     | Test hsuforum name   | C1     | hsuforum     | 1         |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on "//h3/a/p[contains(text(),'Test forum name')]" "xpath_element"
    And I wait "2" seconds
    And I add a new discussion to "Test forum name" forum with:
      | Subject | Discussion 1      |
      | Message | Test post message |
      | Group   | Group 1           |
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on "//h3/a/p[contains(text(),'Test hsuforum name')]" "xpath_element"
    And I wait "2" seconds
    And I add a new discussion to "Test hsuforum name" Open Forum with:
      | Subject | Open forum         |
      | Message | Test post message  |
      | Group   | Group 2            |
    And I log out

  @javascript
  Scenario: Student2 should see recent activity made in Forums on Snap personal menu
    And I log in as "student2"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should see "Discussion 1"
    And I log out

  @javascript
  Scenario: Student3 should not see recent activity made in Forums on Snap personal menu
    And I log in as "student3"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should not see "Discussion 1"
    And I log out

  @javascript
  Scenario: Student1 should not see recent activity made in Open Forums on Snap personal menu
    And I log in as "student1"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should not see "Open forum"
    And I log out

  @javascript
  Scenario: Student4 should see recent activity made in Open Forums on Snap personal menu
    And I log in as "student4"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should see "Open forum"
    And I log out
