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
# Tests for course favoriting in the Snap personal menu.
#
# @package    theme_snap
# @copyright  Copyright (c) 2017 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_personalmenu
Feature: When the moodle theme is set to Snap, expired courses appear in an archive section within the personal menu.
  For non archived courses, it is possible to favorite and un-favorite them.

  Background:
    Given the following "courses" exist:
      | fullname      | shortname | category | groupmode | visible | startdate     | enddate       |
      | Course 1      | C1        | 0        | 1         | 1       |               |               |
      | Course 2      | C2        | 0        | 1         | 1       | ##yesterday## | ##tomorrow##  |
      | Course 3      | C3        | 0        | 1         | 1       | ##-1 years##  | ##next year## |
      | Past-course 1 | PC1       | 0        | 1         | 1       | ##-2 years##  | ##last year## |
      | Past-course 2 | PC2       | 0        | 1         | 1       | ##-2 years##  | ##last year## |
      | Past-course 3 | PC3       | 0        | 1         | 1       | ##-2 years##  | ##last year## |
      | Course H      | CH        | 0        | 1         | 0       |               |               |
      | Past Course H | PCH       | 0        | 1         | 0       | ##-3 years##  | ##-2 years##  |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student1 | C2     | student        |
      | student1 | C3     | student        |
      | student1 | PC1    | student        |
      | student1 | PC2    | student        |
      | student1 | PC3    | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | teacher1 | C3     | editingteacher |
      | teacher1 | PC1    | editingteacher |
      | teacher1 | PC2    | editingteacher |
      | teacher1 | PC3    | editingteacher |
      | teacher1 | CH     | editingteacher |
      | teacher1 | PCH    | editingteacher |

  @javascript
  Scenario: User can navigate to and from archived courses sections.
    Given I log in as "student1"
    And I open the personal menu
    And I should see "Courses"
    And I should see "##last year##Y##"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I should not see "Past-course 1"
    And I should not see "Past-course 2"
    And I should not see "Past-course 3"
    And I click on "#snap-pm-accessible-tab .nav-tabs li:not(.active)" "css_element"
    And I should see "Past-course 1"
    And I should see "Past-course 2"
    And I should see "Past-course 3"
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I click on "#snap-pm-tab-current" "css_element"
    And I should not see "Past-course 1"
    And I should not see "Past-course 2"
    And I should not see "Past-course 3"

  @javascript
  Scenario: User can favorite courses in current / hidden section but not archive sections.
    Given I log in as "student1"
    And I open the personal menu
    And I should see "Courses"
    And Favorite toggle exists for course "C1"
    And Favorite toggle exists for course "C2"
    And Favorite toggle exists for course "C3"
    And Course card "C1" appears before "C2"
    And Course card "C2" appears before "C3"
    And I toggle course card favorite "C3"
    Then Course card "C3" is favorited
    And I reload the page
    And I open the personal menu
    Then Course card "C3" appears before "C1"
    And Course card "C1" appears before "C2"
    # Make sure archived courses can not be favorited.
    And I click on "#snap-pm-accessible-tab .nav-tabs li:not(.active)" "css_element"
    And Favorite toggle does not exist for course "PC1"
    And Favorite toggle does not exist for course "PC2"
    And Favorite toggle does not exist for course "PC3"
    # Log out and log in as teacher (make sure they can't see students favorites)
    And I log out
    And I log in as "teacher1"
    And I open the personal menu
    Then Favorite toggle exists for course "C1"
    And Favorite toggle exists for course "C2"
    And Favorite toggle exists for course "C3"
    And I follow "Hidden courses"
    And Favorite toggle exists for course "CH"
    And Course card "C1" is not favorited
    And Course card "C2" is not favorited
    And Course card "C3" is not favorited
    And Course card "CH" is not favorited
    # Test favoriting / unfavoriting
    And I toggle course card favorite "C2"
    Then Course card "C2" is favorited
    And I toggle course card favorite "C2"
    Then Course card "C2" is not favorited
    # Test favoriting / unfavoriting hidden course
    And I toggle course card favorite "CH"
    Then Course card "CH" is favorited
    And I toggle course card favorite "CH"
    Then Course card "CH" is not favorited
    # Make sure archived courses can not be favorited.
    And I click on "#snap-pm-accessible-tab .nav-tabs li:not(.active)" "css_element"
    And Favorite toggle does not exist for course "PC1"
    And Favorite toggle does not exist for course "PC2"
    And Favorite toggle does not exist for course "PC3"

  @javascript
  Scenario: User can favorite / unfavorite courses when all are hidden.
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | visible |
      | Hidden 1 | H1        | 0        | 0       |
      | Hidden 2 | H2        | 0        | 0       |
      | Hidden 3 | H3        | 0        | 0       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher2 | H1     | editingteacher |
      | teacher2 | H2     | editingteacher |
      | teacher2 | H3     | editingteacher |
    Given I log in as "teacher2"
    When I open the personal menu
    Then Favorite toggle exists for course "H1"
    And Favorite toggle exists for course "H2"
    And Favorite toggle exists for course "H3"
    And Course card "H1" appears before "H2"
    And Course card "H2" appears before "H3"
    And I toggle course card favorite "H3"
    Then Course card "H3" is favorited
    And Course card "H3" appears before "H1"
    And Course card "H3" appears before "H2"
    And I toggle course card favorite "H2"
    Then Course card "H2" appears before "H1"
    Then Course card "H2" is favorited
    And I toggle course card favorite "H2"
    Then Course card "H2" is not favorited
    Then Course card "H1" appears before "H2"
    And I reload the page
    And I open the personal menu
    And I follow "Hidden courses"
    Then Course card "H3" appears before "H1"
    And Course card "H1" appears before "H2"
