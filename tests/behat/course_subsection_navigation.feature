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
# Snap footer navigation between subsections.
#
# @package    theme_snap
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_course
Feature: Subsection footer navigation in Snap shows next and previous between sibling subsections

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname | shortname | category | format | initsections |
      | Course 1 | C1        | 0        | topics | 5              |
    And the following "users" exist:
      | username | firstname | lastname | email              |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I enable "subsection" "mod" plugin
    And the following "activities" exist:
      | activity   | name          | course | idnumber     | section |
      | subsection | Subsection 1  | C1     | Subsection1  | 1       |
      | subsection | Subsection 2  | C1     | Subsection2  | 1       |

  @javascript
  Scenario: First subsection has next only; second has previous only
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I click on "//a[contains(@href,'/course/section.php')][contains(normalize-space(.),'Subsection 1')]" "xpath_element"
    And I wait until the page is ready
    Then "nav.section_footer a.next_section:not(.disabled)" "css_element" should exist
    And "nav.section_footer a.previous_section:not(.disabled)" "css_element" should not exist
    And I should see "Next subsection"
    When I click on "//span[contains(@class,'nav_title') and contains(.,'Subsection 2')]" "xpath_element"
    And I wait until the page is ready
    Then "nav.section_footer a.previous_section:not(.disabled)" "css_element" should exist
    And I should see "Previous subsection"
    And I should not see "Next subsection"