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
# Tests for inline resource media.
#
# @package    theme_snap
# @author     2015 Guy Thomas <gthomas@moodlerooms.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, clicking on a resource with a media file mime type will open the
  resource inline.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
      | thememobile | snap |
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1 | 0 | 1 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: MP3 opens inline and marked as completed.
    Given I log in as "teacher1" (theme_snap)
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And "#snap-drop-file-1" "css_element" should exist
    And I upload file "test_mp3_file.mp3" to section 1
    Then ".snap-resource[data-type='mp3']" "css_element" should exist
    Then ".snap-resource[data-type='mp3']" "css_element" should exist
    And I click on ".snap-edit-asset" "css_element"
    And I set the following fields to these values:
      | Completion tracking | 2 |
      | Student must view this activity to complete it | 1 |
    And I click on "#id_submitbutton2" "css_element"
    And "span.autocompletion img[title='Not completed: test mp3 file']" "css_element" should exist
    And I click on ".snap-resource[data-type='mp3'] .snap-asset-link a" "css_element"
    And I wait until "#snap-light-box" "css_element" is visible
   Then "#snap-light-box" "css_element" should exist
    And I click on "#snap-light-box-close" "css_element"
   Then "#snap-light-box" "css_element" should not exist
    And "span.autocompletion img[title='Not completed: test mp3 file']" "css_element" should not exist
    And "span.autocompletion img[title='Completed: test mp3 file']" "css_element" should exist
