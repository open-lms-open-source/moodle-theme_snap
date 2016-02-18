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
#
# @package    theme_snap
# @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: In the Snap theme, within a course, editing teachers can create a new section by clicking on a
  link in the TOC which reveals a form.
  This requires the course to use the weeks and topics format.

  Background:
    Given the following config values are set as admin:
      | theme | snap |
    And the following "courses" exist:
      | fullname               | shortname     | category | groupmode | format         |
      | Topics course          | course_topics | 0        | 1         | topics         |
      | Weeks course           | course_weeks  | 0        | 1         | weeks          |
      | Single activity course | course_single | 0        | 1         | singleactivity |
      | Social course          | course_social | 0        | 1         | social         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | teacher2 | Teacher   | 2        | teacher2@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role           |
      | teacher1 | course_topics | editingteacher |
      | teacher1 | course_weeks  | editingteacher |
      | teacher1 | course_single | editingteacher |
      | teacher1 | course_social | editingteacher |
      | teacher2 | course_topics | teacher        |
      | teacher2 | course_weeks  | teacher        |
      | teacher2 | course_single | teacher        |
      | teacher2 | course_social | teacher        |
      | student1 | course_topics | student        |
      | student1 | course_weeks  | student        |
      | student1 | course_single | student        |
      | student1 | course_social | student        |

  @javascript
  Scenario: For editing teachers, ensure new section creation is available and works for topic and week courses but
    not other formats.
  Given I log in with snap as "teacher1"
    And I create a new section in course "Topics course"
   Then I should see "New section title" in the "#course-toc" "css_element"
    And I create a new section in course "Weeks course"
   Then I should see "New section title" in the "#course-toc" "css_element"
    And I open the personal menu
    # Negative test - the single activity course should not allow for section creation via the toc.
    And Snap I follow link "Single activity course"
    And "Create a new section" "css_element" should not exist
    # Negative test - the social course should not allow for section creation via the toc.
    And Snap I follow link "Social course"
    And "Create a new section" "css_element" should not exist
    # Make sure student can see the sections created by the teacher in Topics and Weeks format courses.
    And Snap I log out
    And I log in with snap as "student1"
    And I open the personal menu
    And Snap I follow link "Topics course"
   Then I should see "New section title" in the "#course-toc" "css_element"
    And I open the personal menu
    And Snap I follow link "Weeks course"
   Then I should see "New section title" in the "#course-toc" "css_element"

  @javascript
  Scenario: For non editing teachers and students, ensure new section creation is not available for any course formats.
  Given I log in with snap as "teacher2"
    And I open the personal menu
    And Snap I follow link "Topics course"
   Then "Create a new section" "css_element" should not exist
    And I open the personal menu
    And Snap I follow link "Weeks course"
   Then "Create a new section" "css_element" should not exist
    And Snap I follow link "Single activity course"
   Then "Create a new section" "css_element" should not exist
    And Snap I follow link "Social course"
   Then "Create a new section" "css_element" should not exist
    And Snap I log out
    And I log in with snap as "student1"
    And I open the personal menu
    And Snap I follow link "Topics course"
   Then "Create a new section" "css_element" should not exist
    And I open the personal menu
    And Snap I follow link "Weeks course"
   Then "Create a new section" "css_element" should not exist
    And Snap I follow link "Single activity course"
   Then "Create a new section" "css_element" should not exist
    And Snap I follow link "Social course"
   Then "Create a new section" "css_element" should not exist