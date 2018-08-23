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
# @copyright  2015 Guy Thomas <osdev@blackboard.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, teachers can move course resources and activities without using drag and drop.

  Background:
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Edit settings" node in "Front page settings"
    And I set the following fields to these values:
      | Include a topic section | 1 |
    And I log out
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1 | 0 | topics |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity | course               | idnumber | name             | intro                         | section | assignsubmission_onlinetext_enabled |
      | assign   | C1                   | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   |
      | assign   | C1                   | assign2  | Test assignment2 | Test assignment description 2 | 1       | 1                                   |
      | assign   | Acceptance test site | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   |
      | assign   | Acceptance test site | assign2  | Test assignment2 | Test assignment description 2 | 1       | 1                                   |

  @javascript
  Scenario: In read mode, on front page, admin moves activity.
    Given I log in as "admin"
    And I am on site homepage
    And I click on ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment1\"']" "css_element"
    Then I should see "Moving \"Test assignment1\""
    And I click on "#region-main .sitetopic ul.section li.snap-drop.asset-drop div.asset-wrapper" "css_element"
    Then ".snap-activity.modtype_assign" "css_element" should appear after ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment2\"']" "css_element"

  @javascript
  Scenario: In read mode, on course, teacher moves activity.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And I click on ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment1\"']" "css_element"
    Then I should see "Moving \"Test assignment1\""
    And I click on "li#section-1 li.snap-drop.asset-drop div.asset-wrapper" "css_element"
    Then ".snap-activity.modtype_assign" "css_element" should appear after ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment2\"']" "css_element"

  @javascript
  Scenario: In read mode, on course, teacher moves activity to a different section.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And I click on ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment1\"']" "css_element"
    Then I should see "Moving \"Test assignment1\""
    And I follow "Topic 2"
    And I click on "li#section-2 li.snap-drop.asset-drop div.asset-wrapper" "css_element"
    Then "li#section-2 .snap-activity.modtype_assign" "css_element" should appear before "li.snap-drop.asset-drop" "css_element"

  @javascript
  Scenario: In read mode, on course, teacher moves two activities to a different section.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And I click on ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment1\"']" "css_element"
    Then I should see "Moving \"Test assignment1\""
    And I click on ".snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment2\"']" "css_element"
    Then I should see "Moving 2 objects"
    And I follow "Topic 2"
    And I click on "li#section-2 li.snap-drop.asset-drop div.asset-wrapper" "css_element"
    Then "li#section-2 .snap-activity.modtype_assign" "css_element" should appear before "li.snap-drop.asset-drop" "css_element"
    And "li#section-2 .snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment1\"']" "css_element" should appear before "li#section-2 .snap-activity.modtype_assign .snap-asset-move img[title='Move \"Test assignment2\"']" "css_element"

  @javascript
  Scenario: Student cannot move activity.
    Given I log in as "student1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then ".snap-activity.modtype_assign .js-snap-asset-move" "css_element" should not exist
