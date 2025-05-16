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
# Test for Snap's form validation
#
# @package    theme_snap
# @autor      Dayana Pardo
# @copyright  Copyright (c) 2025 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax @theme_snap_form_validation
Feature: Required field validation when creating a new course

  Background:
    Given I log in as "admin"
    And I follow "My Courses"

  @javascript
  Scenario: Admin attempts to create a course with empty required fields
    And I click on "Browse all courses" "link"
    And I click on "Add a new course" "link"
    And I wait "3" seconds
    And I expand all fieldsets
    And I press "Save and display"
    Then I should see "Missing full name"
    And I should see "Missing short name"
    And ".is-invalid" "css_element" should exist