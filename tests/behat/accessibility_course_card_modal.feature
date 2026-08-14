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
# Tests for course card modal accessibility in Snap.
#
# @package    theme_snap
# @copyright  Copyright (c) 2026 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Course card modal dialog accessibility

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin0']" to "List of courses"
    And I press "Save changes"
    And I am on site homepage

  @javascript
  Scenario: Focus returns to the trigger button after closing the course card modal with Escape key
    Given I hover ".snap-home-course" "css_element"
    And I press the tab key
    And I press the tab key
    And the focused element is "Show course information" "button"
    When I press the enter key
    And ".snap-home-course-card" "css_element" should be visible
    And I press the escape key
    Then ".snap-home-course-card" "css_element" should not be visible
    And the focused element is "Show course information" "button"
    And ".more-info" "css_element" should be visible
