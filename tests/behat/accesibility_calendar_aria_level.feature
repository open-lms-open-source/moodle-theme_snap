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
# Tests for Calendar's anchors aria-label attribute
#
# @package    theme_snap
# @autor      Oscar Nadjar
# @copyright Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: Calendar anchors must contain aria-label attribute.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format | enablecompletion |
      | Course 1 | C1        | 0        | topics | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course               | idnumber | name             | intro                         | section | assignsubmission_onlinetext_enabled | completion | completionview |
      | assign   | C1                   | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   | 1          | 0              |
      | assign   | Acceptance test site | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   | 0          | 0              |

  @javascript
  Scenario: All calendar's anchors must contain the aria-label attribute
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And I click on ".snap-asset .snap-edit-asset" "css_element"
    And the "aria-label" attribute of "#id_allowsubmissionsfromdate_calendar" "css_element" should contain "Calendar"
    And the "aria-label" attribute of "#id_cutoffdate_calendar" "css_element" should contain "Calendar"
    And the "aria-label" attribute of "#id_gradingduedate_calendar" "css_element" should contain "Calendar"
    And the "aria-label" attribute of "#id_duedate_calendar" "css_element" should contain "Calendar"