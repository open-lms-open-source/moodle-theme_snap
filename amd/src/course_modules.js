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
 * @author    Guy Thomas
 * @copyright Copyright (c) 2016 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course main functions.
 */
define(
    [
        'jquery',
        'core/ajax',
        'theme_snap/util',
        'theme_snap/ajax_notification',
        'core/str',
        'core/event'
    ],
    function($, ajax, util, ajaxNotify, str, Event) {

        /**
         * Reveal page module content.
         *
         * @param {jQuery} pageMod
         */
        var revealPageMod = function(pageMod) {
            pageMod.find('.pagemod-content').slideToggle("fast", function() {
                // Animation complete.
                if (pageMod.is('.state-expanded')) {
                    pageMod.attr('aria-expanded', 'true');
                    pageMod.find('.pagemod-content').focus();

                } else {
                    pageMod.attr('aria-expanded', 'false');
                    pageMod.focus();
                }

            });
        };

        /**
         * Page mod toggle content.
         */
        var listenPageModuleReadMore = function() {
            var pageToggleSelector = ".pagemod-readmore,.pagemod-content .snap-action-icon";
            $(document).on("click", pageToggleSelector, function(e) {
                var pageMod = $(this).closest('.modtype_page');
                util.scrollToElement(pageMod);
                var isexpanded = pageMod.hasClass('state-expanded');
                pageMod.toggleClass('state-expanded');

                var readmore = pageMod.find('.pagemod-readmore');

                var pageModContent = pageMod.find('.pagemod-content');
                if (pageModContent.data('content-loaded') == 1) {
                    // Content is already available so reveal it immediately.
                    revealPageMod(pageMod);
                    var readPageUrl = M.cfg.wwwroot + '/theme/snap/rest.php?action=read_page&contextid=' +
                        readmore.data('pagemodcontext');
                    if (!isexpanded) {
                        $.ajax({
                            type: "GET",
                            async: true,
                            url: readPageUrl,
                            success: function(data) {
                                ajaxNotify.ifErrorShowBestMsg(data).done(function(errorShown) {
                                    if (errorShown) {
                                        return;
                                    }
                                });
                            }
                        });
                    }
                } else {
                    if (!isexpanded) {
                        // Content is not available so request it.
                        var loadingStrPromise = str.get_string('loading', 'theme_snap');
                        $.when(loadingStrPromise).done(function(loadingStr) {
                            pageMod.find('.contentafterlink').prepend(
                                '<div class="ajaxstatus alert alert-info">' + loadingStr + '</div>'
                            );
                        });
                        var getPageUrl = M.cfg.wwwroot + '/theme/snap/rest.php?action=get_page&contextid=' +
                            readmore.data('pagemodcontext');
                        $.ajax({
                            type: "GET",
                            async: true,
                            url: getPageUrl,
                            success: function(data) {
                                ajaxNotify.ifErrorShowBestMsg(data).done(function(errorShown) {
                                    if (errorShown) {
                                        return;
                                    } else {
                                        // No errors, reveal page mod.
                                        pageModContent.find('#pagemod-content-container').prepend(data.html);
                                        pageModContent.data('content-loaded', 1);
                                        pageMod.find('.contentafterlink .ajaxstatus').remove();
                                        revealPageMod(pageMod);
                                        Event.notifyFilterContentUpdated('.pagemod-content');
                                    }
                                });
                            }
                        }).then(
                            ()=>{
                                $(document).trigger('snap-course-content-loaded');
                            }
                        );
                    } else {
                        revealPageMod(pageMod);
                    }
                }

                e.preventDefault();
            });
        };

        return {

            init: function() {

                // Listeners.
                listenPageModuleReadMore();
            }
        };
    }
);
