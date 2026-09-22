@theme @theme_snap @javascript
Feature: Course TOC default behaviour
  In order to control the initial course navigation experience
  As a site administrator
  I need to configure the default course TOC state

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | One | student1@example.com |

    And the following "courses" exist:
      | fullname    | shortname | category | numsections |
      | Test Course | TC1       | 0        | 3           |

    And the following "activities" exist:
      | activity | name       | intro              | course | section |
      | page     | Page One   | Content for page 1 | TC1     | 1      |
      | page     | Page Two   | Content for page 2 | TC1     | 1      |
      | page     | Page Three | Content for page 3 | TC1     | 2      |

    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | TC1     | student |

  Scenario: Collapsed is applied on the first course visit
    Given I log in as "admin"
    And I set the following administration settings values:
      | tableofcontentsbehaviour | Collapsed |
    And I log out
    And I log in as "student1"
    And I am on "Test Course" course homepage
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "false"

  Scenario: Expanded is applied on the first course visit
    Given I log in as "admin"
    And I set the following administration settings values:
      | tableofcontentsbehaviour | Expanded |
    And I log out
    And I log in as "student1"
    And I am on "Test Course" course homepage
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "true"

  Scenario: Manual TOC changes are preserved during the active session
    Given I log in as "admin"
    And I set the following administration settings values:
      | tableofcontentsbehaviour | Collapsed |
    And I log out
    And I log in as "student1"
    And I am on "Test Course" course homepage
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "false"
    When I click on ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element"
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "true"
    When I reload the page
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "true"


  Scenario: Site default is applied again in a new session
    Given I log in as "admin"
    And I set the following administration settings values:
      | tableofcontentsbehaviour | Collapsed |
    And I log out
    And I log in as "student1"
    And I am on "Test Course" course homepage
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "false"
    When I click on ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element"
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "true"
    When I log out
    And I log in as "student1"
    And I am on "Test Course" course homepage
    Then the "aria-expanded" attribute of ".courseindex-section[data-number='1'] .courseindex-chevron" "css_element" should contain "false"
