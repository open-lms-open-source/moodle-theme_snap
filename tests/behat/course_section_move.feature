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
# @copyright  2015 Guy Thomas
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course
Feature: When the moodle theme is set to Snap, teachers can move course sections without using drag and drop and without
  having to enter edit mode.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format | initsections |
      | Course 1 |     C1    |     0    | topics |      1       |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "activities" exist:
      | activity   | course  | idnumber    | name             | intro                         | section |
      | assign     | C1      | assign1     | Test assignment1 | Test assignment description 1 | 1       |
      | assign     | C1      | assign2     | Test assignment2 | Test assignment description 2 | 1       |

  @javascript
  Scenario: In read mode, teacher moves section 1 before section 4 (section 3).
    Given I log in as "admin"
    And I change window size to "large"
    And I log out
    And I log in as "teacher1"
    And I switch edit mode in Snap
    And I am on the course main page for "C1"
    And I follow "Section 1"
    And I follow "Untitled Section"
    And I set the section name to "My & < > Section"
    And I press "Save changes"
    And I follow "My & < > Section"
    And I follow "Move \"My & < > Section\""
    And I click on "Section 3" "link" in the ".modal-body" "css_element"
    When I follow "My & < > Section"
    Then I should see "My & < > Section" in the "#section-3 .sectionname" "css_element"
    # Check that navigation is also updated.
    Then the previous navigation for section "3" is for "Section 3"
    And the next navigation for section "3" is for "Section 4"
    And I switch edit mode in Snap
    Then the previous navigation for section "3" is for "Section 3"
    And the next navigation for section "3" is for "Section 4"
    And I follow "Section 4"
    And I switch edit mode in Snap
    And the previous navigation for section "4" is for "My & < > Section"
    And I switch edit mode in Snap
    And the previous navigation for section "4" is for "My & < > Section"
    When I follow "Section 2"
    And I switch edit mode in Snap
    And the next navigation for section "1" is for "Section 3"
    And I switch edit mode in Snap
    And the next navigation for section "1" is for "Section 3"
    # The data-section attribute of the moved section module link should match the section number.
    # This is done so activities are created in the correct section.
    When I follow "My & < > Section"
    And "button.section-modchooser-link.btn-add-activity[data-sectionid='3']" "css_element" should be visible

  @javascript
  Scenario: Teacher loses teacher capability whilst course open and receives the correct error message when trying to
  move section.
    Given debugging is turned off
    And I log in as "admin"
    And I log out
    And I log in as "teacher1"
    And I am on the course main page for "C1"
    And I switch edit mode in Snap
    And I follow "Section 1"
    And I follow "Untitled Section"
    And I set the section name to "My & < > Section"
    And I press "Save changes"
    And I follow "My & < > Section"
    And I follow "Move \"My & < > Section\""
    And the editing teacher role is removed from course "C1" for "teacher1"
    Given I skip because "The message is being showed but the step is failing because Core throws it as an exception."
    And I click on "Section 3" "link" in the ".modal-body" "css_element"
    Then I should see "Sorry, but you do not currently have permissions to do that (Move sections)"

  @javascript
  Scenario: In read mode, student cannot move sections.
    Given I log in as "admin"
    And I log out
    And I log in as "student1"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    Then "a[title=Move section]" "css_element" should not exist

  @javascript
  Scenario: Only navigation between sections is possible.
    Given I log in as "teacher1"
    And I enable "subsection" "mod" plugin
    And the following "activities" exist:
      | activity   | name        | course | idnumber    | section |
      | subsection | Subsection 1 | C1     | Subsection1 | 1      |
      | subsection | Subsection 2 | C1     | Subsection2 | 1      |
    And I am on the course main page for "C1"
    And "//span[contains(@class, \"nav_guide\") and normalize-space(text()) = \"Next section\"]" "xpath" should be visible
    And "//span[contains(@class, \"nav_guide\") and normalize-space(text()) = \"Previous section\"]" "xpath" should not be visible
    And I follow "Section 5"
    And "//span[contains(@class, \"nav_guide\") and normalize-space(text()) = \"Next section\"]" "xpath" should not be visible
    And "//span[contains(@class, \"nav_guide\") and normalize-space(text()) = \"Previous section\"]" "xpath" should be visible
    And I follow "Section 1"
