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
# Tests for navigation between activities.
#
# @package    theme_snap
# @author     Juan Felipe Martinez <juan.martinez@blackboard.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Course scrollback navigation in Snap theme

  Background:
    Given the following config values are set as admin:
      | theme        | snap |
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
      | student1  | Student    | 1         | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | student1  | C1      | student         |
      | teacher1  | C1      | editingteacher  |
    And the following "activities" exist:
      | activity   | name         | intro                       | course | idnumber  | section |
      | imscp      | Imscp 1      | Test imscp description      | C1     | imscp1    | 0       |
      | assign     | Assignment 1 | Test assignment description | C1     | assign1   | 1       |

  @javascript
  Scenario: Scrollback with a teacher in the course homepage and in section.
    Given I log in as "teacher1"
    And I reset session storage
    And I am on "Course 1" course homepage
    And The id for element "//*[@data-type='IMS content package']" "xpath_element" is saved for scrollback
    And I click on "//h4/a/span[contains(text(),'Imscp 1')]" "xpath_element"
    And The stored element scroll id matches the session storage id
    And I follow "Introduction"
    And I wait until the page is ready
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    And The id for element "//*[@data-type='Assignment']" "xpath_element" is saved for scrollback
    And I click on "//h4/a/span[contains(text(),'Assignment 1')]" "xpath_element"
    And The stored element scroll id matches the session storage id
    And I follow "Topic 1"
    And I wait until the page is ready

  @javascript
  Scenario: Scrollback with a student clicking the resource card.
    Given I log in as "student1"
    And I reset session storage
    And I am on "Course 1" course homepage
    And The id for element "//*[@data-type='IMS content package']" "xpath_element" is saved for scrollback
    And I click on "//*[@data-type='IMS content package']/div/div[1]/div[1]" "xpath_element"
    And The stored element scroll id matches the session storage id
    And I follow "Introduction"
    And I wait until the page is ready
    And The id for element "//*[@data-type='IMS content package']" "xpath_element" is saved for scrollback
    And I click on "//*[@data-type='IMS content package']/div" "xpath_element"
    And The stored element scroll id matches the session storage id
    And I follow "Introduction"
    And I wait until the page is ready
