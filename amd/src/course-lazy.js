/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package
 * @copyright Copyright (c) 2016 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course main functions.
 */
define(
    [
        'jquery',
        'theme_snap/util',
        'theme_snap/section_asset_management',
        'theme_snap/course_modules',
        'core/str',
        'theme_snap/activity_cards',
    ],
    function($, util, sectionAssetManagement, courseModules, str, activityCards) {

    /**
     * Return class(has private and public methods).
     * @param {object} courseConfig
     */
    return function(courseConfig) {

        var self = this;

        self.courseConfig = courseConfig;

        /**
         * Are we on the main course page - i.e. TOC is visible.
         * @returns {boolean}
         */
        var onCoursePage = function() {
            return $('body').attr('id').indexOf('page-course-view-') === 0;
        };

        /**
         * Scroll to a mod via search
         * @param {string} modid
         */
        var scrollToModule = function(modid) {
            // Sometimes we have a hash, sometimes we don't
            // strip hash then add just in case
            $('#toc-search-results').html('');
            var targmod = $("#" + modid.replace('#', ''));

            var searchpin = $("#searchpin");
            if (!searchpin.length) {
                searchpin = $('<i id="searchpin"></i>');
            }

            $(targmod).find('.instancename').prepend(searchpin);
            $(targmod).attr('tabindex', '-1').focus();
        };

        /**
         * Change visibility of sections.
         * @param {string} section The section ID to be shown.
         * @param {string} modid The module ID to set focus.
         */
        var switchSectionVisibility = function(section, modid = null) {
            var visibleSections = $(
                'ul.sections > .section.main.state-visible,' +
                '#coursetools.state-visible,' +
                '#snap-add-new-section.state-visible'
            );

            let sectiontarget = '';
            if (section === '#coursetools' || section === '#snap-add-new-section') {
                sectiontarget = section;
            } else if (section !== '') {
                // We use SectionID to identify the section to show.
                sectiontarget = 'ul.sections > li.section[data-id="' + section + '"]';
            }

            // If no visible section, then make one visible.
            if (!visibleSections.length) {
                if (sectiontarget !== '') {
                    $(sectiontarget).removeClass('hidden').addClass('state-visible').focus();
                } else if ($('.section.main.current').length) {
                    $('ul.sections > .section.main.current').addClass('state-visible').focus();
                } else {
                    $('ul.sections > #section-0').addClass('state-visible').focus();
                }
                sectionAssetManagement.setTOCVisibleSection();
                scrollBack();
                return;
            }

            if (sectiontarget !== '') {
                // If already a visible section, show the new section instead.
                visibleSections.removeClass('state-visible').addClass('hidden');
                $(sectiontarget).removeClass('hidden').addClass('state-visible');
            }

            // If a module was in the hash then scroll to it.
            if (modid !== null) {
                scrollToModule(modid);
            } else {
                // Faux link click behaviour - scroll to page top.
                scrollBack();
            }

            sectionAssetManagement.setTOCVisibleSection();
        };

        /**
         * Main function to manage section visualization.
         */
        this.sectionRouter = function() {
            if (!onCoursePage()) {
                // Only relevant for main course page.
                return;
            }

            // We know the params at 0 is a section id.
            // Params will be in the format: #section-[number]&module-[cmid], e.g: #section-1&module-7255.
            var urlParams = location.hash.split("&"),
                hashSection = urlParams[0] || '', // Example: #section-1
                mod = urlParams[1] || null;

            let sectionID = '';
            // Let Core handle some Modules behavior.
            if (hashSection.startsWith('#h5pbook') || hashSection.startsWith('#module-')) {
                return;
            } else if (hashSection.startsWith('#section-')) {
                // Get section number.
                sectionID = hashSection.match(/\d+/)[0];
            }

            // If #snap-add-new-section was visible, remove that in favor of the course section.
            if (document.getElementById('snap-add-new-section')) {
                document.getElementById('snap-add-new-section').classList.remove('state-visible');
            }

            if ((sectionID === '' || sectionID === undefined)
            && (location.pathname.endsWith('/course/section.php') || location.pathname.endsWith('/course/view.php'))) {
                if (self.courseConfig.sectionid !== undefined) {
                    sectionID = self.courseConfig.sectionid;
                } else {
                    let selectedSection = document.querySelector('[id^="section-"]');
                    if (selectedSection) {
                        sectionID = selectedSection.getAttribute('data-id');
                    }
                }
            }

            var $sectionNode = $('ul.sections > li.section[data-id="' + sectionID + '"]');
            if (hashSection === '#coursetools' || hashSection === '#snap-add-new-section') {
                // Make visible the Dashboard or New section Form.
                switchSectionVisibility(hashSection, null);
            } else if (sectionID !== '' && !($sectionNode.length > 0)) {
                // Section does not exist in DOM, render it.
                sectionAssetManagement.getSection(sectionID, mod, switchSectionVisibility);
                sectionAssetManagement.updateBreadcrumb(sectionID);
            } else {
                // Section already rendered, show it.
                switchSectionVisibility(sectionID, mod);
                sectionAssetManagement.updateBreadcrumb(sectionID);
            }

            // Store last activity/resource accessed on sessionStorage
            $('li.snap-activity:visible, li.snap-resource:visible').on('click', 'a.mod-link', function() {
                sessionStorage.setItem('lastMod', $(this).parents('[id^=module]').attr('id'));
            });
        };

        /**
         * Scroll to the last activity or resource accessed,
         * if there is nothing stored in session go to page top.
         */
        var scrollBack = function() {
            var storedmod = sessionStorage.getItem('lastMod');
            // When a new module is created, we don't want these scrolls.
            if (!courseConfig.coursemodulecreatedid) {
                if (storedmod === null) {
                    window.scrollTo(0, 0);
                } else {
                    util.scrollToElement($('#' + storedmod + ''));
                    sessionStorage.removeItem('lastMod');
                }
            }
        };

        /**
         * Initialise course JS.
         */
        var init = function() {
            sectionAssetManagement.init(self);
            courseModules.init(courseConfig);
            var isNativeFormat = ['weeks', 'topics'].includes(self.courseConfig.format);

            // SL - 19th aug 2014 - check we are in a course and if so, show current section.
            if (onCoursePage() && isNativeFormat) {
                self.sectionRouter();
                activityCards.init();
            }
        };

        /**
         * Snap modchooser listener to add current section to urls.
         */
        var modchooserSectionLinks = function() {
            $('.section-modchooser-link').click(function() {
                // Grab the section number from the button.
                var sectionNum = $(this).attr('data-sectionid');
                $('.snap-modchooser-addlink').each(function() {
                    // Update section in mod link to current section.
                    var newLink = this.href.replace(/(section=)[0-9]+/ig, '$1' + sectionNum);
                    $(this).attr('href', newLink);
                });
            });
        };

        // Intialise course lib.
        init();
        modchooserSectionLinks();

        // Handle cancel button for add new section form.
        const cancelBtn = document.getElementById('cancel-new-section');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                window.location.hash = '';
            });
        }
    };
});
