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
# Tests for toggle course section visibility in non edit mode in snap.
#
# @package    theme_snap
# @author     Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Check that the correct attributes exists for URL field in a database activity template.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |

  @javascript
  Scenario: Url type and Url autocomplete should exists for input Url in the "Add entry" for Database activity.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    # Create database activity and allow editing of
    # approved entries.
    And I add a "Database" to section "1" and I fill the form with:
      | Name              | Test database name |
      | Description       | Test               |
    And I click on "li.modtype_data a.mod-link" "css_element"
    # To generate the default templates.
    And I click on "//div[@class='fieldadd']//select[@class='custom-select singleselect']//option[@value='url']" "xpath_element"
    And I set the field "Field name" to "Data URL"
    And I click on "Add" "button"
    And I should see "Field added"
    And I click on "//a[contains(@title, 'Add entry')]" "xpath_element"
    And the "type" attribute of "input.mod-data-input.form-control.d-inline" "css_element" should contain "url"
    And the "autocomplete" attribute of "input.mod-data-input.form-control.d-inline" "css_element" should contain "url"
