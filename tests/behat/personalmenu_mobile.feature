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
# Tests for Snap personal menu on mobile devices.
#
# @package    theme_snap
# @copyright  Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, students and teachers can open a personal menu which works responsively
  on mobile devices.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario Outline: Teacher / Student can view personal menu on a mobile device.
    Given I change window size to "320x480"
    And I log in as "<user>" (theme_snap)
    And I open the personal menu
    And I follow "Deadlines" in the mobile personal menu
    Then I should see "You have no upcoming deadlines."
    # This is deliberately not in the order of the icons as the default pane shows courses so we need to switch to
    # something else first.
    And I follow "Courses" in the mobile personal menu
    Then I should see "Course 1"
    And I follow "<gradealt>" in the mobile personal menu
    Then I should see "<gradenotice>"
    And I follow "Messages" in the mobile personal menu
    Then I should see "You have no messages."
    And I follow "Forum posts" in the mobile personal menu
    Then I should see "You have no relevant forum posts."
    And I click on "#fixy-close" "css_element"
    And I open the personal menu
    And I wait until "#fixy-mobile-menu" "css_element" is visible

    Examples:
    | user     | gradealt | gradenotice                       |
    | teacher1 | Grading  | You have no submissions to grade. |
    | student1 | Feedback | You have no recent feedback.      |

  @javascript
  Scenario: Teacher / Student using Joule can view alerts in the personal menu on a mobile device.
    Given I am using Joule
    And I change window size to "320x480"
    And the message processor "badge" is enabled
    And I log in as "student1" (theme_snap)
    And I open the personal menu
    And I follow "Deadlines" in the mobile personal menu
    Then I should see "You have no upcoming deadlines."
    # This is deliberately not in the order of the icons as the default pane shows courses so we need to switch to
    # something else first.
    And I follow "Courses" in the mobile personal menu
    Then I should see "Course 1"
    And I follow "Messages" in the mobile personal menu
    Then I should see "You have no messages."
    And I follow "Forum posts" in the mobile personal menu
    Then I should see "You have no relevant forum posts."
    And I follow "Alerts" in the mobile personal menu
    Then I should see "You have no unread alerts."
    And I click on "#fixy-close" "css_element"
    And I open the personal menu
    And I wait until "#fixy-mobile-menu" "css_element" is visible

  @javascript
  Scenario Outline: Mobile menu icons (excluding alerts) only appear when enabled.
    Given I change window size to "320x480"
    And the following config values are set as admin:
      | <toggle> | 0 | theme_snap |
    And I log in as "student1" (theme_snap)
    And I open the personal menu
    Then "a[href='<href>']" "css_element" should not exist
    And the following config values are set as admin:
      | <toggle> | 1 | theme_snap |
    And I reload the page
    And I open the personal menu
    Then "a[href='<href>']" "css_element" should exist
    Examples:
    | toggle           | href                           |
    | deadlinestoggle  | #snap-personal-menu-deadlines  |
    | feedbacktoggle   | #snap-personal-menu-graded     |
    | messagestoggle   | #snap-personal-menu-messages   |
    | forumpoststoggle | #snap-personal-menu-forumposts |

  @javascript
  Scenario: Alerts mobile menu icon only appears when alerts enabled.
    Given I am using Joule
    And I change window size to "320x480"
    And the message processor "badge" is disabled
    And I log in as "student1" (theme_snap)
    And I open the personal menu
    Then "a[href='#snap-personal-menu-badges']" "css_element" should not exist
    And the message processor "badge" is enabled
    And I reload the page
    And I open the personal menu
    Then "a[href='#snap-personal-menu-badges']" "css_element" should exist


