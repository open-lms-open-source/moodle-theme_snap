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
# Tests for N2018 behat tweaks.
#
# @package    theme_n2018
# @copyright  Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_n2018
Feature: Disable N2018 quick login
  In order to use some advanced authentication functionality
  As an Admin
  I need to be able to disable the N2018 quick login

  @javascript
  Scenario: N2018 quick login is disabled
    Given the following config values are set as admin:
      | theme_n2018_disablequicklogin | 1 |
    And I am on homepage
    And I wait until the page is ready
    Then ".btn.btn-default.n2018-login-button.js-n2018-pm-trigger" "css_element" should not exist

  @javascript
  Scenario: N2018 quick login is not disabled
    Given I am on homepage
    And I wait until the page is ready
    Then ".btn.btn-default.n2018-login-button.js-n2018-pm-trigger" "css_element" should exist
