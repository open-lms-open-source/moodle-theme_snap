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
# Test for Snap's Course category page.
#
# @package    theme_snap
# @autor      Daniel Cifuentes
# @copyright  Copyright (c) 2024 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Users can interact with the different options in the course index category page in Snap.

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |

  @javascript
  Scenario: Users can interact with blocks in the course category page.
    Given I log in as "admin"
    And I follow "My Courses"
    And I click on "Browse all courses" "link"
    And I follow "Manage courses"
    When I click on "Courses" "link" in the ".breadcrumb" "css_element"
    And I should see "Manage courses"
    And I should see "Add a new course"
    And I switch edit mode in Snap
    And I wait until the page is ready
    And I should see "Add a block"
    And I set the field with xpath "//select[@class = 'custom-select singleselect']" to "Calendar"
    And I wait until the page is ready
    And "#moodle-blocks" "css_element" should exist