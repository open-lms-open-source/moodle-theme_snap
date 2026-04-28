# This file is part of Moodle - https://moodle.org/
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
# along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
#
# Tests for navigation between activities with restrictions.
#
# @package    theme_snap
# @author     Juan Felipe Orozco Escobar <juanfelipe.orozcoescobar@openlms.net>
# @copyright  Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Access Course Activities overview
  In order to review all the activities in a course
  As an admin, a teacher, and a student
  I need to be able to access the Activities overview page from the course dashboard and course administration menus

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Admin can access the Course Activities page from the dashboard and the cog icon
    Given I am logged in as "admin"
    And I am on "Course 1" course homepage
    And I scroll to the base of selector ".toc-footer"
    And I wait "3" seconds
    And I follow "Course Dashboard"
    Then "//li[contains(@class, 'tool-card')]/a[contains(text(), 'Activities')]" "xpath_element" should exist
    And I click on "//li[contains(@class, 'tool-card')]/a[contains(text(), 'Activities')]" "xpath_element"
    Then I should see "An overview of all activities in the course, with dates and other information."
    And I am on "Course 1" course homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I scroll to the base of selector "#settingsnav"
    Then "Activities" "link" should exist
    And I click on "Activities" "link"
    Then I should see "An overview of all activities in the course, with dates and other information."

  @javascript
  Scenario: Teacher can access the Course Activities page from the dashboard and the cog icon
    Given I am logged in as "teacher1"
    And I am on "Course 1" course homepage
    And I scroll to the base of selector ".toc-footer"
    And I wait "3" seconds
    And I follow "Course Dashboard"
    Then "//li[contains(@class, 'tool-card')]/a[contains(text(), 'Activities')]" "xpath_element" should exist
    And I click on "//li[contains(@class, 'tool-card')]/a[contains(text(), 'Activities')]" "xpath_element"
    Then I should see "An overview of all activities in the course, with dates and other information."
    And I am on "Course 1" course homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I scroll to the base of selector "#settingsnav"
    Then "Activities" "link" should exist
    And I click on "Activities" "link"
    Then I should see "An overview of all activities in the course, with dates and other information."

  @javascript
  Scenario: Student can access the Course Activities page from the dashboard
    Given I am logged in as "student1"
    And I am on "Course 1" course homepage
    And I scroll to the base of selector ".toc-footer"
    And I wait "3" seconds
    And I follow "Course Dashboard"
    Then "//li[contains(@class, 'tool-card')]/a[contains(text(), 'Activities')]" "xpath_element" should exist
    And I click on "//li[contains(@class, 'tool-card')]/a[contains(text(), 'Activities')]" "xpath_element"
    Then I should see "An overview of all activities in the course, with dates and other information."
