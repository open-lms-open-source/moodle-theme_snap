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
# @package    theme_snap
# @copyright  Copyright (c) 2025 Open LMS.
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course_index
Feature: Testing course index drawer in theme_snap

  Background:
    Given the following "courses" exist:
      | fullname     | shortname | format  |
      | Test Course  | C1        | topics  |

    And the following "activities" exist:
      | activity | name      | intro        | course | idnumber | section |
      | book     | Test Book | Test content | C1     | book1    | 1       |

  @javascript
  Scenario: The "Test Book" activity icon is correct in the course index.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    Then "//li[contains(@id,'course-index-cm') and .//span[contains(@class,'activityiconcontainer')]//img[contains(@class,'book')]]" "xpath_element" should exist
    And "//li[contains(@id,'course-index-cm')]//img[contains(@class,'book')]" "xpath_element" should exist