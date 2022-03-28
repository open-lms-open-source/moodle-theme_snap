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
# Test for book settiing "Display description on course page"
#
# @package    theme_snap
# @author     Oscar Nadjar <oscar.nadjar@openlms.net>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Setting for mod_book should not appear on Snap.

  Background:
    Given the following config values are set as admin:
      | theme        | snap |
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
      | student1  | Student    | 1         | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | student1  | C1      | student         |
      | teacher1  | C1      | editingteacher  |

  @javascript
  Scenario: As a teacher I should not see the setting Display description on course page.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I add a "Book" to section "0"
    And "#id_general .fcontainer .checkbox" "css_element" should exist
    And I should not see "Display description on course page"

  @javascript
  Scenario: As a teacher I should see the setting Subcharter.
    Given I log in as "teacher1"
    And the following "activities" exist:
      | activity   | name         | intro                       | course | idnumber  | section |
      | book       | Book 1       | Test book description       | C1     | book1     | 0       |
    And I am on "Course 1" course homepage
    And I click on "//h3/a/p[contains(text(),'Book 1')]" "xpath_element"
    And I set the following fields to these values:
      | Chapter title | Dummy first chapter |
      | Content | Dummy content |
	And I press "Save changes"
	And I press "Turn editing on"
	And I follow "Add new chapter"
    Then ".checkbox #id_subchapter" "css_element" should exist
    And the "Subchapter" "checkbox" should be enabled
