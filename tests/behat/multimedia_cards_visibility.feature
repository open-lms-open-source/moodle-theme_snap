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
# Test to check that no multimedia files appears at a card description content.
#
# @package    theme_snap
# @author     Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Check that multimedia files does not appear for activity cards.
  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | teacher1  | Teacher    | 1         | teacher1@example.com  |
      | student1  | Student    | 1         | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C1      | editingteacher  |
      | student1  | C1      | student         |

  @javascript
  Scenario: Add an image to an activity card, student and teacher should not see the image in the content.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Folder" to section "1" and I fill the form with:
      | Name         | Test Page        |
      | Description | <p>Test Content</p><img src="https://download.moodle.org/unittest/test.jpg" alt="test image" width="200" height="150" class="img-responsive atto_image_button_text-bottom"> |
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    Then I click on "//a[@class='snap-edit-asset']" "xpath_element"
    And I wait until the page is ready
    And I set the following fields to these values:
      | Display description on course page | 1 |
    And I press "Save and return to course"
    And "img.img-responsive atto_image_button_text-bottom" "css_element" should not exist
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Topic 1"
    And "img.img-responsive atto_image_button_text-bottom" "css_element" should not exist

  @javascript
  Scenario Outline: Add an image to an activity card, student and teacher should see the image in the content, when activity display is set as list in Snap settings.
    Given I log in as "admin"
    And the following config values are set as admin:
      | resourcedisplay | <Option> | theme_snap |
    And I log out
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Folder" to section "1" and I fill the form with:
      | Name         | Test Page        |
      | Description | <p>Test Content</p><img src="https://download.moodle.org/unittest/test.jpg" alt="test image" width="200" height="150" class="img-responsive atto_image_button_text-bottom"> |
    And I am on "Course 1" course homepage
    And I follow "Topic 1"
    Then I click on "//a[@class='snap-edit-asset']" "xpath_element"
    And I wait until the page is ready
    And I set the following fields to these values:
      | Display description on course page | 1 |
    And I press "Save and return to course"
    And "img.img-responsive.atto_image_button_text-bottom" "css_element" should exist
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Topic 1"
    And "img.img-responsive.atto_image_button_text-bottom" "css_element" should exist
    Examples:
      | Option     |
      | 1          |



