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
# @autor      Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, message page should be accessible.

  @javascript
  Scenario: In messages page, it must be possible to click the items.

    Given I log in as "admin"
    And I am on site homepage
    And I click on ".js-snap-pm-trigger.snap-my-courses-menu" "css_element"
    And I click on "#snap-pm-updates section:nth-child(4) a.snap-personal-menu-more" "css_element"
    And I should see "Starred"
    And I should see "Group"
    And I should see "Private"
    And "div.header-container div[data-region='view-overview'] div.text-right.mt-3 a[data-route='view-contacts']" "css_element" should exist
    And I click on "div.header-container div[data-region='view-overview'] div.text-right.mt-3 a" "css_element"
    And I should see "Contacts"
    And I should see "Requests"
    And I click on "div.header-container div[data-region='view-contacts'] a" "css_element"
    And I click on "div.header-container div[data-region='view-overview'] .ml-2 a" "css_element"
    And I should see "Settings"
