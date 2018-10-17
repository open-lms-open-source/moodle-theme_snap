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
 * @copyright Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
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
        'theme_snap/course_modules'
    ],
    function($, util, sectionAssetManagement, courseModules) {

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
            $('#course-toc').removeClass('state-visible');
        };

        /**
         * Mark the section shown to user with a class in the TOC.
         */
        this.setTOCVisibleSection = function() {
            var sectionIdSel = '.section.main.state-visible, #coursetools.state-visible, #snap-add-new-section.state-visible';
            var currentSectionId = $(sectionIdSel).attr('id');
            $('#chapters li').removeClass('snap-visible-section');
            $('#chapters a[href$="' + currentSectionId + '"]').parent('li').addClass('snap-visible-section');
            $(document).trigger('snapContentRevealed');
        };

        /**
         * When on course page, show the section currently referenced in the location hash.
         */
        this.showSection = function() {
            if (!onCoursePage()) {
                // Only relevant for main course page.
                return;
            }
            var sectionSetByServer = '';

            if ($('.section.main.state-visible.set-by-server').length) {
                sectionSetByServer = '#' + $('.section.main.state-visible.set-by-server').attr('id');
                $('.section.main.state-visible.set-by-server').removeClass('set-by-server');
            } else {
                $('.course-content .section.main, #moodle-blocks,#coursetools, #snap-add-new-section').removeClass('state-visible');
            }

            // We know the params at 0 is a section id.
            // Params will be in the format: #section-[number]&module-[cmid], e.g: #section-1&module-7255.
            var urlParams = location.hash.split("&"),
                section = urlParams[0],
                mod = urlParams[1] || null;

            if (section !== '' && section !== sectionSetByServer) {
                $(sectionSetByServer).removeClass('state-visible');
            }

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
                scrollBack();
            }

            // Default niceties to perform.
            var visibleChapters = $(
                '.section.main.state-visible,' +
                '#coursetools.state-visible,' +
                '#snap-add-new-section.state-visible'
            );
            if (!visibleChapters.length) {
                if ($('.section.main.current').length) {
                    $('.section.main.current').addClass('state-visible').focus();
                } else {
                    $('#section-0').addClass('state-visible').focus();
                }
                scrollBack();
            }

            // Store last activity/resource accessed on sessionStorage
            $('li.snap-activity:visible, li.snap-resource:visible').on('click', 'a.mod-link', function() {
                sessionStorage.setItem('lastMod', $(this).parents('[id^=module]').attr('id'));
            });

            this.setTOCVisibleSection();
        };

        /**
         * Scroll to the last activity or resource accessed,
         * if there is nothing stored in session go to page top.
         */
        var scrollBack = function () {
            var storedmod = sessionStorage.getItem('lastMod');
            if(storedmod === null){
                window.scrollTo(0, 0);
            } else {
                util.scrollToElement($('#'+storedmod+''));
                sessionStorage.removeItem('lastMod');
            }
        };

        /**
         * Initialise course JS.
         */
        var init = function() {
            sectionAssetManagement.init(self);
            courseModules.init();

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
                $(document).on('snapTOCReplaced', function() {
                    self.setTOCVisibleSection();
                });
            }
        };

        /**
         * Snap modchooser listener to add current section to urls.
         */
        var modchooserSectionLinks = function() {
            $('.section-modchooser-link').click(function() {
                // Grab the section number from the button.
                var sectionNum = $(this).attr('data-section');
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
    };
});
