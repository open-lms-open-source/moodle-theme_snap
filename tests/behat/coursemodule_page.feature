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
# Tests for page module.
#
# @package    theme_snap
# @copyright  Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @_bug_phantomjs
Feature: Open page module inline
  As any user
  I need to view page modules inline and have auto completion tracking updated.

  Background:
    Given the following config values are set as admin:
      | enablecompletion | 1 |
      | theme | snap |
      | thememobile | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1 | 0 | 1 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |

  @javascript
  Scenario: Page mod is created and opened inline.
    Given the following "activities" exist:
      | activity | course | idnumber | name       | intro        | content       | completion | completionview |
      | page     | C1     | page1    | Test page1 | Test page 1  | page content1 | 0          | 0              |
    And I log in with snap as "student1"
    And I follow "Menu"
    And I follow "Course"
    And I should not see "page content1"
    And I follow "Read more&nbsp;»"
    And I wait until ".pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    # The above step basically waits for the page content to load up.
    And I should see "page content1"


  @javascript
  Scenario: Page mod is created with auto completion tracking enabled and opened inline.
    Given the following "activities" exist:
      | activity | course | idnumber | name       | intro        | content       | completion | completionview |
      | page     | C1     | page2    | Test page2 | Test page 2  | page content2 | 2          | 1              |
    And I log in with snap as "student1"
    And I follow "Menu"
    And I follow "Course"
    And I should not see "page content2"
    And "span.autocompletion img[title='Not completed: Test page2']" "css_element" should exist
    And I follow "Read more&nbsp;»"
    And I wait until ".pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    # The above step basically waits for the page content to load up.
    And I should see "page content2"
    And "span.autocompletion img[title='Not completed: Test page2']" "css_element" should not exist
    And "span.autocompletion img[title='Completed: Test page2']" "css_element" should exist
