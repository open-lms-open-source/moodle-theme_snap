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
# @author     Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  
@theme @theme_snap
Feature: When the Moodle theme is set to Snap, personal menu and course mod chooser should be accessible tabs.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format | startdate  | enddate    |
      | Course 1 | C1        | topics | 1378796400 | 0          |
      | Course 2 | C2        | topics | 1337670000 | 1520440320 |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
      | teacher1  | C2      | editingteacher  |

  @javascript
  Scenario: Personal menu tab should have a specific aria-controls attribute to be accessible.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I open the personal menu
    And the "aria-controls" attribute of "#snap-pm-accessible-tab a#snap-pm-tab-current" "css_element" should contain "snap-pm-courses-current"

  @javascript
  Scenario: Course mod chooser tab should have a specific aria-controls attribute to be accessible.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I click on "a.section-modchooser-link" "css_element"
    And the "aria-controls" attribute of "#modchooser-accessible-tab a#activites-tab" "css_element" should contain "activites"
    And the "aria-controls" attribute of "#modchooser-accessible-tab a#resources-tab" "css_element" should contain "resources"
    And the "aria-controls" attribute of "#modchooser-accessible-tab a#help-guide-tab" "css_element" should contain "help"

  @javascript
  Scenario: Press arrow keys should be an accessible way to display content correctly.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I click on "a.section-modchooser-link" "css_element"
    And I click on "a#activites-tab" "css_element"
    # 39 is the keystroke for right arrow key press.
    And I press key "39" in "a#activites-tab" "css_element"
    And the "aria-selected" attribute of "a#activites-tab" "css_element" should contain "false"
    And the "aria-selected" attribute of "a#resources-tab" "css_element" should contain "true"
    # 40 is the keystroke for down arrow key press.
    And I press key "40" in "a#resources-tab" "css_element"
    And the "aria-selected" attribute of "a#resources-tab" "css_element" should contain "false"
    And the "aria-selected" attribute of "a#help-guide-tab" "css_element" should contain "true"
    # 37 is the keystroke for left arrow key press.
    And I press key "37" in "a#help-guide-tab" "css_element"
    And the "aria-selected" attribute of "a#resources-tab" "css_element" should contain "true"
    And the "aria-selected" attribute of "a#help-guide-tab" "css_element" should contain "false"
    # 38 is the keystroke for up arrow key press.
    And I press key "38" in "a#resources-tab" "css_element"
    And the "aria-selected" attribute of "a#activites-tab" "css_element" should contain "true"
    And the "aria-selected" attribute of "a#resources-tab" "css_element" should contain "false"
