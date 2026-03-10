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
# along with Moodle. If not, see <http://www.gnu.org/licenses/>.
#
# Test for Bulk actions in Snap
#
# @package    theme_snap
# @autor      Bryan Cruz
# @copyright  Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, Bulk course activity actions work as expected.
  Background:
    Given the following "course" exists:
      | fullname     | Course 1 |
      | shortname    | C1       |
      | category     | 0        |
      | numsections  | 4        |
      | initsections | 1        |
    And the following "activities" exist:
      | activity | name              | intro                       | course | idnumber | section |
      | assign   | Activity sample 1 | Test assignment description | C1     | sample1  | 1       |
      | assign   | Activity sample 2 | Test assignment description | C1     | sample2  | 1       |
      | assign   | Activity sample 3 | Test assignment description | C1     | sample3  | 2       |
      | assign   | Activity sample 4 | Test assignment description | C1     | sample4  | 2       |
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
      | student1  | Student    | 1         | student1@example.com  |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | student1  | C1      | student         |
      | teacher1  | C1      | editingteacher  |
      | admin     | C1      | editingteacher  |
    Then I log in as "teacher1"

  @javascript
  Scenario: Bulk actions are available with editing mode on, and closes when navigating to other course section.
    And I am on the course main page for "C1"
    And I should not see "Bulk actions"
    And I turn editing mode on
    And I should see "Bulk actions"
    And I click on "Bulk actions" "button"
    And I should see "0 selected" in the "sticky-footer" "region"
    # When changing sections the Bulk actions are closed.
    And I follow "Section 2"
    And I wait until the page is ready
    And I should not see "0 selected" in the "sticky-footer" "region"

  @javascript
  Scenario: Bulk duplicate activities works on Snap
    And I am on the course main page for "C1"
    And I turn editing mode on
    And I follow "Section 1"
    And I wait until the page is ready
    And I click on "Bulk actions" "button" in the "#section-1" "css_element"
    Given I click on "Select activity Activity sample 1" "checkbox"
    And I click on "Select activity Activity sample 2" "checkbox"
    And I should see "2 selected" in the "#section-1 #sticky-footer" "css_element"
    When I click on "Duplicate activities" "button" in the "#section-1 #sticky-footer" "css_element"
    Then I should see "Activity sample 1" in the "#section-1" "css_element"
    And I should see "Activity sample 1 (copy)" in the "#section-1" "css_element"
    And "Activity sample 1 (copy)" "activity" should appear after "Activity sample 1" "activity"

  @javascript
  Scenario: Select All activities should select only the visible activities in the current section.
    Given I am on the course main page for "C1"
    And I should not see "Bulk actions"
    And I turn editing mode on
    And I should see "Bulk actions"
    And I follow "Section 2"
    And I wait until the page is ready
    # Go back to the first section and verify 'Select All' selects the current section activities only.
    And I follow "Section 1"
    And I wait until the page is ready
    And I click on "Bulk actions" "button" in the "#section-1" "css_element"
    Then I click on "Select activity Activity sample 1" "checkbox"
    And I click on "Select all" "checkbox" in the "#section-1 #sticky-footer" "css_element"
    And I should see "2 selected" in the "#section-1 #sticky-footer" "css_element"

