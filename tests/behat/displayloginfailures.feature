@theme_snap @core @core_auth 
Feature: Test the 'showlogfailures' feature works.
  In order to see my recent login failures when logging in
  As a user
  I need to have at least one failed login attempt and then log in

  Background:
    Given the following "users" exist:
      | username |
      | teacher1 |
    And the following config values are set as admin:
      | displayloginfailures | 1 |

  # Given the user has at least one failed login attempt, when they login, then they should see both header and footer notices.
  Scenario: Check that 'displayloginfailures' works without javascript for teachers.
    # Simulate a log in failure for the teacher.
    Given I am on site homepage
    Given I follow "Log in"
    And I set the field "Username" to "teacher1"
    And I set the field "Password" to "wrongpass"
    And I press "Log in"
    And I should see "Invalid login, please try again"
    # Now, log in with the correct credentials.
    When I set the field "Username" to "teacher1"
    And I set the field "Password" to "teacher1"
    And I press "Log in"
    # Confirm the notices are displayed.
    Then I should see "1 failed logins since your last login" in the "#snap-header" "css_element"
    # Confirm the notices disappear when navigating to another page.
    And I am on homepage
    And I should not see "1 failed logins since your last login" in the "#snap-header" "css_element"