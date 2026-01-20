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
# Tests for section progress indicators in course index.
#
# @package   theme_snap
# @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course_index
Feature: Section progress indicators in course index

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion | initsections |
      | Course 1 | C1        | topics | 1                | 3            |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role   |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name       | course | section | completion | completionview |
      | resource | Resource 1 | C1     | 3       | 0          | 0              |

  @javascript
  Scenario: Section progress does not appear for sections without completion tracking
    Given I log in as "student1"
    And I am on the course main page for "C1"
    Then I should not see "Progress:" in the "#course-index" "css_element"

  @javascript
  Scenario: Section progress indicator displays and updates correctly
    Given the following "activities" exist:
      | activity | name         | course | section | completion | completionview |
      | assign   | Assignment 1 | C1     | 1       | 1          | 0              |
      | assign   | Assignment 2 | C1     | 1       | 1          | 0              |
    And I log in as "student1"
    And I am on the course main page for "C1"
    And I go to section 1 of course "C1"
    And I should see "Progress: 0/2"
    When I mark the activity "Assignment 1" as complete
    Then I should see "Progress: 1/2"
    When I mark the activity "Assignment 2" as complete
    Then I should see "Progress: 2/2"