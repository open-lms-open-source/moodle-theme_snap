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
# Tests for the icon activities color admin settings in Snap theme.
#
# @package   theme_snap
# @copyright Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the moodle theme is set to Snap, admins can change the activity icon colors.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | initsections |
      | Course 1 | C1        | topics | 1            |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | course | section | name         |
      | assign   | C1     | 1       | Assignment 1 |
    And the following config values are set as admin:
      | linkadmincategories | 0 |

  @javascript
  Scenario: Admin can see the icon activities color section in Snap Basics settings.
    Given I log in as "admin"
    And I am on site homepage
    And I go to "Site administration > Appearance > Themes" in snap administration
    And I follow "Edit theme settings 'Snap'"
    And I click on "Basics" "link"
    Then I should see "Icon activities color"
    And I should see "Administration activities"
    And I should see "Assessment activities"
    And I should see "Collaboration activities"
    And I should see "Communication activities"
    And I should see "Interactive content activities"
    And I should see "Resource activities"

  @javascript
  Scenario: Changing assessment activity color updates the border on assignment cards.
    Given I log in as "admin"
    And I am on site homepage
    And I go to "Site administration > Appearance > Themes" in snap administration
    And I follow "Edit theme settings 'Snap'"
    And I click on "Basics" "link"
    And I should see "Icon activities color"
    And I set the following fields to these values:
      | s_theme_snap_assessactivitiescolor | #FF0000 |
    And I js click on "Save changes" "button"
    And I wait until the page is ready
    And I should see "Changes saved"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Section 1"
    Then I check element "li.activity.modtype_assign .activityiconcontainer.assessment .activityicon" has filter for color "#FF0000"
