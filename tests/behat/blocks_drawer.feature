@theme @theme_snap
Feature: Testing blocks_drawer in theme_snap

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |

  @javascript
  Scenario: The blocks drawer can be opened, closed and see the Navigation block
    Given I am logged in as "teacher1"
    And I am on site homepage
    Then "button[data-original-title='Toggle block drawer']" "css_element" should exist
    And I should not see "Dashboard" in the "Navigation" "block"
    And I click on "button[data-original-title='Toggle block drawer']" "css_element"
    And I should see "Dashboard" in the "Navigation" "block"

  @javascript
  Scenario: The drawers from snap feeds, settings block, and block drawer should open one at a time
    Given I am logged in as "admin"
    And the following config values are set as admin:
      | linkadmincategories | 0 |
    And I am on site homepage
    And "section.block_settings.state-visible" "css_element" should not exist
    And I click on "#admin-menu-trigger" "css_element"
    And ".drawer.show" "css_element" should not exist
    And "section.block_settings.state-visible" "css_element" should exist
    And I should see "Site administration" in the "#settingsnav" "css_element"
    Then I click on "#snap_feeds_side_menu_trigger" "css_element"
    And "#snap_feeds_side_menu" "css_element" should exist
    And ".drawer.show" "css_element" should not exist
    And "section.block_settings.state-visible" "css_element" should not exist
    Then I click on "#snap_feeds_side_menu_trigger" "css_element"
    And I click on "button[data-original-title='Toggle block drawer']" "css_element"
    And I should see "Dashboard" in the "Navigation" "block"

  @javascript
  Scenario: When the user scrolls horizontally, a button to scroll back to the left should appear.
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activities" exist:
      | activity | course | idnumber | name             | intro             | assignsubmission_onlinetext_enabled | assignfeedback_comments_enabled | section | duedate         |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | 1                                   | 1                               | 1       | ##tomorrow##    |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment 2 | 1                                   | 1                               | 1       | ##next week##   |
      | assign   | C1     | assign3  | Test assignment3 | Test assignment 3 | 1                                   | 1                               | 1       | ##yesterday##   |
      | assign   | C1     | assign4  | Test assignment4 | Test assignment 4 | 1                                   | 1                               | 1       | ##yesterday##   |
      | assign   | C1     | assign5  | Test assignment5 | Test assignment 5 | 1                                   | 1                               | 1       | ##yesterday##   |
      | assign   | C1     | assign6  | Test assignment6 | Test assignment 6 | 1                                   | 1                               | 1       | ##yesterday##   |
      | assign   | C1     | assign7  | Test assignment7 | Test assignment 7 | 1                                   | 1                               | 1       | ##yesterday##   |
      | assign   | C1     | assign8  | Test assignment8 | Test assignment 8 | 1                                   | 1                               | 1       | ##yesterday##   |
      | assign   | C1     | assign9  | Test assignment9 | Test assignment 9 | 1                                   | 1                               | 1       | ##yesterday##   |
    And I am logged in as "admin"
    And I am on "Course 1" course homepage
    And I am on the "Course 1" "grades > Grader report > View" page logged in as "admin"
    When I scroll to the right
    Then I click on "#goto-left-link" "css_element"
    And I should see "Test assignment1"
