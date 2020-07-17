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
# Tests for sending messages in snap.
#
# @package    theme_snap
# @autor      Rafael Monterroza rafael.monterroza@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @snap_message @javascript
Feature: Snap message send messages
  As a user
  I need to be able to send a message

  Background:
    Given I create the following course categories:
      | id | name   | category | idnumber | description |
      |  5 | Cat 5  |     0    |   CAT5   |   Test      |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | CAT5     | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | student3 | C1     | student |
    And the following "groups" exist:
      | name    | course | idnumber | enablemessaging |
      | Group 1 | C1     | G1       | 1               |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1 |
      | student2 | G1 |
    And the following config values are set as admin:
      | messaging        | 1 |
      | messagingminpoll | 1 |

  Scenario: Send a message to a group conversation in snap
    Given I log in as "student1"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "//span[contains(text(),\"Group\")]" "xpath_element"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    When I send "Hi!" message in the message area
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    And I log out
    And I log in as "student2"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I should see "(1)" in the ".section[data-region='view-overview-group-messages']" "css_element"
    And I should see "1" in the ".badge-primary[data-region='section-unread-count'][aria-label='There are 1 unread conversations']" "css_element"
    And I should see "1" in the ".badge-primary[data-region='unread-count'][aria-label='There are 1 unread messages']" "css_element"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    Then ".badge-primary.hidden[data-region='unread-count'][aria-label='There are 1 unread messages']" "css_element" should exist
    Then ".badge-primary.hidden[data-region='section-unread-count'][aria-label='There are 1 unread conversations']" "css_element" should exist

  Scenario: Send a message to a starred conversation in snap
    Given I log in as "student1"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "//span[contains(text(),\"Group\")]" "xpath_element"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    And I click on "conversation-actions-menu-button" "button"
    And I click on "Star" "link" in the "//div[@data-region='header-container']" "xpath_element"
    And I click on "//span[contains(text(),\"Starred\")]" "xpath_element"
    And I should see "Group 1"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    And I send "Hi!" message in the message area
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    And I click on "//span[contains(text(),\"Group\")]" "xpath_element"
    And I should see "No group conversations"
    And I log out
    And I log in as "student2"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I should see "(1)" in the ".section[data-region='view-overview-favourites']" "css_element"
    And I should see "1" in the ".badge-primary[data-region='section-unread-count'][aria-label='There are 1 unread conversations']" "css_element"
    And I should see "1" in the ".badge-primary[data-region='unread-count'][aria-label='There are 1 unread messages']" "css_element"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    Then ".badge-primary.hidden[data-region='unread-count'][aria-label='There are 1 unread messages']" "css_element" should exist
    Then ".badge-primary.hidden[data-region='section-unread-count'][aria-label='There are 1 unread conversations']" "css_element" should exist

  Scenario: Send a message to a private conversation via contacts in snap
    Given the following "message contacts" exist:
      | user     | contact |
      | student1 | student2 |
      | student3 | student2 |
    And I log in as "student1"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "Contacts" "link"
    And I click on "Student 2" "link" in the "//*[@data-section='contacts']" "xpath_element"
    When I send "Hi!" message in the message area
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    And I log out
    And I log in as "student3"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "Contacts" "link"
    And I click on "Student 2" "link" in the "//*[@data-section='contacts']" "xpath_element"
    When I send "Hello!" message in the message area
    Then I should see "Hello!" in the ".d-flex[data-region='day-messages-container']" "css_element"
    When I send "How are you?" message in the message area
    Then I should see "How are you?" in the ".d-flex[data-region='day-messages-container']" "css_element"
    And I log out
    And I log in as "student2"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I should see "(2)" in the ".section[data-region='view-overview-messages']" "css_element"
    And I should see "2" in the ".badge-primary[data-region='section-unread-count'][aria-label='There are 2 unread conversations']" "css_element"
    And I should see "1" in the ".badge-primary[data-region='unread-count'][aria-label='There are 1 unread messages']" "css_element"
    And I should see "2" in the ".badge-primary[data-region='unread-count'][aria-label='There are 2 unread messages']" "css_element"
    And I click on ".rounded-circle[alt='Student 3']" "css_element"
    And I should see "Hello!" in the ".d-flex[data-region='day-messages-container']" "css_element"
    And I should see "How are you?" in the ".d-flex[data-region='day-messages-container']" "css_element"
    Then ".badge-primary.hidden[data-region='unread-count'][aria-label='There are 2 unread messages']" "css_element" should exist
    And I should see "1" in the ".badge-primary[data-region='section-unread-count'][aria-label='There are 2 unread conversations']" "css_element"
    And I click on ".rounded-circle[alt='Student 1']" "css_element"
    Then I should see "Hi!" in the ".d-flex[data-region='day-messages-container']" "css_element"
    Then ".badge-primary.hidden[data-region='unread-count'][aria-label='There are 1 unread messages']" "css_element" should exist
    Then ".badge-primary.hidden[data-region='section-unread-count'][aria-label='There are 2 unread conversations']" "css_element" should exist

  Scenario: Message bubble should have a specific color instead of site color.
    And I skip because "This test is failing for 3.8. To be fixed in INT-15793"
    Given I log in as "student1"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "//span[contains(text(),\"Group\")]" "xpath_element"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    And I click on "conversation-actions-menu-button" "button"
    And I click on "Star" "link" in the "//div[@data-region='header-container']" "xpath_element"
    And I click on "//span[contains(text(),\"Starred\")]" "xpath_element"
    And I should see "Group 1"
    And I click on ".rounded-circle[alt='Group 1']" "css_element"
    And I send "Hi!" message in the message area
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    And I check element ".message-app .message.bg-secondary" with property "background-color" = "#E6E6E6"
    And I check element ".message-app .message.send .tail" with property "border-bottom-color" = "#E6E6E6"

  @javascript
  Scenario: When a user has unread conversations, a notification should appear in the message icon on the navigation bar
  and should redirect to the message page.
    Given the following "message contacts" exist:
      | user     | contact |
      | student1 | student2 |
      | student3 | student2 |
    And I log in as "student1"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "Contacts" "link"
    And I click on "Student 2" "link" in the "//*[@data-section='contacts']" "xpath_element"
    When I send "Hi!" message in the message area
    Then I should see "Hi!" in the ".message.clickable[data-region='message']" "css_element"
    And I log out
    And I log in as "student3"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I follow "View my messages"
    And I click on "Contacts" "link"
    And I click on "Student 2" "link" in the "//*[@data-section='contacts']" "xpath_element"
    When I send "Hello!" message in the message area
    Then I should see "Hello!" in the ".d-flex[data-region='day-messages-container']" "css_element"
    And I log out
    And I log in as "student2"
    And I am on site homepage
    And "#mr-nav .badge-count-container .snap-message-count" "css_element" should exist
    And "//a[@class='snap-message-count']//div[text()='2']" "xpath_element" should exist
    And the "aria-label" attribute of "#mr-nav .badge-count-container .snap-message-count i.icon.fa-comment" "css_element" should contain "Open messaging drawer. There are 2 unread conversations"
    And I click on "#mr-nav .badge-count-container .snap-message-count" "css_element"
    And I should see "(2)" in the ".section[data-region='view-overview-messages']" "css_element"
    And I should see "2" in the ".badge-primary[data-region='section-unread-count'][aria-label='There are 2 unread conversations']" "css_element"
    # Now we need to see that the unread message notification disappears after the message are read.
    And I click on ".message-app .panel-body-container .view-overview-body div[data-region='view-overview-messages'] .list-group[data-region='content-container'] a.list-group-item:nth-child(1)" "css_element"
    Then I should see "Hello!" in the ".d-flex[data-region='day-messages-container']" "css_element"
    And I click on ".message-app .panel-body-container .view-overview-body div[data-region='view-overview-messages'] .list-group[data-region='content-container'] a.list-group-item:nth-child(2)" "css_element"
    Then I should see "Hi!" in the ".d-flex[data-region='day-messages-container']" "css_element"
    And I am on site homepage
    And the "aria-label" attribute of "#mr-nav .badge-count-container .snap-message-count i.icon.fa-comment" "css_element" should contain "Open messaging drawer. There are 0 unread conversations"

  @javascript
  Scenario: Message icon should change its color when the category color changes.
    Given the following config values are set as admin:
      | category_color | {"5":"#510038"} | theme_snap |
    And I log in as "admin"
    And I follow "Browse all courses"
    And I purge snap caches
    And I wait until the page is ready
    And I follow "Cat 5"
    And I check element ".badge-count-container .icon.fa-comment" with color "#510038"