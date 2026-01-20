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
# Tests for manual completion.
#
# @package   theme_snap
# @author    Guy Thomas
# @copyright Copyright (c) 2016 Open LMS
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course
Feature: Manual completion updates page wihout reload.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | category | groupmode | enablecompletion | initsections |
      | Course 1 | C1        | topics | 0        | 1         | 1                |      1       |
      | Course 2 | C2        | topics | 0        | 1         | 0                |      1       |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | admin    | C1     | teacher |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course               | idnumber | name             | intro                         | section | assignsubmission_onlinetext_enabled | completion | completionview |
      | assign   | C1                   | assign1  | Test assignment1 | Test assignment description 1 | 0       | 1                                   | 1          | 0              |
      | assign   | C1                   | assign2  | Test assignment2 | Test assignment description 2 | 0       | 1                                   | 1          | 0              |
      | assign   | C1                   | assign3  | Test assignment3 | Test assignment description 3 | 1       | 1                                   | 1          | 0              |
      | assign   | C1                   | assign4  | Test assignment4 | Test assignment description 4 | 2       | 1                                   | 0          | 0              |

  @javascript
  # Done as one scenario for best performance.
  Scenario Outline: Assignment module is manually marked complete and releases restricted activities / sections.
    Given I log in as "admin"
    And the following config values are set as admin:
      | resourcedisplay     | <Option> | theme_snap |
    And I am on the course main page for "C1"
    # Restrict the second assign module to only be accessible after the first assign module is marked complete.
    And I restrict course asset "Test assignment2" by completion of "Test assignment1"
    # Restrict section 1 to only be accessible after the second assign module is complete.
    And I follow "Section 1"
    And I click on "#section-1 .edit-summary" "css_element"
    And I set the section name to "Section 1"
    And I apply asset completion restriction "Test assignment2" to section
    # Restrict section 2 to only be accessible after the third assign module is complete.
    And I follow "Section 2"
    And I click on "#section-2 .edit-summary" "css_element"
    And I set the section name to "Section 2"
    And I apply asset completion restriction "Test assignment3" to section
    And I log out
    # Log in as student to test manual completion releases restrictions.
    And I log in as "student1"
    And I am on the course main page for "C1"
    And I click on "//a[@class='snap-conditional-tag']" "xpath_element"
    And I should see "Not available unless: The activity Test assignment1 is marked complete"
    And I go to section 1 of course "C1"
    And I should see "Section 1 is not available"
    And I should not see "Test assignment3"
    And I go to section 2 of course "C1"
    And I should not see "Test assignment4"
    And I follow "General"
    And I should see "Conditional" in TOC item 1
    And I should see "Conditional" in TOC item 2
    When I mark the activity "Test assignment1" as complete
    And I should see "Test assignment2"
    And I should not see "Test assignment3"
    # Test chained activity completion
    When I mark the activity "Test assignment2" as complete
    Then I should not see "Conditional" in TOC item 1
    And I should see "Conditional" in TOC item 2
    When I follow "Section 1"
    Then I should not see "Not available unless: The activity Test assignment2 is marked complete"
    And I should see "Test assignment3"
    And I go to section 2 of course "C1"
    Then I should see "Section 2 is not available"
    And I follow "Section 1"
    # Test chained activity completion when section has become visible
    When I mark the activity "Test assignment3" as complete
    # Then the "Test assignment3" "assign" activity with "manual" completion should be marked as complete (core_fix)
    Then I should not see "Conditional" in TOC item 2
    And I go to section 2 of course "C1"
    And I should see "Test assignment4"
    # Test marking incomplete
    And I follow "Section 1"
    When I mark the activity "Test assignment3" as incomplete
    Then I should see "Conditional" in TOC item 2
    And I go to section 2 of course "C1"
    Then I should see "Section 2 is not available"
    When I follow "General"
    When I mark the activity "Test assignment2" as incomplete
    Then I should see "Conditional" in TOC item 1
    And I go to section 1 of course "C1"
    Then I should see "Section 1 is not available"
    Examples:
      | Option     |
      | 0          |
      | 1          |

  @javascript
  Scenario: Course progress bar is displayed and updated depending on the course completion.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And "#course-toc-progress-bar" "css_element" should exist
    And I should see "Progress: 0/3"
    And I should see "0%"
    Then I press "Mark as done"
    And I should see "Progress: 1/3"
    And I should see "33%"
    And I open "Test assignment1" actions menu
    And I choose "Duplicate" in the open action menu
    And I should see "Progress: 1/4"
    And I should see "25%"
    Then I press "Done"
    And I should see "Progress: 0/4"
    And I should see "0%"
    And I open "Test assignment1" actions menu
    And I choose "Delete" in the open action menu
    Then I should see asset delete dialog
    And I click on "Delete" "button" in the "Delete activity?" "dialogue"
    And I should see "Progress: 0/3"
    And I should see "0%"
    And I am on the course main page for "C2"
    And "#course-toc-progress-bar" "css_element" should not exist