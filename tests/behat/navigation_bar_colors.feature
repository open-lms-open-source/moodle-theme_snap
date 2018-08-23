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
# Test the Category color setting for snap.
#
# @package   theme_snap
# @copyright Copyright (c) 2018 Blackboard Inc. (http://www.blackboard.com)
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, admins can change the color of the Navigation bar buttons.

  @javascript
  Scenario: Go to Snap Navigation bar settings page and set colors for My Courses and Login button.
    Given I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Snap" node in "Site administration>Appearance>Themes"
    And I should see "Navigation bar"
    And I click on "Navigation bar" "link"
    And I should see "Change My Courses button colors"
    And I set the following fields to these values:
      | s_theme_snap_navbarbuttoncolor  | #FF0000 |
      | s_theme_snap_navbarbuttonlink   | #000000 |
      | Change My Courses button colors |    1    |
    And I click on "Save changes" "button"
    And I wait until the page is ready
    And I should see "Changes saved"
    And I check element "a.js-snap-pm-trigger.snap-my-courses-menu" with color "#000000"
    And I check element "#snap-pm-trigger" with property "background-color" = "#FF0000"
    And I log out
    And I check element "a.btn.btn-primary.snap-login-button.js-snap-pm-trigger" with color "#000000"
    And I check element "a.btn.btn-primary.snap-login-button.js-snap-pm-trigger" with property "background-color" = "#FF0000"


