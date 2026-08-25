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
# Tests for the user tours in Snap.
#
# @package    theme_snap
# @copyright  Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_usertours
Feature: The reset user tour link is visible in the Snap footer
  In order to replay a user tour
  As a user
  I need the "Reset user tour on this page" link to be visible in Snap's footer

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: The reset link is visible in Snap's footer on a course page
    Given a user tour named "Snap course tour" exists for URL match "/course/view.php%"
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "Snap course tour content"
    And I press "Got it"
    And I should not see "Snap course tour content"
    Then "#moodle-footer .tool_usertours-resettourcontainer #resetpagetour" "css_element" should exist
    And "Reset user tour on this page" "link" should be visible

  @javascript
  Scenario: Clicking the reset link in Snap's footer replays the tour
    Given a user tour named "Snap course tour" exists for URL match "/course/view.php%"
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "Snap course tour content"
    And I press "Got it"
    And I should not see "Snap course tour content"
    When I click on "Reset user tour on this page" "link" in the "#moodle-footer" "css_element"
    Then I should see "Snap course tour content"

  @javascript
  Scenario: The reset link is visible in Snap's footer on the My courses page
    Given a user tour named "Snap my courses tour" exists for URL match "/my/courses.php"
    And I log in as "teacher1"
    And I visit "/my/courses.php"
    And I should see "Snap my courses tour content"
    And I press "Got it"
    Then "#moodle-footer .tool_usertours-resettourcontainer #resetpagetour" "css_element" should exist
    And "Reset user tour on this page" "link" should be visible
