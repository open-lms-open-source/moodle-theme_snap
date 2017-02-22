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
# Tests for manual completion.
#
# @package   theme_snap
# @author    Guy Thomas <gthomas@moodlerooms.com>
# @copyright Copyright (c) 2017 Blackboard Inc.
# @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: Bootstrap plugin is made available to global jquery instance without wiping over it.

  Background:
    Given the following config values are set as admin:
      | theme | snap |

  @javascript
  Scenario: Tooltip shows for admin menu.
    Given I log in as "admin" (theme_snap)
    And I see a bootstrap tooltip on hovering over the admin menu

  @javascript
  Scenario: Bootstrap code does not overwrite global jquery.
    Given I log in as "admin" (theme_snap)
    And I am on the snap jquery bootstrap test page
    And ".ui-progressbar-overlay" "css_element" should exist
    And I click on "Launch demo modal" "button"
    And I should see "Hey there buddy!"
