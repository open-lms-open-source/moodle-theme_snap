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
# @autor      Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, message page should be accessible.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | admin    | C1     | editingteacher |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: In messages page, it must be possible to click the items.
    Given I skip because "The message UI for Snap has changed, more steps are required."
    Given I log in as "admin"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I click on "#snap-pm-updates section:nth-child(4) a.snap-personal-menu-more" "css_element"
    And I should see "Starred"
    And I should see "Group"
    And I should see "Private"
    And "div.header-container div[data-region='view-overview'] div.text-right.mt-3 a[data-route='view-contacts']" "css_element" should exist
    And I click on "div.header-container div[data-region='view-overview'] div.text-right.mt-3 a" "css_element"
    And I should see "Contacts"
    And I should see "Requests"
    And I click on "div.header-container div[data-region='view-contacts'] a" "css_element"
    And I click on "div.header-container div[data-region='view-overview'] .ml-2 a" "css_element"
    And I should see "Settings"

  @javascript
  Scenario: When admin review messages preferences of other users, message drawer should not appear
    Given I skip because "Current error from core breaks the test"
    Given I log in as "admin"
    And the following config values are set as admin:
      | linkadmincategories | 0 |
    And I close the personal menu
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Users" node
    And I expand "Accounts" node
    And I follow "Browse list of users"
    And I should see "Student 1"
    And I follow "Student 1"
    And I should see "Preferences"
    And I click on "//span/a[contains(text(),\"Preferences\")]" "xpath_element"
    And I follow "Message preferences"
    Then ".message-drawer" "css_element" should not be visible