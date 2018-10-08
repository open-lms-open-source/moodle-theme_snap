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
# @copyright  Copyright (c) 2018 Blackboard Inc.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: As an admin, I should be able to set a site's footer on Snap theme.

  Background:
    Given the following config values are set as admin:
        | theme | snap |
      And the following "users" exist:
        | username | firstname | lastname | email |
        | user1    | User1     | 1        | user1@example.com |

  @javascript
  Scenario: Admin sets a footer and it should be visible in the platform for other users.
    Given I log in as "admin"
      And I am on site homepage
      And "iframe" "css_element" should not be visible
      And I should not see "New footer"
      And I click on "#admin-menu-trigger" "css_element"
      And I navigate to "Snap" node in "Site administration>Appearance>Themes"
     Then I should see "Site footer"
      And I set the following fields to these values:
        | Site footer | <iframe></iframe> <p>New footer</p>|
      And I click on "Save changes" "button"
      And I wait until the page is ready
     Then I should see "New footer"
      And "iframe" "css_element" should be visible
      And I log out
      And I should see "New footer"
     Then "iframe" "css_element" should be visible
      And I log in as "user1"
      And I am on site homepage
     Then I should see "New footer"
      And "iframe" "css_element" should be visible
      And I log out