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
# @author     2015 Guy Thomas <gthomas@moodlerooms.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, conditional restrictions work as normal.

  Background:
    Given the following config values are set as admin:
      | theme              | snap |
      | enablecompletion   | 1    |
      | enableavailability | 1    |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "activities" exist:
      | activity | course | idnumber | name                        | intro                     | section | assignsubmission_onlinetext_enabled |
      | assign   | C1     | assign1  | S1 Restricted - date past   | Restricted by date past   | 1       | 1                                   |
      | assign   | C1     | assign2  | S1 Restricted - date future | Restricted by date future | 1       | 1                                   |
      | assign   | C1     | assign3  | S2 Restricted - date past   | Restricted by date past   | 2       | 1                                   |
      | assign   | C1     | assign4  | S2 Restricted - date future | Restricted by date future | 2       | 1                                   |
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
  Given I log in as "teacher1" (theme_snap)
    And I am on the course main page for "C1"
    And I go to course section 1
    And I restrict course asset "S1 Restricted - date past" by date to "yesterday"
    And I restrict course asset "S1 Restricted - date future" by date to "tomorrow"
    And I should see available from date of "yesterday" in the 1st asset within section 1
    And I should see available from date of "tomorrow" in the 2nd asset within section 1
    And I go to course section 2
    And I restrict course asset "S2 Restricted - date past" by date to "yesterday"
    And I restrict course asset "S2 Restricted - date future" by date to "tomorrow"
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
    And I log out (theme_snap)
    And I log in as "student1" (theme_snap)
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
