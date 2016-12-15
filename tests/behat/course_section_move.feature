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
# @copyright  2015 Guy Thomas <gthomas@moodlerooms.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, teachers can move course sections without using drag and drop and without
  having to enter edit mode.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
      | defaulthomepage | 0 |
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

  @javascript
  Scenario: In read mode, teacher moves section 1 before section 4 (section 3).
    Given I log in as "teacher1" (theme_snap)
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    And I follow "Untitled Topic"
    And I set the following fields to these values:
      | name | My & < > Topic |
    And I press "Save changes"
    And I follow "Move \"My & < > Topic\""
    Then I should see "Moving \"My & < > Topic\"" in the "#snap-footer-alert" "css_element"
    When I follow "Topic 4"
    And I follow "Place section \"My & < > Topic\" before section \"Topic 4\""
    Then I should see "My & < > Topic" in the "#section-3" "css_element"
    And "#chapters li:nth-of-type(4).snap-visible-section" "css_element" should exist
    # Check that navigation is also updated.
    # Note that "4th" refers to section-3 as section-0 is the "introduction" section in the TOC.
    When I click on the "4th" link in the TOC
    Then I should see "My & < > Topic" in the "#section-3 .sectionname" "css_element"
    Then the previous navigation for section "3" is for "Topic 2" linking to "#section-2"
    And the next navigation for section "3" is for "Topic 4" linking to "#section-4"
    And the previous navigation for section "4" is for "My & < > Topic" linking to "#section-3"
    And the next navigation for section "2" is for "My & < > Topic" linking to "#section-3"

  @javascript
  Scenario: Teacher loses teacher capability whilst course open and receives the correct error message when trying to
  move section.
    Given debugging is turned off
    And I log in as "teacher1" (theme_snap)
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    And I follow "Untitled Topic"
    And I set the following fields to these values:
      | name | My & < > Topic |
    And I press "Save changes"
    And I follow "Move \"My & < > Topic\""
    Then I should see "Moving \"My & < > Topic\"" in the "#snap-footer-alert" "css_element"
    When I follow "Topic 4"
    And the editing teacher role is removed from course "C1" for "teacher1"
    And I follow "Place section \"My & < > Topic\" before section \"Topic 4\""
    Then I should see "Sorry, but you do not currently have permissions to do that (Move sections)"

  @javascript
  Scenario: In read mode, student cannot move sections.
    Given I log in as "student1" (theme_snap)
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "a[title=Move section]" "css_element" should not exist
