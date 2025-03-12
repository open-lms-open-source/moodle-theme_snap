@theme @theme_snap
Feature: Testing sidebarmenu in theme_snap

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |

  @javascript
  Scenario: The sidebar menu should be opened by default
    Given I am logged in as "admin"
    And I am on site homepage
    And ".snap-sidebar-menu.show" "css_element" should exist

  @javascript
  Scenario: The sidebar menu should be opened and closed by clicking on the trigger button
    Given I am logged in as "admin"
    And I am on site homepage
    And ".snap-sidebar-menu.show" "css_element" should exist
    And I click on ".snap-sidebar-menu-trigger" "css_element"
    And ".snap-sidebar-menu.show" "css_element" should not exist
    And ".snap-sidebar-menu" "css_element" should exist

