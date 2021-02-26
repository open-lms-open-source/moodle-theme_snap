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
# The setting "Custom" on editing a section using topics format
# should not be visible.
#
# @package    theme_snap
# @autor      Oscar Nadjar <oscar.nadjar@openlms.net>
# @copyright  Copyright (c) 2019 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


@theme @theme_snap
Feature: With topics format the option "Custom" on editing a section shouldn't be visible.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format   |
      | Course 1 | C1        | 0        | topics   |
      | Course 2 | C2        | 0        | weeks    |

    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |

  @javascript
  Scenario: As a teacher I shouldn't see the option "Custom" on editing a section with topics format.
    Given I log in as "teacher1"
    And I am on the course main page for "C1"
    And I click on "#section-0 .summary .edit-summary" "css_element"
    And I should not see "Custom" in the ".snap-form-required .fdefaultcustom .form-check-inline" "css_element"
    And I am on "Course 2" course homepage
    And I click on "a[href=\"#section-1\"].chapter-title" "css_element"
    And I click on "#section-1 .summary .edit-summary" "css_element"
    And I should see "Custom" in the ".snap-form-required .fdefaultcustom .form-check-inline" "css_element"
