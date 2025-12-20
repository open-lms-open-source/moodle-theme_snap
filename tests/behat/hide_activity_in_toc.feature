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
# @package    theme_snap
# @copyright  Copyright (c) 2025 Open LMS.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_toc
Feature: Hide activities from Table of Contents in theme_snap

  Background:
    Given the following config values are set as admin:
      | config | value |
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | format | initsections |
      | Test Course | TC1 | topics | 2 |
    And the following "activities" exist:
      | activity | name | course | section |
      | page | Visible Page | TC1 | 1 |
      | page | Hidden Page | TC1 | 1 |


  @javascript
  Scenario: Activity hidden from TOC should not appear in course index drawer but remain accessible
    Given I log in as "admin"
    And I am on "Test Course" course homepage
    And "courseindex-content" "region" should be visible
    And I should see "Visible Page" in the "courseindex-content" "region"
    And I should see "Hidden Page" in the "courseindex-content" "region"
    And I am on activity "page" "Hidden Page" page
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Hide this activity in the Table of contents" to "1"
    And I press "Save and return to course"
    And I wait until the page is ready
    And I go to section 1 of course "TC1"
    And I should see "Hidden Page"
    And "courseindex-content" "region" should be visible
    And I should see "Visible Page" in the "courseindex-content" "region"
    And I should not see "Hidden Page" in the "courseindex-content" "region"

  @javascript
  Scenario: Unhiding an activity from TOC should make it visible again
    Given I log in as "admin"
    And I am on activity "page" "Hidden Page" page
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Hide this activity in the Table of contents" to "1"
    And I press "Save and return to course"
    And I wait until the page is ready
    And I am on "Test Course" course homepage
    And "courseindex-content" "region" should be visible
    And I should not see "Hidden Page" in the "courseindex-content" "region"
    And I am on activity "page" "Hidden Page" page
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Hide this activity in the Table of contents" to ""
    And I press "Save and return to course"
    And I wait until the page is ready
    And I am on "Test Course" course homepage
    And "courseindex-content" "region" should be visible
    And I should see "Hidden Page" in the "courseindex-content" "region"

  @javascript
  Scenario: Hidden activities should not appear in TOC search
    Given I log in as "admin"
    And I am on activity "page" "Hidden Page" page
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Hide this activity in the Table of contents" to "1"
    And I press "Save and return to course"
    And I wait until the page is ready
    And I am on "Test Course" course homepage
    And "courseindex-content" "region" should be visible
    And I set the field with xpath "//input[@id='toc-search-input']" to "Hidden Page"
    And I should not see "Hidden Page" in the "courseindex-content" "region"
    And I set the field with xpath "//input[@id='toc-search-input']" to "Visible"
    And I should see "Visible Page" in the "courseindex-content" "region"