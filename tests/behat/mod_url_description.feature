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

@theme @theme_snap

Feature: When the moodle theme is set to Snap, the users see an intermediate page to display the description in mod_url.

  Background:
    Given the following config values are set as admin:
      | allowstealth | 1    |
      | theme        | snap |
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

  Scenario: As a teacher I should see an intermediate page with the description in mod_url.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
	And I follow "Url 1"
	And I wait until the page is ready
    Then I should see "Test url description"