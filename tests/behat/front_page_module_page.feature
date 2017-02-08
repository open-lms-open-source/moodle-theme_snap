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
# Tests for page module behaviour at front page.
#
# @package    theme_snap
# @author     Guillermo Alvarez
# @copyright  2017 Blackboard Ltd
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Open page module inline
  As any user
  I need to view page modules inline at front page.

  Background:
    Given the following config values are set as admin:
      | enablecompletion   | 1    |
      | enableavailability | 1    |
      | theme              | snap |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And completion tracking is "Enabled" for course "Acceptance test site"
    And debugging is turned off

  @javascript
  Scenario: Page mod is created and opened inline at the front page.
     Given the following "activities" exist:
       | activity | course               | idnumber | name       | intro        | content       | completion | completionview | section |
       | page     | Acceptance test site | page1    | Test page1 | Test page 1  | page content1 | 0          | 0              | 1       |
     And I log in as "admin"
     And I am on site homepage
     And I should not see "page content1"
     And I follow "Read more&nbsp;»"
     And I should not see an error dialog
     And I wait until ".pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
     # The above step basically waits for the page content to load up.
     And I should see "page content1"

  @javascript
  Scenario: Page mod completion updates on read more and affects availability for other modules at the front page.
    Given the following "activities" exist:
      | activity | course               | idnumber  | name              | intro                 | content                 | section |
      | page     | Acceptance test site | pagec     | Page completion   | Page completion intro | Page completion content | 1       |
      | page     | Acceptance test site | pager     | Page restricted   | Page restricted intro | Page restricted content | 1       |
   Then I log in as "admin" (theme_snap)
    And I am on site homepage
    # Sometimes the page activity is not being created with the correct completion options, so I have to do it manually
    And I follow "Edit \"Page completion\""
    And I expand all fieldsets
    And I set the field "completion" to "2"
    And I set the field "completionview" to "1"
    And I press "Save and return to course"
    # Restrict the second page module to only be accessible after the first page module is marked complete.
    And I restrict course asset "Page restricted" by completion of "Page completion"
    And I log out (theme_snap)
    And I log in as "student1" (theme_snap)
    And I am on site homepage
   Then I should not see "Page restricted intro"
    And I should see availability info "Not available unless: The activity Page completion is marked complete"
    And I follow visible link "Read more&nbsp;»"
    And I should not see an error dialog
    And I wait until ".pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    # The above step basically waits for the page module content to load up.
   Then I should see "Page completion content"
    And I should not see availability info "Not available unless: The activity Page completion is marked complete"
    And I should see "Page restricted"
    And I follow visible link "Read more&nbsp;»"
   Then I should see " Page restricted content"

  @javascript
  Scenario: Page mod should be visible at the front page for users that are not logged in.
    Given the following "activities" exist:
      | activity | course               | idnumber | name       | intro        | content       | completion | completionview | section |
      | page     | Acceptance test site | page1    | Test page1 | Test page 1  | page content1 | 0          | 0              | 1       |
    And I log in as "admin" (theme_snap)
    And I am on site homepage
    And I should see "Test page1"
    And I should not see "page content1"
    And I log out (theme_snap)
    And I should not see "page content1"
   Then I follow visible link "Read more&nbsp;"
    And I should not see an error dialog
    And I wait until ".pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    And I should see "page content1"
