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
# along with Moodle. If not, see <http://www.gnu.org/licenses/>.
#
# Test for Snap's carousel accessibility
#
# @package    theme_snap
# @autor      Rafael Becerra
# @copyright  Copyright (c) 2022 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Snap's carousel must have the correct attributes to make it accessible.

  Background:
    Given the following config values are set as admin:
      | slide_one_title     | Title for slide one | theme_snap |
      | slide_two_title     | Title for slide two | theme_snap |
      | cover_carousel      | 1                   | theme_snap |
    And the following config values are set as admin:
      | defaulthomepage | 0                           |
    And the following "blocks" exist:
      | blockname     | contextlevel | reference |
      | private_files | System       |   1       |
    And I change window size to "large"
    And I log in as "admin"
    And I click on the block drawer toggle
    And I scroll to the bottom
    And I follow "Manage private files..."
    And I upload "lib/tests/fixtures/gd-logo.png" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I log out

  @javascript @_file_upload
  Scenario: Snap's carousel must comply with the accessibility standards.
    Given I am using Open LMS
    And I log in as "admin"
    And the following config values are set as admin:
      | linkadmincategories | 0 |
    And I am on site homepage
    And I go to "Site administration > Appearance > Themes" in snap administration
    And I follow "Edit theme settings 'Snap'"
    And I follow "Cover display"
    And I click on "#themesnapcoverdisplay #admin-slide_one_image div[id^='filemanager-'] .filemanager-container .dndupload-message .dndupload-arrow" "css_element"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "//p[contains(text(),'gd-logo.png')]" "xpath_element"
    And I click on "Select this file" "button"
    And I click on "Save changes" "button"
    # Check the existence of the carousel in the front page.
    And I am on site homepage
    And I should see "Title for slide one"
    # The play/pause toggle should not be visible when only one slide exists.
    Then "#carousel-play-resume-buttons #carousel-toggle-button" "css_element" should not be visible
    And I go to "Site administration > Appearance > Themes" in snap administration
    And I follow "Edit theme settings 'Snap'"
    And I follow "Cover display"
    And I click on "#themesnapcoverdisplay #admin-slide_two_image div[id^='filemanager-'] .filemanager-container .dndupload-message .dndupload-arrow" "css_element"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    # Since this is for testing purposes only, it doesn't matter that the images are the same.
    And I click on "a.fp-file" "css_element"
    And I click on "Select this file" "button"
    And I click on "Save changes" "button"
    And I am on site homepage
    # Dots are a labelled list, numbered from one.
    And "#snap-site-carousel ul.carousel-indicators" "css_element" should exist
    And "#snap-site-carousel ul.carousel-indicators li button[data-bs-slide-to='0']" "css_element" should exist
    And the "aria-label" attribute of "#snap-site-carousel .carousel-indicators button[data-bs-slide-to='0']" "css_element" should contain "Go to slide 1"
    And the "aria-label" attribute of "#snap-site-carousel .carousel-indicators button[data-bs-slide-to='1']" "css_element" should contain "Go to slide 2"
    # Targeted by slide name, not .active, so it holds whichever is showing.
    And the "role" attribute of "#snap-carousel-container .carousel-slide_one" "css_element" should contain "group"
    And the "role" attribute of "#snap-carousel-container .carousel-slide_two" "css_element" should contain "group"
    And the "aria-label" attribute of "#snap-carousel-container .carousel-slide_one" "css_element" should contain "Slide 1 of 2"
    And the "aria-label" attribute of "#snap-carousel-container .carousel-slide_two" "css_element" should contain "Slide 2 of 2"
    Then "#carousel-play-resume-buttons #carousel-toggle-button" "css_element" should exist
    # Starts unpressed: slides auto-rotate.
    And the "aria-pressed" attribute of "#carousel-toggle-button" "css_element" should contain "false"
    And "#carousel-toggle-button .fa-pause" "css_element" should exist
    # Pausing switches icon, label and pressed state together.
    And I click on "#carousel-toggle-button" "css_element"
    And the "aria-pressed" attribute of "#carousel-toggle-button" "css_element" should contain "true"
    And "#carousel-toggle-button .fa-play" "css_element" should exist
    # Resuming switches them back.
    And I click on "#carousel-toggle-button" "css_element"
    And the "aria-pressed" attribute of "#carousel-toggle-button" "css_element" should contain "false"
    And "#carousel-toggle-button .fa-pause" "css_element" should exist
    # Regression: arrows must not silently restart rotation while paused.
    And I click on "#carousel-toggle-button" "css_element"
    And I click on "#snap-site-carousel .carousel-control-next" "css_element"
    And the "aria-pressed" attribute of "#carousel-toggle-button" "css_element" should contain "true"
    And "#carousel-toggle-button .fa-play" "css_element" should exist
    # Inactive slides are out of the accessibility tree; the active one is not.
    And the "aria-hidden" attribute of "#snap-carousel-container .carousel-item:not(.active)" "css_element" should contain "true"
    And the "aria-hidden" attribute of "#snap-carousel-container .carousel-item.active" "css_element" should not be set
