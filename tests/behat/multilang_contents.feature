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
# Test for multilang strings in Snap.
#
# @package    theme_snap
# @author     2018 Rafael Becerra <rafael.becerrarodriguez@blackboard.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: The site displays only the language that user has selected for multilang strings.

  Background:
    And I skip because "It is failing due to a problem when selecting an option in a dropdown. To be fixed in INT-15840"
    Given the following config values are set as admin:
      | theme | snap |
      | linkadmincategories | 0 |
    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | Course 1  | C1       |
    And I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Filters" node
    And I follow "Manage filters"
    And I click on "On" "option" in the "Multi-Language Content" "table_row"
    And I click on "Content and headings" "option" in the "Multi-Language Content" "table_row"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Edit settings" in current page administration
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin0']" to "Announcements"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Site news on front page displays only in english.
    And I skip because "It is failing due to a problem when selecting an option in a dropdown. To be fixed in INT-15840"
    Given I log in as "admin"
    And I am on site homepage
    And I click on "//div/a[contains(text(),'Add a new topic')]" "xpath_element"
    And I set the field "subject" to "Test discussion"
    And I set the field "Message" to "<span lang=\"en\" class=\"multilang\">English text</span><span lang=\"it\" class=\"multilang\">Italian text</span>"
    And I press "Post to forum"
    And I am on site homepage
    And ".news-article.clearfix" "css_element" should exist
    And I click on "//div/p/a[contains(text(),'Read more')]" "xpath_element"
    And "//div[contains(text(),'English text')]" "xpath_element" should exist
    And I log out

  @javascript
  Scenario: Language is changed site footer displays in only english.
    And I skip because "It is failing due to a problem when selecting an option in a dropdown. To be fixed in INT-15840"
    Given I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I expand "Site administration" node
    And I expand "Appearance" node
    And I expand "Themes" node
    And I follow "Snap"
    And I set the field "Site footer" to "<span lang=\"en\" class=\"multilang\">English text</span><span lang=\"it\" class=\"multilang\">Italian text</span>"
    And I press "Save changes"
    And "#snap-footer-content" "css_element" should exist
    And "//div[contains(text(),'English text')]" "xpath_element" should exist
    And I log out

  @javascript
  Scenario: Course header for the category displays in only english.
    Given I skip because "It's failing in MR 3.7. It will be fixed in INT-15840"
    Given I log in as "admin"
    And I am on course index
    And I click on "//p/a[contains(text(),'Manage courses')]" "xpath_element"
    And I click on "//div/a[@id='dropdown-1']" "xpath_element"
    And I click on "//div/a[@class='dropdown-item action-edit menu-action']" "xpath_element"
    And I set the field "Category name" to "<span lang=\"en\" class=\"multilang\">English text</span><span lang=\"it\" class=\"multilang\">Italian text</span>"
    And I press "Save changes"
    And I am on course index
    And "//h1/div[contains(text(),'English text')]" "xpath_element" should exist
    And I log out
