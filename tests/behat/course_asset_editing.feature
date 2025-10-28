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
# Tests for course resource and activity editing features.
#
# @package    theme_snap
# @copyright  2015 Guy Thomas
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course @theme_snap_course_asset
Feature: When the moodle theme is set to Snap, teachers edit assets without entering edit mode.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format | showcompletionconditions | enablecompletion | initsections |
      | Course 1 | C1        | 0        | topics | 1                        | 1                |      1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher2  | 1        | teacher2@example.com |
      | student1 | Student   | 1        | student1@example.com |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | admin    | C1     | editingteacher |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | student1 | C1     | student        |

  @javascript
  Scenario: Student cannot access edit actions.
    Given the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1                                   |
    And I log in as "admin"
    And I log out
    Then I log in as "student1"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    And "Actions" "icon" should not exist in the "#section-1" "css_element"

  @javascript
  Scenario: In read mode, non-editing teacher can see teacher's actions.
    Given the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1                                   |
    And I log in as "admin"
    And I log out
    And I log in as "teacher2"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    Then "#section-1" "css_element" should exist
    And I should see "Test assignment"
    And "Actions" "icon" should not exist in the "#section-1" "css_element"

  @javascript
  Scenario: In read mode, teacher hides then shows activity.
    Given the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1                                   |
    And I log in as "admin"
    And I log out
    And I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    Then "#section-1" "css_element" should exist
    And I should see "Test assignment"
    And I open "Test assignment" actions menu
    And I choose "Hide" in the open action menu
    And I should see "Hidden from students" in the "Test assignment" "activity"
    And I open "Test assignment" actions menu
    And I choose "Show" in the open action menu
    Then I should not see "Hidden from students" in the "Test assignment" "activity"

  @javascript
  Scenario: In read mode, teacher hides then shows resource.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    Then "#section-1" "css_element" should exist
    And "#snap-drop-file-1" "css_element" should exist
    And I upload file "test_text_file.txt" to section 1
    Then I should see "test_text_file.txt"
    And I wait "2" seconds
    And I switch edit mode in Snap
    And I open "test text file" actions menu
    And I choose "Hide" in the open action menu
    And I should see "Hidden from students" in the "test text file" "activity"
    # This is to test that the change persists.
    And I reload the page
    And I open "test text file" actions menu
    And I choose "Show" in the open action menu
    And I should not see "Hidden from students" in the "test text file" "activity"
    # This is to test that the change persists.
    And I reload the page
    And I should not see "Hidden from students" in the "test text file" "activity"

  @javascript
  Scenario: In read mode, teacher duplicates activity.
    Given the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1                                   |
    And I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    And I open "Test assignment" actions menu
    And I choose "Duplicate" in the open action menu
    And I should see "Test assignment (copy)"
    # This is to test that the duplication persists.
    And I reload the page
    And I should see "Test assignment (copy)"

  @javascript
  Scenario: In read mode, teacher duplicates resource.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Section 1"
    Then "#section-1" "css_element" should exist
    And "#snap-drop-file-1" "css_element" should exist
    When I upload file "test_text_file.txt" to section 1
    Then I should see "test text file"
    And I switch edit mode in Snap
    And I open "test text file" actions menu
    And I choose "Duplicate" in the open action menu
    And I should see "test text file (copy)"
        # This is to test that the duplication persists.
    And I reload the page
    And I should see "test text file (copy)"

  @javascript
  Scenario: In read mode, teacher can copy activity to sharing cart.
    Given I skip because "Will be reviewed in INT-21471"
    Given the following "activities" exist:
      | activity | course | idnumber | name            | intro           | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | Test assignment | Test assignment | 1       | 1|
    And the following "blocks" exist:
      | blockname         | contextlevel | reference | pagetypepattern | defaultregion |
      | sharing_cart      | Course       | C1        | course-view-*   | side-pre      |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Section 1"
    And I switch edit mode in Snap
    And I open "Test assignment" actions menu
    And I choose "Copy to Sharing Cart" in the open action menu
    Then I should see "Are you sure you want to copy this"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Section 1"
    And ".snap-activity[data-type='Assignment'] button.snap-edit-asset-more" "css_element" should not exist
    Then I should not see "Copy to Sharing Cart"

  @javascript
  Scenario: In the frontpage, an admin duplicates an activity.
    Given the following "activities" exist:
      | activity | course               | section | name        | intro                  | idnumber |
      | assign   | Acceptance test site | 1       | Assignment1 | Assignment description | assign1  |
    Then I log in as "admin"
    And I am on site homepage
    And I switch edit mode in Snap
    And I open "Assignment1" actions menu
    And I choose "Duplicate" in the open action menu
    And I should see "Assignment1 (copy)"

  @javascript
  Scenario: In the frontpage, an admin can edit completions conditions
    Given I skip because "XXXX - completion link is not working on actions menu"
    Given the following "activities" exist:
      | activity | name              | course | idnumber | gradepass | completion | completionusegrade | completionview |
      | quiz     | Activity sample 1 | C1     | quiz1    | 5.00      | 2          | 1                  | 1               |
    When I am on the "C1" "Course" page logged in as "admin"
    And I open "Activity sample 1" actions menu
    And I choose "Edit conditions" in the open action menu
    Then I click on "Edit conditions Activity sample 1" "button"
    And ".snap-form-required > fieldset" "css_element" should not be visible
    But ".snap-form-advanced > fieldset#id_activitycompletionheader" "css_element" should be visible
    

  @javascript
  Scenario: In the frontpage, an admin should not see the Edit conditions option if Enable completion is disabled in the site
    Given I skip because "XXXX - completion link is not working on actions menu"
    Given I disable site completion tracking
    And the following "activities" exist:
      | activity | name              | course | idnumber | gradepass | completion | completionusegrade |
      | quiz     | Activity sample 1 | C1     | quiz1    | 5.00      | 2          | 1                  |
    When I am on the "C1" "Course" page logged in as "admin"
    And I click on "More Options" "button"
    Then I should not see "Edit conditions" in the "#snap-asset-menu" "css_element"
