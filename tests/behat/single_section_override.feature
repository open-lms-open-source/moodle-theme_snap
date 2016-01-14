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
# Tests for html5 file upload direct to course.
#
# @package    theme_snap
# @copyright  Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: When the moodle theme is set to Snap, courses in single section per page mode are forced to operate with all
  sections displayed at the same time.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | format | coursedisplay |
      | Course 1 | C1        | 0        | topics | 1             |
    # In the above, coursedisplay 1 means that the course will display single section at a time.
    And the following "course enrolments" exist:
      | user  | course | role           |
      | admin | C1     | editingteacher |

  @javascript
  Scenario: Admin opens course with single section per page mode but sees the page rendered in regular mode.
    Given I log in with snap as "admin"
    And I follow "Menu"
    And Snap I follow link "Course 1"
    And I wait until the page is ready
    And I go to course section 1
    And ".section-navigation.navigationtitle" "css_element" should not exist
    # In the above, .section-navigation.navigationtitle relates to the element on the page which contains the single
    # section at a time navigation. Visually you would see a link on the left entitled "General" and a link on the right
    # enitled "Topic 2"
    # This test ensures you do not see those elements. If you swap to clean theme in a single section mode at a time
    # course you will see that navigation after clicking on topic 1.