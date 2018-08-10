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
# Tests deleting sections in snap.
#
# @package    theme_snap
# @author     Guy Thomas
# @copyright  2016 Blackboard Ltd
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, teachers can delete sections without having to reload the page.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | course | idnumber | name             | intro                         | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment description 1 | 1       | 1                                   |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment description 2 | 2       | 1                                   |

  @javascript
  Scenario: In read mode, on course, teacher can cancel / confirm delete section.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"

    And I follow "Topic 1"
    And I click on "#section-1 .edit-summary" "css_element"
    And I set the section name to "Topic one"
    And I press "Save changes"
    And I follow "Topic 2"
    And I click on "#section-2 .edit-summary" "css_element"
    And I set the section name to "Topic two"
    And I press "Save changes"

    And I follow "Topic one"
    Then "#section-1" "css_element" should exist
    When I click on "#section-1 .snap-section-editing.actions a.snap-delete" "css_element"
    Then I should see section delete dialog
    And I cancel dialog
    Then I should not see section delete dialog
    And I should see "Topic one"
    When I click on "#section-1 .snap-section-editing.actions a.snap-delete" "css_element"
    Then I should see section delete dialog
    When I press "Delete Section"
    Then I should not see "Topic one" in the "#section-1" "css_element"
    And I cannot see "Test assignment1" in course asset search
    And I can see "Test assignment2" in course asset search

  @javascript
  Scenario: Student cannot delete section.
    Given I log in as "student1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1 .snap-section-editing.actions a.snap-delete" "css_element" should not exist
