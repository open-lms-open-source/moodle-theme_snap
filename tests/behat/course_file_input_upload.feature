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
# @copyright  Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap @theme_snap_course
Feature: When the moodle theme is set to Snap, teachers can upload files as resources directly to the current
  course section from a simple file input element in either read or edit mode.

  Background:
  Given the following "courses" exist:
      | fullname | shortname | category | format | maxbytes |
      | Course 1 | C1        | 0        | topics | 500000   |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: In read mode, teacher uploads file.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And "#snap-drop-file-1" "css_element" should exist
    And I upload file "test_text_file.txt" to section 1
    And I upload file "test_mp3_file.mp3" to section 1
    Then ".snap-resource[data-type='text']" "css_element" should exist
    And ".snap-resource[data-type='mp3']" "css_element" should exist
    # Make sure image uploads do not suffer from annoying prompt for label handler.
    And I upload file "testgif.gif" to section 1
    Then I should not see "Add image to course page"
    And I should not see "Create file resource"
    And I should see "testgif" in the "#section-1 .snap-native-image .activityinstance .instancename" "css_element"

  @javascript
  Scenario: Student cannot upload file.
    Given I log in as "student1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#snap-drop-file" "css_element" should not exist

  @javascript
  Scenario: A teacher with the capability should be able to upload a file with any size.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And "#snap-drop-file-1" "css_element" should exist
    And I upload file "400KB_file.txt" to section 1
    And I upload file "600KB_file.mp3" to section 1
    Then ".snap-resource[data-type='text']" "css_element" should exist
    And ".snap-resource[data-type='mp3']" "css_element" should not exist
    And I should see "The file '600KB_file.mp3' is too large and cannot be uploaded"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Users > Permissions" in current page administration
    And I override the system permissions of "Teacher" role with:
      | capability                         | permission |
      | moodle/course:ignorefilesizelimits | Allow      |
    And I log out
    And I log in as "teacher1"
    And I am on the course main page for "C1"
    And I follow "Topic 1"
    Then "#section-1" "css_element" should exist
    And "#snap-drop-file-1" "css_element" should exist
    And I upload file "600KB_file.mp3" to section 1
    And ".snap-resource[data-type='mp3']" "css_element" should exist



