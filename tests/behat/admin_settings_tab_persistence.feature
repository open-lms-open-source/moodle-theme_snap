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
# Test that the active tab persists after saving Snap theme settings.
#
# @package   theme_snap
# @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Snap admin settings tab persistence after saving changes.

  Background:
    Given I log in as "admin"
    And the following config values are set as admin:
      | linkadmincategories | 0 |
    And I am on site homepage
    And I go to "Site administration > Appearance > Themes" in snap administration
    And I follow "Edit theme settings 'Snap'"

  @javascript
  Scenario: Active tab is preserved after saving settings
    # Switch to a non-default tab.
    And I click on "Course display" "link" in the "#snap-admin-tabs" "css_element"
    And I should see "Course display" in the "#snap-admin-tabs .nav-link.active" "css_element"
    # Save settings and verify the tab is still active after page reload.
    And I click on "Save changes" "button"
    And I wait until the page is ready
    Then the "class" attribute of "#snap-admin-tabs .nav-link[href='#themesnapcoursedisplay']" "css_element" should contain "active"
    And the "class" attribute of "#themesnapcoursedisplay" "css_element" should contain "active"

  @javascript
  Scenario: Active tab is preserved when switching between different tabs and saving
    # Switch to Snap feeds tab.
    And I click on "Snap feeds" "link" in the "#snap-admin-tabs" "css_element"
    And I should see "Snap feeds" in the "#snap-admin-tabs .nav-link.active" "css_element"
    # Switch to a different tab before saving.
    And I click on "Navigation bar" "link" in the "#snap-admin-tabs" "css_element"
    And I should see "Navigation bar" in the "#snap-admin-tabs .nav-link.active" "css_element"
    # Save and verify the last active tab is preserved.
    And I click on "Save changes" "button"
    And I wait until the page is ready
    Then the "class" attribute of "#snap-admin-tabs .nav-link[href='#themesnaptopbar']" "css_element" should contain "active"
    And the "class" attribute of "#themesnaptopbar" "css_element" should contain "active"
