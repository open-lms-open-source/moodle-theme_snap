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
# Tests for visibility of activity restriction tags.
#
# @package    theme_snap
# @copyright Copyright (c) 2018 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @theme_snap_login
Feature: When the moodle theme is set to Snap, the custom snap login form should be shown.

  @javascript
  Scenario: The login template must contain the custom snap form.
    Given I am on login page
    And I check element "#login" has class "snap-custom-form"

  @javascript
  Scenario: The login template must change when the Stylish template is selected.
    Given I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Appearance" node
    And I click on "Category: Themes" "link"
    And I follow "Snap"
    And I click on "Login page" "link"
    And I should see "Stylish template"
    And I set the field with xpath "//select[@id='id_s_theme_snap_loginpagetemplate']" to "Stylish"
    And I click on "Save changes" "button"
    And I log out
    And I am on login page
    Then ".page-stylish-login" "css_element" should exist