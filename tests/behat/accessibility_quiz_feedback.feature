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
# Tests for toggle course section visibility in non edit mode in snap.
#
# @package    theme_snap
# @author     Rafael Becerra rafael.becerrarodriguez@blackboard.com
# @copyright  Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: When the Moodle theme is set to Snap, quiz answers feedback have a tabindex attribute.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | teacher1    | Teacher1 | teacher1@example.com |
      | student1 | student1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user | course | role           |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber |
      | quiz       | Quiz   | Quiz 1 description | C1     | quiz1    |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    |
      | Test questions   | truefalse   | TF1   | First question  |
      | Test questions   | truefalse   | TF2   | Second question |
    And quiz "Quiz" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |
    And I log out

  @javascript
  Scenario: Answers feedback for the quizes should have a present tabindex attribute.
    Given I log in as "student1"
    And I am on the course main page for "C1"
    And I click on ".snap-asset-link a" "css_element"
    And I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "//input[@value=\"Submit all and finish\"]" "xpath_element"
    And I wait until the page is ready
    Then "div.feedback" "css_element" should exist
    And the "tabindex" attribute of "div.feedback" "css_element" should contain "0"
    And the "tabindex" attribute of "div.specificfeedback" "css_element" should contain "0"
    And the "tabindex" attribute of "div.generalfeedback" "css_element" should contain "0"
    And the "tabindex" attribute of "div.rightanswer" "css_element" should contain "0"
