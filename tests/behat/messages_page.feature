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
# @autor      Rafael Becerra rafael.becerrarodriguez@openlms.net
# @copyright  Copyright (c) 2019 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @theme_snap_message
Feature: When the Moodle theme is set to Snap, message page should be accessible.

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
    Given I log in as "admin"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I click on "//a[small[contains(@id, \"snap-pm-messages\")]]" "xpath_element"
    And ".message-app.main" "css_element" should be visible
    # A message drawer floating div gets renderer but outside of the window
    Then ".drawer .message-app" "css_element" should not be visible
    And I should see "Starred"
    And I should see "Group"
    And I should see "Private"
    And "div.panel-header-container div[data-region='view-overview'] a[data-route='view-contacts']" "css_element" should exist
    And I click on "div.panel-header-container div[data-region='view-overview'] a[data-route='view-contacts']" "css_element"
    And I should see "Contacts"
    And I should see "Requests"
    And I click on "div.message-app.main div.body-container a[data-action='show-contacts-section']" "css_element"
    And I click on "div.message-app.main div.body-container a[data-action='show-requests-section']" "css_element"

  @javascript
  Scenario: When admin review messages preferences of other users, message drawer should not appear
    Given I log in as "admin"
    And the following config values are set as admin:
      | linkadmincategories | 1 |
    And I close the personal menu
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I follow "Users"
    And I follow "Accounts"
    And I follow "Browse list of users"
    And I should see "Student 1"
    And I follow "Student 1"
    And I should see "Preferences"
    And I click on "//span/a[contains(text(),\"Preferences\")]" "xpath_element"
    And I follow "Message preferences"
    Then ".drawer .message-app" "css_element" should not be visible

  @javascript
  Scenario: In personal menu preferences page, it must be possible to click the items.
    Given I log in as "admin"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I click on "//a[@id='snap-pm-preferences']" "xpath_element"
    And I follow "Message preferences"
    And I should see "Settings"
    And I should see "Privacy"
    And I should see "Notification preferences"
    Then ".drawer .message-app" "css_element" should be visible

  @javascript
  Scenario: When selecting messages of a contact, it must be possible to click the items.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I wait until the page is ready
    And I click on "//a[contains(text(),\"Teacher 1\")]/following-sibling::small/a" "xpath_element"
    And ".message-app.main" "css_element" should be visible
    # A message drawer floating div gets renderer but outside of the window
    Then ".drawer .message-app" "css_element" should not be visible

  @javascript
  Scenario: When selecting messages in profile, a new messages window opens.
    Given I log in as "admin"
    And I open the personal menu
    And I click on "//a[@id='snap-pm-profile']" "xpath_element"
    And I wait until the page is ready
    And I click on "//a[@id='message-user-button']" "xpath_element"
    And I wait until the page is ready
    And I check body for classes "theme-snap"
    And "body#page-message-index" "css_element" should exist

  @javascript
  Scenario: Accessing the messages from the user report in the grader redirects to the message index page.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I follow "Course Dashboard"
    And I follow "Gradebook"
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "User report" in current page administration
    And I click on "div.search-widget" "css_element"
    And I click on "div.searchresultscontainer a[role='menuitem']" "css_element"
    And I click on "#message-user-button" "css_element"
    Then "body#page-message-index" "css_element" should exist
