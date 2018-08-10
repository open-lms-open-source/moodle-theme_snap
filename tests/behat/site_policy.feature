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
# Tests for site policy redirects.
#
# @package    theme_snap
# @copyright  Copyright (c) 2017 Blackboard Inc.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: As an authenticated non-admin user, opening the snap personal menu redirects to the site policy acceptance
  page when not previously accepted.

  Background:
    Given the following config values are set as admin:
      | defaulthomepage | 0                           |
      | sitepolicy      | http://somesitepolicy.local |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |

  @javascript
  Scenario: Opening personal menu redirects to site policy page appropriately when personal menu set to show on login.
    Accepting the site policy prevents redirect on next login.
    Given I log in as "student1"
    And I have been redirected to the site policy page
    And I press "Yes"
    And I log out
    When I log in as "student1"
    Then I am currently on the default site home page

  @javascript
  Scenario: Opening personal menu redirects to site policy page appropriately when personal menu set to not show on login.
    Accepting the site policy prevents redirect next time personal menu is opened.
    Given the following config values are set as admin:
      | personalmenulogintoggle | 0 | theme_snap |
    And I skip because "Site policy changes in core changed functionality"
    And I log in as "student1"
    And I am currently on the default site home page
    When I open the personal menu
    Then I have been redirected to the site policy page
    And I press "Yes"
    And I log out
    When I log in as "student1"
    Then I am currently on the default site home page

  @javascript
  Scenario: Opening personal menu does not redirect when logged in as admin user.
    Given I log in as "admin"
    Then I am currently on the default site home page
