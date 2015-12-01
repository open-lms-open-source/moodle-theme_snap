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
      | thememobile | snap |
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
  Scenario: In read mode, teacher moves section 1 to section 2.
    Given I log in with snap as "teacher1"
    And I follow "Menu"
    And I follow "Course"
    And I wait until the page is ready
    And I follow "Topic 1"
    And I follow "Untitled Topic"
    And I set the following fields to these values:
      | name | My topic |
    And I press "Save changes"
    And I wait until the page is ready
    And I follow "Move section"
   Then I should see "Moving \"My topic\"" in the "#snap-move-message" "css_element"
    And I follow "Topic 4"
   Then I should see "Place section \"My topic\" before section \"Topic 4\""
    And I wait until the page is ready
    And I should see "My topic" in the "#section-4" "css_element"

  @javascript
  Scenario: In read mode, student cannot move sections.
    Given I log in with snap as "student1"
    And I follow "Menu"
    And I follow "Course"
    And I wait until the page is ready
    And I follow "Topic 1"
   Then "a[title=Move section]" "css_element" should not exist
