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
# Tests for page module.
#
# @package    theme_snap
# @copyright  Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: Open page module inline
  As any user
  I need to view page modules inline and have auto completion tracking updated.

  Background:
    Given the following config values are set as admin:
      | enablecompletion | 1 |
      | enableavailability | 1 |
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | format | category | groupmode | enablecompletion |
      | Course 1 | C1        | topics | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | admin    | C1     | teacher |
      | student1 | C1     | student |

  @javascript
  Scenario: Page mod is created and opened inline.
    Given the following "activities" exist:
      | activity | course | idnumber | name       | intro        | content       | completion | completionview |
      | page     | C1     | page1    | Test page1 | Test page 1  | page content1 | 0          | 0              |
    And I log in as "student1" (theme_snap)
    And I am on the course main page for "C1"
    And I should not see "page content1"
    And I follow "Read more&nbsp;»"
    And I wait until ".pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    # The above step basically waits for the page content to load up.
    And I should see "page content1"


  @javascript
  Scenario: Page mod completion updates on read more and affects availability for other modules and sections.
    Given the following "activities" exist:
      | activity | course | idnumber  | name              | intro                 | content                 | completion | completionview | section |
      | page     | C1     | pagec     | Page completion   | Page completion intro | Page completion content | 2          | 1              | 0       |
      | page     | C1     | pager     | Page restricted   | Page restricted intro | Page restricted content | 0          | 0              | 0       |
      | page     | C1     | pagec2    | Page completion 2 | Page comp2      intro | Page comp2      content | 2          | 1              | 1       |
    And the following "activities" exist:
      | activity | course | idnumber     | name            | section |
      | assign   | C1     | assigntest   | Assignment Test | 2       |
    And I log in as "admin" (theme_snap)
    And I am on the course main page for "C1"
    # Restrict the second page module to only be accessible after the first page module is marked complete.
    And I restrict course asset "Page restricted" by completion of "Page completion"
    # Restrict section one of the course to only be accessible after the first page module is marked complete.
    And I follow "Topic 1"
    And I click on "#section-1 .edit-summary" "css_element"
    And I set the field "name" to "Topic 1"
    And I apply asset completion restriction "Page completion" to section
    And I follow "Topic 2"
    And I click on "#section-2 .edit-summary" "css_element"
    And I set the field "name" to "Topic 2"
    And I apply asset completion restriction "Page completion 2" to section
    And I log out (theme_snap)
    And I log in as "student1" (theme_snap)
    And I am on the course main page for "C1"
    Then I should not see "page content2"
    # Note: nth-of-type(2) corresponds to the second section in the TOC.
    And I should see "Conditional" in the "#chapters li:nth-of-type(2)" "css_element"
    And I should see "Conditional" in the "#chapters li:nth-of-type(3)" "css_element"
    And "span.autocompletion img[title='Not completed: Page completion']" "css_element" should exist
    And I should see availability info "Not available unless: The activity Page completion is marked complete"
    And I follow "Topic 1"
    And "#chapters li:nth-of-type(2).snap-visible-section" "css_element" should exist
    # Make sure topic 1 show section availability info.
    Then I should see availability info "Not available unless: The activity Page completion is marked complete"
    And I follow "Introduction"
    And I follow visible link "Read more&nbsp;»"
    And I wait until "#section-0 .pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    # The above step basically waits for the page module content to load up.
    Then I should see "Page completion content"
    And I should not see availability info "Not available unless: The activity Page completion is marked complete"
    And I should not see "Conditional" in the "#chapters li:nth-of-type(2)" "css_element"
    And I should see "Progress: 1 / 1" in the "#chapters li:nth-of-type(1)" "css_element"
    And "#chapters li:nth-of-type(1).snap-visible-section" "css_element" should exist
    And "span.autocompletion img[title='Not completed: Page completion']" "css_element" should not exist
    And "span.autocompletion img[title='Completed: Page completion']" "css_element" should exist
    And I follow "Topic 1"
    # Make sure topic 1 does not show section availability info.
    Then I should not see availability info "Not available unless: The activity Page completion is marked complete"
    And I should see "Page completion 2"
    # Test chained conditional release.
    And I follow "Topic 2"
    Then I should see availability info "Not available unless: The activity Page completion 2 is marked complete"
    And I follow "Topic 1"
    And "span.autocompletion img[title='Not completed: Page completion 2']" "css_element" should exist
    And I follow visible link "Read more&nbsp;»"
    And I wait until "#section-1 .pagemod-content[data-content-loaded=\"1\"]" "css_element" is visible
    Then "span.autocompletion img[title='Not completed: Page completion 2']" "css_element" should not exist
    And "span.autocompletion img[title='Completed: Page completion 2']" "css_element" should exist
    And "#chapters li:nth-of-type(2).snap-visible-section" "css_element" should exist
    And I follow "Topic 2"
    Then I should not see availability info "Not available unless: The activity Page completion 2 is marked complete"
    And I should not see "Conditional" in the "#chapters li:nth-of-type(3)" "css_element"
    And I should see "Assignment Test"
