@theme @theme_snap @theme_snap_course_index
Feature: Testing course index drawer in theme_snap

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | initsections |
      | Course 1 | C1        | topics | 3            |
    And the following "activities" exist:
      | activity | name         | course | section |
      | assign   | Assignment 1 | C1     | 1       |
      | assign   | Assignment 2 | C1     | 2       |

  @javascript
  Scenario: Course index should be open by default and save user preferences if closed
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And I click on ".drawertoggle" "css_element"
    And I log out
    Then I log in as "admin"
    And I am on the course main page for "C1"
    And "#theme_boost-drawers-courseindex" "css_element" should not be visible
    And I click on ".drawer-toggler" "css_element"
    And I log out
    Then I log in as "admin"
    And I am on the course main page for "C1"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible

  @javascript
  Scenario: If the course index drawer is open, the others should be closed
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And ".block_settings" "css_element" should not be visible
    And "#theme_snap-drawers-blocks" "css_element" should not be visible
    And "#snap_feeds_side_menu" "css_element" should not be visible
    And ".message-app" "css_element" should not be visible
    And I click on "#admin-menu-trigger" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should not be visible
    And ".block_settings" "css_element" should be visible
    And I click on ".drawer-toggler" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And ".block_settings" "css_element" should not be visible
    And I click on ".blocks-drawer-button" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should not be visible
    And "#theme_snap-drawers-blocks" "css_element" should be visible
    And I click on ".drawer-toggler" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And "#theme_snap-drawers-blocks" "css_element" should not be visible
    And I click on "#snap_feeds_side_menu_trigger" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should not be visible
    And "#snap_feeds_side_menu" "css_element" should be visible
    And I click on ".drawer-toggler" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And "#snap_feeds_side_menu" "css_element" should not be visible
    And I click on "[data-region='popover-region-messages']" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should not be visible
    And ".message-app" "css_element" should be visible
    And I click on ".drawer-toggler" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And ".message-app" "css_element" should not be visible

  @javascript
  Scenario: Changing the highlighted section is reflected in the course index
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I follow "Section 3"
    And I follow "Section 2"
    And I click on "#extra-actions-dropdown-2" "css_element"
    And I click on "#section-2 .snap-highlight" "css_element"
    Then I should see "Highlighted" in the "nav#courseindex [data-number='2']" "css_element"
    And I follow "Section 1"
    And I click on "#extra-actions-dropdown-1" "css_element"
    And I click on "#section-1 .snap-highlight" "css_element"
    Then I should see "Highlighted" in the "nav#courseindex [data-number='1']" "css_element"
