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
 * @package   theme_snap
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course main functions.
 */
define(
    [
        'jquery',
        'theme_snap/util',
        'theme_snap/responsive_video',
        'theme_snap/section_asset_management'
    ],
    function($, util, responsiveVideo, sectionAssetManagement) {

    /**
     * Return class(has private and public methods).
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
         * @param string modid
         * @return void
         */
        var scrollToModule = function(modid) {
            // sometimes we have a hash, sometimes we don't
            // strip hash then add just in case
            $('#toc-search-results').html('');
            var targmod = $("#" + modid.replace('#', ''));
            // http://stackoverflow.com/questions/6677035/jquery-scroll-to-element
            util.scrollToElement(targmod);

            var searchpin = $("#searchpin");
            if (!searchpin.length) {
                searchpin = $('<i id="searchpin"></i>');
            }

            $(targmod).find('.instancename').prepend(searchpin);
            $(targmod).attr('tabindex', '-1').focus();
        };

        /**
         * When on course page, show the section currently referenced in the location hash.
         */
        this.showSection = function() {
            if (!onCoursePage()) {
                // Only relevant for main course page.
                return;
            }

            // Reset visible section & blocks
            $('.course-content .main, #moodle-blocks,#coursetools, #snap-add-new-section').removeClass('state-visible');

            // We know the params at 0 is a section id.
            // Params will be in the format: #section-[number]&module-[cmid], e.g: #section-1&module-7255.
            var urlParams = location.hash.split("&"),
                section = urlParams[0],
                mod = urlParams[1] || null;

            // Course tools special section.
            if (section == '#coursetools') {
                $('#moodle-blocks').addClass('state-visible');
            }

            // If a modlue was in the hash then scroll to it.
            if (mod !== null) {
                $(section).addClass('state-visible');
                scrollToModule(mod);
            } else {
                $(section).addClass('state-visible').focus();
                // Faux link click behaviour - scroll to page top.
                window.scrollTo(0, 0);
            }

            // Default niceties to perform.
            var visibleChapters = $(
                '.section.main.state-visible,' +
                '#coursetools.state-visible,' +
                '#snap-add-new-section.state-visible'
            );
            if (!visibleChapters.length) {
                // Show chapter 0.
                $('#section-0').addClass('state-visible').focus();
                window.scrollTo(0, 0);
            }

            responsiveVideo.apply();
            // Add faux :current class to the relevant section in toc.
            var sectionIdSel = '.main.state-visible, #coursetools.state-visible, #snap-add-new-section.state-visible';
            var currentSectionId = $(sectionIdSel).attr('id');
            $('#chapters li').removeClass('current');
            $('#chapters a[href$="' + currentSectionId + '"]').parent('li').addClass('current');
        };

        /**
         * Initialise course JS.
         */
        var init = function() {
            sectionAssetManagement.init(self);

            // Only load the conditionals library if it's enabled for the course, viva la HTTP2!
            if (self.courseConfig.enablecompletion) {
                require(
                    [
                        'theme_snap/course_conditionals-lazy'
                    ], function(conditionals) {
                        conditionals(courseConfig);
                    }
                );
            }

            // SL - 19th aug 2014 - check we are in a course and if so, show current section.
            if (onCoursePage()) {
                self.showSection();
            }
        };

        // Intialise course lib.
        init();

    };
});
