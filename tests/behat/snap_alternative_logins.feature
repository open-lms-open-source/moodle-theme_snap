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
# Tests for personal menu display on initial login.
#
# @package    theme_snap
# @author     2020 Diego Casas <diego.casas@blackboard.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_login
Feature: When the moodle theme is set to Snap, the login options should be shown

  Background:
    Given the following config values are set as admin:
      |  config   |    value        | plugin   |
      | hostname  | host.domain.com | auth_cas |
      | baseuri   | cas/            | auth_cas |
      | port      | 8448            | auth_cas |
      | multiauth | yes             | auth_cas |
      | auth      | cas             |          |

  @javascript
  Scenario Outline: Login screen when alternative login options are enabled
    Given the following config values are set as admin:
      |  config      |   value   | plugin     |
      | enabledlogin | <enabled> | theme_snap |
    Given I am on login page
    And I wait until the page is ready
    And I wait until ".snap-log-in-loading-spinner" "css_element" is not visible
    And "#base-login" "css_element" <option1> be visible
    And "#alt-login" "css_element" <option2> be visible
    Then I am on site homepage
    And I click on "#page-mast .js-snap-pm-trigger" "css_element"
    And ".snap-pm-content form" "css_element" <option1> exist
    And ".snap-pm-content .potentialidps" "css_element" <option2> exist
    Examples:
      | enabled |   option1    | option2    |
      |   0     |   should     | should     |
      |   1     |   should     | should not |
      |   2     |   should not | should     |

  @javascript
  Scenario Outline: Login screen when both login options are enabled but the order change
    Given the following config values are set as admin:
      |  config           |   value   | plugin     |
      | enabledlogin      |   0       | theme_snap |
      | enabledloginorder |  <order>  | theme_snap |
    Given I am on login page
    And I wait until ".snap-log-in-loading-spinner" "css_element" is not visible
    And "<loginoption1>" "css_element" should appear before "<loginoption2>" "css_element"
    And I am on site homepage
    Then I click on "#page-mast .js-snap-pm-trigger" "css_element"
    And ".snap-pm-content <pmoption1>" "css_element" should appear before ".snap-pm-content <pmoption2>" "css_element"
    Examples:
      | order |   loginoption1    | loginoption2    |   pmoption1    | pmoption2      |
      |   0     |   #base-login     | #alt-login      |   form         | .potentialidps |
      |   1     |   #alt-login      | #base-login     | .potentialidps | form           |

  @javascript
  Scenario: Help button should redirect to login page
    Given the following config values are set as admin:
      |  config           |   value   | plugin     |
      | enabledlogin      |   0       | theme_snap |
      | enabledloginorder |   1       | theme_snap |
    And I am on site homepage
    And I click on "#page-mast .js-snap-pm-trigger" "css_element"
    And "#snap-pm-login-help" "css_element" should exist
    And I click on "#snap-pm-login-help" "css_element"
    And "#page-login-index" "css_element" should exist