# This file is part of Moodle - https://moodle.org/
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
# along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
#
# Test for intermediate page in file and url activities
#
# @package    theme_snap
# @author     Farhan Karmali <farhan.karmali@openlms.net>
# @copyright  Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Admin cog appears on course question banks page
  As a site administrator
  I need to see the admin cog on the course question banks page
  So that I can access course administration settings

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |

  @javascript
  Scenario: Admin cog is visible on the question banks page for a site admin using the snap theme
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    When I navigate to "Question banks" in current page administration
    Then "#admin-menu-trigger" "css_element" should exist