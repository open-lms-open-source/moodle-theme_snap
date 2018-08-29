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
# Tests for conditional resources.
#
# @package    theme_snap
# @author     2015 Guy Thomas <osdev@blackboard.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, conditional restrictions work as normal.

  Background:
  Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "activities" exist:
      | activity | course | idnumber | name                        | intro                     | section | assignsubmission_onlinetext_enabled | completion | completionview |
      | assign   | C1     | assign1  | S1 Restricted - date past   | Restricted by date past   | 1       | 1                                   | 0          | 0              |
      | assign   | C1     | assign2  | S1 Restricted - date future | Restricted by date future | 1       | 1                                   | 0          | 0              |
      | assign   | C1     | assign3  | S2 Restricted - date past   | Restricted by date past   | 2       | 1                                   | 0          | 0              |
      | assign   | C1     | assign4  | S2 Restricted - date future | Restricted by date future | 2       | 1                                   | 0          | 0              |
      | assign   | C1     | assign5  | S3 Completion - view        | View completion active    | 3       | 1                                   | 1          | 1              |
      | assign   | C1     | assign6  | S4 Activity                 | View completion active    | 4       | 1                                   | 1          | 1              |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Conditionally restricted section notices show for students only when restrictions not met but always show for teachers.
  Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I go to course section 1
    And I restrict assignment "S1 Restricted - date past" by date to "yesterday"
    And I restrict assignment "S1 Restricted - date future" by date to "tomorrow"
    And I should see available from date of "yesterday" in the 1st asset within section 1
    And I should see available from date of "tomorrow" in the 2nd asset within section 1
    And I go to course section 2
    And I restrict assignment "S2 Restricted - date past" by date to "yesterday"
    And I restrict assignment "S2 Restricted - date future" by date to "tomorrow"
    And I should see available from date of "yesterday" in the 1st asset within section 2
    And I should see available from date of "tomorrow" in the 2nd asset within section 2
    And I restrict course section 1 by date to "yesterday"
    And I restrict course section 2 by date to "tomorrow"
    And I should see "Conditional" in TOC item 1
    And I should see "Conditional" in TOC item 2
    And I should not see "Conditional" in TOC item 3
    And I go to course section 1
    And I should see available from date of "yesterday" in section 1
    And I go to course section 2
    And I should see available from date of "tomorrow" in section 2
    And I go to course section 4
    And I click on "#section-4 .edit-summary" "css_element"
    And I set the section name to "Topic 4"
    And I apply asset completion restriction "S3 Completion - view" to section
    And I go to course section 4
    And I should see availability info "Not available unless: The activity S3 Completion - view is marked complete" in "section" "4"
    And I go to course section 3
    And I click on "//li[contains(@class, 'modtype_assign')]//a/span[contains(text(), 'S3 Completion - view')]" "xpath_element"
    And I am on the course main page for "C1"
    And I go to course section 4
    And I should see availability info "Not available unless: The activity S3 Completion - view is marked complete" in "section" "4"
    And I log out
    And I log in as "student1"
    And I am on the course main page for "C1"
    And I should not see "Conditional" in TOC item 1
    And I should see "Conditional" in TOC item 2
    And I should not see "Conditional" in TOC item 3
    And I go to course section 1
    And I should not see available from date of "yesterday" in section 1
    And I should see available from date of "tomorrow" in the 2nd asset within section 1
    And I go to course section 2
    And I should see available from date of "tomorrow" in section 2
    And "#section-2 li.snap-activity" "css_element" should not exist
    And I go to course section 4
    And I should see availability info "Not available unless: The activity S3 Completion - view is marked complete" in "section" "4"
    And I go to course section 3
    And I click on "//li[contains(@class, 'modtype_assign')]//a/span[contains(text(), 'S3 Completion - view')]" "xpath_element"
    And I am on the course main page for "C1"
    And I go to course section 4
    And I should not see availability info "Not available unless: The activity S3 Completion - view is marked complete" in "section" "4"
