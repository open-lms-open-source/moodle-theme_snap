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
# Tests for My Account general view.
#
# @package    local_myaccount
# @autor      Rafael Becerra
# @copyright  Copyright (c) 2020 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @local_myaccount
Feature: Correct visualization of the general view for My Account plugin only for an Administrator in Snap theme.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
      | student1  | Student    | 1         | student1@example.com  |
    And the following config values are set as admin:
      | linkadmincategories | 0 |

  @javascript
  Scenario: As a Student in Snap, I'm not able to see the My Account link in the personal menu in Snap.
    Given I am using Open LMS
    And I log in as "student1"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should not see "My Account"
    And I log out
    And I log in as "teacher1"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should not see "My Account"

  @javascript
  Scenario: As an admin in Snap, I'm able to see and enter to the My Account page under personal menu.
    Given I am using Open LMS
    Given I log in as "admin"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should see "My Account"
    And I click on "div.snap-pm-user a#snap-pm-myaccount" "css_element"
    # Check the existence of the first view in the page - General.
    And I should see "General"
    # Check for the existence of the main icons for this view.
    And I should see "Open LMS Public roadmap"
    And I should see "Open LMS Community"
    And I should see "Open LMS Latest Releases"
    And I should see "Open bot"

  @javascript
  Scenario: As an Admin in Snap, I'm redirected to specific pages to change Site logo or Site full name in Snap.
    Given I am using Open LMS
    And I log in as "admin"
    And I open the personal menu
    And I wait for the personal menu to be loaded
    And I should see "My Account"
    And I click on "div.snap-pm-user a#snap-pm-myaccount" "css_element"
    # Check the existence of the first view in the page - General.
    And I should see "General"
    And I am on my account default page
    # Verify that the full name site name in the My Account view, is a link redirecting to the Front settings page.
    And I click on "a#myaccount-sitename-link" "css_element"
    And I set the field with xpath "//*[@id='id_s__fullname']" to "Site full name test"
    And I press "Save changes"
    And I am on my account default page
    And I should see "Site full name test"

  @javascript
  Scenario: As an Admin in Snap, I change drift subscription and the Open bot icon should change.
    Given I am using Open LMS
    Given I log in as "admin"
    And I go to my profile page
    And I should see "Drift subscription"
    And I follow "Drift subscription"
    And the field "Drift subscription" matches value "1"
    And I set the field "Drift subscription" to "0"
    And I press "Save changes"
    And the field "Drift subscription" matches value "0"
    And I am on site homepage
    And I am on my account default page
    And I should see "Enable Open bot"
    And I click on "a#myaccount-openbot-sub" "css_element"
    And I should see "Drift Integration Plugin"
    And the field "Drift subscription" matches value "0"
    And I set the field "Drift subscription" to "1"
    And I press "Save changes"
    And I am on my account default page
    # This is to rectify that the drift subscription is turned on, a different icon will appear in My Account general view.
    And I should see "Open bot"
