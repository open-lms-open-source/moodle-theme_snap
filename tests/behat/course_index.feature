@theme @theme_snap @theme_snap_course_index
Feature: Testing course index drawer in theme_snap

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | initsections |
      | Course 1 | C1        | topics | 3            |
    And the following "activities" exist:
      | activity   | name                  | course | section |
      | assign     | Assignment 1          | C1     | 1       |
      | assign     | Assignment 2          | C1     | 2       |
      | subsection | SubsectionEmpty 1     | C1     | 1       |
      | subsection | SubsectionFulfilled 1 | C1     | 3       |
      | assign     | SubAssign 1           | C1     | 5       |
      | assign     | SubAssign 2           | C1     | 5       |
      | assign     | SubAssign 3           | C1     | 5       |
      | assign     | SubAssign 4           | C1     | 5       |
      | assign     | SubAssign 5           | C1     | 5       |
      | assign     | SubAssign 6           | C1     | 5       |
      | label      | TextAndMedia 1        | C1     | 5       |
      | label      | TextAndMedia 2        | C1     | 5       |
      | quiz       | Quiz 1                | C1     | 5       |
      | quiz       | Quiz 2                | C1     | 5       |
      | quiz       | Quiz 3                | C1     | 5       |
      | quiz       | Quiz 4                | C1     | 5       |
    And I enable "subsection" "mod" plugin

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

  @javascript
  Scenario: Chevron is hidden for sections without activities in course index
    Given I skip because "Currently CSS :has() pseudo-class is not supported in our current behat testing environment."
    Given I am logged in as "admin"
    And I am on the course main page for "C1"
    Then ".courseindex-section[data-number='3'] .courseindex-chevron" "css_element" should not be visible
    When I click on "#section-3 .section-modchooser-link.btn-add-activity" "css_element"
    And I add a "Page" to section "3" using the activity chooser
    Then ".courseindex-section[data-number='3'] .courseindex-chevron" "css_element" should be visible

  @javascript
  Scenario: The course index ought to be sticky in its manner of displays.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I follow "Section 5"
    # The course index should accompany the user, all the way down.
    And I scroll to the base of selector "[data-activityname='SubAssign 5']"
    And I should see "Contents"
    And I should see "SubAssign 1"
    And I should see "SubAssign 2"
    And I should see "SubAssign 3"
    And I should see "SubAssign 4"
    And I should see "SubAssign 5"
    And I should see "SubAssign 6"
    And I should see "TextAndMedia 1"
    And I should see "TextAndMedia 2"
    And I should see "Quiz 1"
    And I should see "Quiz 2"
    And I should see "Quiz 3"
    And I should see "Create a new section"
    And I should see "Course Dashboard"

  @javascript
  Scenario: The course index remains sticky when turning edit mode on and off.
    Given I log in as "admin"
    And I am on the course main page for "C1"
    And I switch editing mode on
    And I should see "Contents"
    And I should see "SubAssign 1"
    And I should see "SubAssign 2"
    And I should see "SubAssign 3"
    And I should see "SubAssign 4"
    And I should see "SubAssign 5"
    And I should see "SubAssign 6"
    And I should see "TextAndMedia 1"
    And I should see "TextAndMedia 2"
    And I should see "Quiz 1"
    And I should see "Quiz 2"
    And I should see "Quiz 3"
    And I should see "Create a new section"
    And I should see "Course Dashboard"
