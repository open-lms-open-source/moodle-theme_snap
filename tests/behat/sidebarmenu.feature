@theme @theme_snap @theme_snap_sidebar_menu
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

  @javascript
  Scenario: The drawers in the sidebar menu should close when a header dropdown/popover is opened
    Given I am logged in as "admin"
    And I am on site homepage
    And ".snap-sidebar-menu.show" "css_element" should exist
    And I click on "#admin-menu-trigger" "css_element"
    And ".block_settings.state-visible" "css_element" should exist
    Then I click on ".usermenu .dropdown-toggle" "css_element"
    And ".block_settings.state-visible" "css_element" should not exist
    And I click on the block drawer toggle
    And ".drawer.show" "css_element" should exist
    And I click on "#nav-intellicart-popover-container" "css_element"
    And ".drawer.show" "css_element" should not exist
    And I click on "#snap_feeds_side_menu_trigger" "css_element"
    And "#snap_feeds_side_menu.state-visible" "css_element" should exist
    And I click on "#nav-notification-popover-container" "css_element"
    And "#snap_feeds_side_menu.state-visible" "css_element" should not exist

  @javascript
  Scenario: Block drawers open by default
    Given I am logged in as "admin"
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "activities" exist:
      | activity | name      | intro        | course | idnumber | section |
      | book     | Test Book | Test content | C1     | book1    | 0       |
    And the following "mod_book > chapter" exists:
      | book    | Test Book                       |
      | title   | First chapter                   |
      | content | This is First chapter's content |
    And I am on the course main page for "C1"
    And I click on "//a[@title='Test Book']" "xpath"
    And I should see "Table of contents"

  @javascript
  Scenario: Block drawers dont open by default in small screen sizes
    Given I am logged in as "admin"
    And I change window size to "320x480"
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "activities" exist:
      | activity | name      | intro        | course | idnumber | section |
      | book     | Test Book | Test content | C1     | book1    | 0       |
    And the following "mod_book > chapter" exists:
      | book    | Test Book                       |
      | title   | First chapter                   |
      | content | This is First chapter's content |
    And I am on the course main page for "C1"
    And I click on "//a[@title='Test Book']" "xpath"
    And I should not see "Table of contents"