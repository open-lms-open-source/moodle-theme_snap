@theme @theme_snap
Feature: Testing course index drawer in theme_snap

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |

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
