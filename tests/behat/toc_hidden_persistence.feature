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
# Tests that the TOC-hidden flag is preserved when an activity is duplicated.
#
# @package    theme_snap
# @copyright  Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_toc
Feature: TOC hidden flag is preserved on activity duplication

  Background:
    Given the following config values are set as admin:
      | config | value |
      | theme  | snap  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname    | shortname | format | initsections |
      | Test Course | TC1       | topics | 2            |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC1    | editingteacher |
    And the following "activities" exist:
      | activity | name         | course | section |
      | page     | Visible Page | TC1    | 1       |
      | page     | Hidden Page  | TC1    | 1       |

  @javascript
  Scenario: Duplicating a TOC-hidden activity produces a copy that is also hidden from the TOC
    Given I log in as "teacher1"
    And I am on "Test Course" course homepage
    # Hide the activity from the TOC via its settings.
    And I am on activity "page" "Hidden Page" page
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I click on "Table of Contents settings" "link"
    And I set the field "Do not show this activity in the Table of Contents" to "1"
    And I press "Save and return to course"
    And I wait until the page is ready
    And I go to section 1 of course "TC1"
    And "courseindex-content" "region" should be visible
    And I should not see "Hidden Page" in the "courseindex-content" "region"
    And I should see "Visible Page" in the "courseindex-content" "region"
    # Duplicate the hidden activity.
    And I open "Hidden Page" actions menu
    And I choose "Duplicate" in the open action menu
    And I wait until the page is ready
    # The duplicate must also be absent from the course index.
    And I should not see "Hidden Page (copy)" in the "courseindex-content" "region"
    And I should see "Visible Page" in the "courseindex-content" "region"
    # Reload to confirm the record persisted in the database.
    And I reload the page
    And I should not see "Hidden Page (copy)" in the "courseindex-content" "region"
    And I should see "Visible Page" in the "courseindex-content" "region"
