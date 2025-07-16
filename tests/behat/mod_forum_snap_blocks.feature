# This file is part of Moodle - https://moodle.org/
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
# along with Moodle. If not, see <https://www.gnu.org/licenses/>.
#
# Test to verify the rendering of the Page Activity expand
# icon on the Course page based on its configured settings.
#
# @package    theme_snap
# @copyright  Copyright (c) 2025 Open LMS (https://www.openlms.net)
# @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_mod_forum
Feature: Edit forum as a teacher

  Background: Add a forum and a discussion attaching files
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activity" exists:
      | activity    | forum      |
      | course      | C1         |
      | idnumber    | 0001       |
      | name        | Test forum |
      | type        | general    |

    @javascript
    Scenario: All Forum administrative options are visible
      Given I log in as "teacher1"
      When I am on the course main page for "C1"

      When I am on the "Test forum" "forum activity" page
      And I click on "a[title='Toggle admin drawer']" "css_element"

      Then I should see "Settings"
      And I should see "Locally assigned roles"
      And I should see "Permissions"
      And I should see "Check permissions"
      And I should see "Filters"
      And I should see "Competency breakdown"
      And I should see "Logs"
      And I should see "Backup"
      And I should see "Restore"
      And I should see "Advanced grading"
      And I should see "Subscription mode"
      And I should see "Subscriptions"
      And I should see "Reports"
      And I should see "Export"
