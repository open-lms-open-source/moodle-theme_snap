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
# Tests for availability of course tools section.
#
# @package   theme_snap
# @author    Guy Thomas <gthomas@moodlerooms.com>
# @copyright Copyright (c) 2016 Blackboard Inc.
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, a course tools section is available.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
      | defaulthomepage | 0 |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |

  @javascript
  Scenario: Course tools link does not show for unsupported formats.
    Given the course format for "C1" is set to "social"
    When I log in as "student1"
    And I am on the course main page for "C1"
    Then "a[href=\"#coursetools\"]" "css_element" should not exist

  @javascript
  Scenario Outline: Course tools link functions for supported formats.
    Given the course format for "C1" is set to "<format>"
    When I log in as "student1"
    And I am on the course main page for "C1"
    And I click on "a[href=\"#coursetools\"]" "css_element"
    Then I should see "Course Tools" in the "#coursetools" "css_element"
    Examples:
      | format |
      | topics |
      | weeks  |

  @javascript
  Scenario: Course tools show automatically for single activity format.
    Given the course format for "C1" is set to "singleactivity" with the following settings:
      | name      | activitytype |
      | value     | forum        |
    And the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section |
      | forum    | C1     | forum1   | Test forum      | Test forum      | 1       |
    When I log in as "student1"
    And I am on the course main page for "C1"
    # Note we have to call this step twice because for some reason it doesn't automatically go to the module page the
    # first time - that's a core issue though.
    And I am on the course main page for "C1"
    Then I should see "Course Tools" in the "#coursetools" "css_element"

  @javascript
  Scenario Outline: Course tools show automatically for single activity format set to hsuforum of types general / single.
    Given I am using Joule
    And the course format for "C1" is set to "singleactivity" with the following settings:
      | name      | activitytype |
      | value     | hsuforum        |
    And the following "activities" exist:
      | activity    | course | idnumber | name          | intro           | section | type   |
      | hsuforum    | C1     | forum1   | Test hsuforum | Test hsuforum   | 1       | <type> |
    When I log in as "student1"
    And I am on the course main page for "C1"
    # Note we have to call this step twice because for some reason it doesn't automatically go to the module page the
    # first time - that's a core issue though.
    And I am on the course main page for "C1"
    Then I should see "Course Tools" in the "#coursetools" "css_element"
    Examples:
      | type    |
      | general |
      | single  |
