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
# along with Moodle. If not, see <http://www.gnu.org/licenses/>.
#
# Tests for the user profile page.
#
# @package    theme_snap
# @copyright  Copyright (c) 2021 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the Moodle theme is set to Snap, the user profile picture should not be a link in the user profile page.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | 1        | user1@example.com    |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course 1  | C1        | 0        |

  And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | C1     | student |

  @javascript
  Scenario: User picture in the user profile page should not be a link
    And I log in as "user1"
    And I open the user menu
    And I follow "Profile"
    And "#page-user-profile div.page-header-image > a" "css_element" should not exist
    And "Dashboard" "link" should exist in the "#page-user-profile #page-header > nav.breadcrumb-nav" "css_element"

  @javascript
  Scenario: Message button should display its text on an enrolled user's profile
    Given I am on the "Course 1" "enrolled users" page logged in as "admin"
    And I follow "User 1"
    Then "#message-user-button" "css_element" should exist
    And "#message-user-button .header-button-title" "css_element" should exist
    And "#message-user-button .header-button-title" "css_element" should be visible
    And I should see "Message" in the "#message-user-button .header-button-title" "css_element"