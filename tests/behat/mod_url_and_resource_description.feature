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
# Test for intermediate page in file and url activities
#
# @package    theme_snap
# @author     Fabian Batioja <fabian.batioja@blackboard.com>
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @_file_upload
Feature: When the moodle theme is set to Snap, the users see an intermediate page to display the description in mod_url and mod_resource.

  Background:
    Given the following config values are set as admin:
      | theme           | snap |
    And the following config values are set as admin:
      | resourcedisplay    | card | theme_snap |
      | displaydescription | 1    | theme_snap |
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
    And the following "activities" exist:
      | activity   | name         | intro                       | course | idnumber  | section | showdescription |
      | url        | Url 1        | Test url description        | C1     | url1      | 0       | 1               |
      | resource   | Resource 1   | Test resource description   | C1     | resource1 | 0       | 1               |

  Scenario: As a teacher I should see an intermediate page with the description in mod_url.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on ".modtype_url a.mod-link" "css_element"
    And I should see "Test url description"
    And "http://moodle.org/" "link" should exist
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on ".modtype_resource a.mod-link" "css_element"
    Then I should see "Test resource description"
    And "resource1.txt" "link" should exist
    And the following config values are set as admin:
      | resourcedisplay | list | theme_snap |
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on ".modtype_url a.mod-link" "css_element"
    And I should not see "Test url description"
    And "http://moodle.org/" "link" should not exist
    