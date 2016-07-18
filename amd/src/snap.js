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
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* exported snapInit */

/**
 * Main snap initialising function.
 */
define(['jquery', 'theme_snap/bootstrap', 'core/log', 'theme_snap/headroom', 'theme_snap/personal_menu',
        'theme_snap/responsive_video', 'theme_snap/section_asset_movement', 'theme_snap/cover_image'],
    function($, bsjq, log, Headroom, personalMenu, responsiveVideo, sectionAssetMovement, coverImage) {

        'use strict';

        // Use bootstrap modified jquery (tooltips).
        $ = bsjq;

        M.theme_snap = M.theme_snap || {};

        /**
         * master switch for logging
         * @type {boolean}
         */
        var loggingenabled = false;

        /**
         * height of navigation bar
         * @type {number}
         */
        var navheight = $('#mr-nav').outerHeight();

        /**
         * timestamp for when window was last resized
         * @type {null}
         */
        var resizestamp = null;

        if (!loggingenabled) {
            log.disableAll(true);
        } else {
            log.enableAll(true);
        }

        /**
         * Get all url parameters from href
         * @param href
         */
        var getURLParams = function(href) {
            // Create temporary array from href.
            var ta = href.split('?');
            if (ta.length < 2) {
                return false; // No url params
            }
            // Get url params full string from href.
            var urlparams = ta[1];

            // Strip out hash component
            urlparams = urlparams.split('#')[0];

            // Get urlparam items.
            var items = urlparams.split('&');

            // Create params array of values hashed by key.
            var params = [];
            for (var i = 0; i < items.length; i++) {
                var item = items[i].split('=');
                var key = item[0];
                var val = item[1];
                params[key] = val;
            }
            return (params);
        };

        /**
         * Ensure lightbox container exists.
         *
         * @param appendto
         * @param onclose
         * @returns {*|jQuery|HTMLElement}
         */
        var lightbox = function(appendto, onclose) {
            var lbox = $('#snap-light-box');
            if (lbox.length === 0) {
                $(appendto).append('<div id="snap-light-box" tabindex="-1">' +
                    '<div id="snap-light-box-content"></div>' +
                    '<a id="snap-light-box-close" class="pull-right snap-action-icon" href="#">' +
                    '<i class="icon icon-close"></i><small>Close</small>' +
                    '</a>' +
                    '</div>');
                $('#snap-light-box-close').click(function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    lightboxclose();
                    if (typeof(onclose) === 'function') {
                        onclose();
                    }
                });
                lbox = $('#snap-light-box');
            }
            return lbox;
        };

        /**
         * Close lightbox.
         */
        var lightboxclose = function() { // jshint ignore:line
            var lbox = lightbox();
            lbox.remove();
        };

        /**
         * Open lightbox and set content if necessary.
         *
         * @param content
         * @param appendto
         * @param onclose
         */
        var lightboxopen = function(content, appendto, onclose) {
            appendto = appendto ? appendto : $('body');
            var lbox = lightbox(appendto, onclose);
            if (content) {
                var contentdiv = $('#snap-light-box-content');
                contentdiv.html('');
                contentdiv.append(content);
            }
            lbox.addClass('state-visible');
        };

        /**
         * move PHP errors into header
         *
         * @author Guy Thomas
         * @date 2014-05-19
         * @return void
         */
        var movePHPErrorsToHeader = function() {
            // Remove <br> tags inserted before xdebug-error.
            var xdebugs = $('.xdebug-error');
            if (xdebugs.length) {
                for (var x = 0; x < xdebugs.length; x++) {
                    var el = xdebugs[x];
                    var fontel = el.parentNode;
                    var br = $(fontel).prev('br');
                    $(br).remove();
                }
            }

            // Get messages using the different classes we want to use to target debug messages.
            var msgs = $('.xdebug-error, .php-debug, .debuggingmessage');

            if (msgs.length) {
                // OK we have some errors - lets shove them in the footer.
                $(msgs).addClass('php-debug-footer');
                var errorcont = $('<div id="footer-error-cont"><h3>' +
                    M.util.get_string('debugerrors', 'theme_snap') +
                    '</h3><hr></div>');
                $('#page-footer').append(errorcont);
                $('#footer-error-cont').append(msgs);
                // Add rulers
                $('.php-debug-footer').after($('<hr>'));
                // Lets also add the error class to the header so we know there are some errors.
                $('#mr-nav').addClass('errors-found');
                // Lets add an error link to the header.
                var errorlink = $('<a class="footer-error-link btn btn-danger" href="#footer-error-cont">' +
                    M.util.get_string('problemsfound', 'theme_snap') + ' <span class="badge">' + (msgs.length) + '</span></a>');
                $('#page-header').append(errorlink);
            }
        };

        /**
         * Are we on the course page?
         * Note: This doesn't mean that we are in a course - Being in a course could mean that you are on a module page.
         * This means that you are actually on the course page.
         */
        var onCoursePage = function() {
            return $('body').attr('id').indexOf('page-course-view-') === 0;
        };

        /**
         * Scroll to an element on page.
         * Only ever called by scrollToModule
         * @param jqueryCollection el
         * @return void
         */
        var scrollToElement = function(el) {
            if (!el.length) {
                // Element does not exist so exit.
                return;
            }
            if (el.length > 1) {
                // If collection has more than one element then exit - we can't scroll to more than one element!
                return;
            }
            var scrtop = el.offset().top - navheight;
            $('html, body').animate({
                scrollTop: scrtop
            }, 600);
        };

        /**
         * Apply block hash to form actions etc if necessary.
         */
        var applyBlockHash = function() {
            // Add block hash to add block form.
            if (onCoursePage()) {
                $('.block_adminblock form').each(function() {
                    $(this).attr('action', $(this).attr('action') + '#coursetools');
                });
            }

            if (location.hash !== '') {
                return;
            }

            var urlParams = getURLParams(location.href);

            // If calendar navigation has been clicked then go back to calendar.
            if (onCoursePage() && typeof(urlParams.time) !== 'undefined') {
                location.hash = 'coursetools';
                if ($('.block_calendar_month')) {
                    scrollToElement($('.block_calendar_month'));
                }
            }

            // Form selectors for applying blocks hash.
            var formselectors = [
                'body.path-blocks-collect #notice form'
            ];

            // There is no decent selector for block deletion so we have to add the selector if the current url has the
            // appropriate parameters.
            var paramchecks = ['bui_deleteid', 'bui_editid'];
            for (var p in paramchecks) {
                var param = paramchecks[p];
                if (typeof(urlParams[param]) !== 'undefined') {
                    formselectors.push('#notice form');
                    break;
                }
            }

            // If required, apply #coursetools hash to form action - this is so that on submitting form it returns to course
            // page on blocks tab.
            $(formselectors.join(', ')).each(function() {
                // Only apply the blocks hash if a hash is not already present in url.
                var formurl = $(this).attr('action');
                if (formurl.indexOf('#') === -1
                    && (formurl.indexOf('/course/view.php') > -1)
                ) {
                    $(this).attr('action', $(this).attr('action') + '#coursetools');
                }
            });
        };

        /**
         * Set forum strings because there isn't a decent renderer for mod/forum
         * It would be great if the official moodle forum module used a renderer for all output
         *
         * @author Guy Thomas
         * @date 2014-05-20
         * @return void
         */
        var setForumStrings = function() {
            $('.path-mod-forum tr.discussion td.topic.starter').attr('data-cellname',
                M.util.get_string('forumtopic', 'theme_snap'));
            $('.path-mod-forum tr.discussion td.picture:not(\'.group\')').attr('data-cellname',
                M.util.get_string('forumauthor', 'theme_snap'));
            $('.path-mod-forum tr.discussion td.picture.group').attr('data-cellname',
                M.util.get_string('forumpicturegroup', 'theme_snap'));
            $('.path-mod-forum tr.discussion td.replies').attr('data-cellname',
                M.util.get_string('forumreplies', 'theme_snap'));
            $('.path-mod-forum tr.discussion td.lastpost').attr('data-cellname',
                M.util.get_string('forumlastpost', 'theme_snap'));
        };

        /**
         * Process toc search string - trim, remove case sensitivity etc.
         *
         * @author Guy Thomas
         * @param string searchString
         * @returns {string}
         */
        var processSearchString = function(searchString) {
            searchString = searchString.trim().toLowerCase();
            return (searchString);
        };

        /**
         * search course modules
         *
         * @author Stuart Lamour
         * @param {array} dataList
         */
        var tocSearchCourse = function(dataList) {
            // keep search input open
            var i;
            var ua = window.navigator.userAgent;
            if (ua.indexOf('MSIE ') || ua.indexOf('Trident/')) {
                // We have reclone datalist over again for IE, or the same search fails the second time round.
                dataList = $("#toc-searchables").find('li').clone(true);
            }

            // TODO - for 2.7 process search string called too many times?
            var searchString = $("#toc-search-input").val();
            searchString = processSearchString(searchString);

            if (searchString.length === 0) {
                $('#toc-search-results').html('');
                $("#toc-search-input").removeClass('state-active');

            } else {
                $("#toc-search-input").addClass('state-active');
                var matches = [];
                for (i = 0; i < dataList.length; i++) {
                    var dataItem = dataList[i];
                    if (processSearchString($(dataItem).text()).indexOf(searchString) > -1) {
                        matches.push(dataItem);
                    }
                }
                $('#toc-search-results').html(matches);
            }
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
            scrollToElement(targmod);

            var searchpin = $("#searchpin");
            if (!searchpin.length) {
                searchpin = $('<i id="searchpin"></i>');
            }

            $(targmod).find('.instancename').prepend(searchpin);
            $(targmod).attr('tabindex', '-1').focus();
        };

        /**
         * Check hash and see if we should scroll to the module
         */
        var checkHashScrollToModule = function() {
            if (location.hash.indexOf("#module") === 0) {
                // we know the hash here is the modid
                scrollToModule(location.hash);
            }
        };

        /**
         * Fat controller for when hashstate/popstate alters
         * Contains the logic and controllers for adding classes for current section &/|| search.
         * @return void
         */
        var showSection = function() {
            // primary use case
            if (onCoursePage()) {
                // check we are not in folder view
                if (!$('.format-folderview').length) {
                    // reset visible section & blocks
                    $('.course-content .main, #moodle-blocks,#coursetools, #snap-add-new-section').removeClass('state-visible');
                    // if the hash is just section, can we skip all this?

                    // we know the params at 0 is a section id
                    // params will be in the format
                    // #section-1&module-7255
                    var urlParams = location.hash.split("&"),
                        section = urlParams[0],
                        mod = urlParams[1] || null;
                    // Course tools special section.

                    if (section == '#coursetools') {
                        $('#moodle-blocks').addClass('state-visible');
                    }

                    // we know that if we have a search modid will be param 1
                    if (mod !== null) {
                        $(section).addClass('state-visible');
                        scrollToModule(mod);
                    } else {
                        $(section).addClass('state-visible').focus();
                        // faux link click behaviour - scroll to page top
                        window.scrollTo(0, 0);
                    }

                    // initial investigation seems to indicate this is not needed
                    // event.preventDefault();
                }

                // default niceties to perform
                var visibleChapters = $('.course-content .main, #coursetools, #snap-add-new-section').filter(':visible');
                if (!visibleChapters.length) {
                    // show chapter 0
                    $('#section-0').addClass('state-visible').focus();
                    window.scrollTo(0, 0);
                }

                responsiveVideo.apply();
                // add faux :current class to the relevant section in toc
                var sectionIdSel = '.main.state-visible, #coursetools.state-visible, #snap-add-new-section.state-visible';
                var currentSectionId = $(sectionIdSel).attr('id');
                $('#chapters li').removeClass('current');
                $('#chapters a[href$="' + currentSectionId + '"]').parent('li').addClass('current');
            }
        };

        /**
         * Apply body classes which could not be set by renderers - e.g. when a notice was outputted.
         * We could do this in plain CSS if there was such a think as a parent selector.
         */
        var bodyClasses = function() {
            var extraclasses = [];
            if ($('#notice.snap-continue-cancel').length) {
                extraclasses.push('hascontinuecancel');
            }
            $('body').addClass(extraclasses.join(' '));
        };

        var updateModCompletion = function($pagemod, completionhtml) {
            // Update completion tracking icon.
            $pagemod.find('.autocompletion').replaceWith(completionhtml);
        };

        /**
         * Reveal pagemod.
         *
         * @param $pagemod
         * @param string completionhtml - updated completionhtml
         */
        var revealPageMod = function($pagemod, completionhtml) {
            $pagemod.find('.pagemod-content').slideToggle("fast", function() {
                // Animation complete.
                if ($pagemod.is('.state-expanded')) {
                    $pagemod.attr('aria-expanded', 'true');
                    $pagemod.find('.pagemod-content').focus();

                }
                else {
                    $pagemod.attr('aria-expanded', 'false');
                    $pagemod.focus();
                }

            });

            if (completionhtml) {
                updateModCompletion($pagemod, completionhtml);
            }

            // If there is any video in the new content then we need to make it responsive.
            responsiveVideo.apply();
        };

        var lightboxMedia = function(resourcemod) {
            var appendto = $('body');
            var spinner = '<div class="loadingstat three-quarters">' +
                Y.Escape.html(M.util.get_string('loading', 'theme_snap')) +
                '</div>';
            lightboxopen(spinner, appendto, function() {
                $(resourcemod).attr('tabindex', '-1').focus();
                $(resourcemod).removeAttr('tabindex');
            });

            $.ajax({
                type: "GET",
                async: true,
                url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_media&contextid=' + $(resourcemod).data('modcontext'),
                success: function(data) {
                    lightboxopen(data.html, appendto);

                    updateModCompletion($(resourcemod), data.completionhtml);

                    // Execute scripts - necessary for flv to work.
                    var hasflowplayerscript = false;
                    $('#snap-light-box script').each(function() {
                        var script = $(this).text();

                        // Remove cdata from script.
                        script = script.replace(/^(?:\s*)\/\/<!\[CDATA\[/, '').replace(/\/\/\]\](?:\s*)$/, '');

                        // Check for flv video scripts.
                        if (script.indexOf('M.util.add_video_player') > -1) {
                            hasflowplayerscript = true;
                            // This is really important - we have to reset this or it will try to apply flow player to all
                            // the video players it has already initialised and even ones that no longer exist because
                            // they have been wiped from the DOM.
                            M.util.video_players = [];
                        }

                        // Execute script.
                        eval(script); // jshint ignore:line
                    });
                    if (hasflowplayerscript) {
                        var jsurl;
                        if (M.cfg.jsrev == -1) {
                            jsurl = M.cfg.wwwroot + '/lib/flowplayer/flowplayer-3.2.13.js';
                        } else {
                            jsurl = M.cfg.wwwroot +
                                '/lib/javascript.php?jsfile=/lib/flowplayer/flowplayer-3.2.13.min.js&rev=' + M.cfg.jsrev;
                        }
                        $('head script[src="' + jsurl + '"]').remove();
                        // This is so hacky it's untrue, we need to load flow player again but it won't do so unless we
                        // make flowplayer undefined.
                        // Note, we can't use flowplayer.delete in strict mode, hence "= undefined".
                        if (typeof(flowplayer) !== 'undefined') {
                            flowplayer = undefined; // jshint ignore:line
                        }
                        M.util.load_flowplayer();
                        $('head script[src="' + jsurl + '"]').trigger("onreadystatechange");
                    }
                    // Apply responsive video after 1 second. Note: 1 second is just to give crappy flow player time to
                    // sort itself out.
                    window.setTimeout(function() {
                        responsiveVideo.apply();
                    }, 1000);
                    $('#snap-light-box').focus();
                }
            });

        };

        /**
         * Add listeners.
         *
         * just a wrapper for various snippets that add listeners
         */
        var addListeners = function() {

            var selectors = [
                'body:not(.editing):not(.format-folderview) .chapters a',
                'body:not(.format-folderview) .section_footer a',
                'body:not(.format-folderview) #toc-search-results a'
            ];

            $(document).on('click', selectors.join(', '), function(e) {
                var href = this.getAttribute('href');
                if (window.history && window.history.pushState) {
                    history.pushState(null, null, href);
                    // Force hashchange fix for FF & IE9.
                    $(window).trigger('hashchange');
                    // Prevent scrolling to section.
                    e.preventDefault();
                } else {
                    location.hash = href;
                }
            });

            // Listen for popstates - back/fwd.
            var lastHash = location.hash;
            $(window).on('popstate hashchange', function(e) {
                var newHash = location.hash;
                log.info('hashchange');
                if (newHash !== lastHash) {
                    if (location.hash === '#primary-nav') {
                        personalMenu.update();
                    }
                    else {
                        $('#page, #moodle-footer, #js-personal-menu-trigger, #logo, .skiplinks').css('display', '');
                        if (onCoursePage()) {
                            // In folder view we sometimes get here - how?
                            log.info('show section', e.target);
                            if ($('.format-folderview').length) {
                                checkHashScrollToModule();
                            }
                            else {
                                showSection();
                            }
                        }
                    }
                }
                //At the end of the func:
                lastHash = newHash;
            });

            // Show fixed header on scroll down
            // using headroom js - http://wicky.nillia.ms/headroom.js/
            var myElement = document.querySelector("#mr-nav");
            // Construct an instance of Headroom, passing the element.
            var headroom = new Headroom(myElement, {
                "tolerance": 5,
                "offset": 100,
                "classes": {
                    // when element is initialised
                    initial: "headroom",
                    // when scrolling up
                    pinned: "headroom--pinned",
                    // when scrolling down
                    unpinned: "headroom--unpinned",
                    // when above offset
                    top: "headroom--top",
                    // when below offset
                    notTop: "headroom--not-top"
                }
            });
            // When not signed in always show mr-nav?
            if (!$('.notloggedin').length) {
                headroom.init();
            }

            // Listener for toc search.
            var dataList = $("#toc-searchables").find('li').clone(true);
            $('#toc-search-input').keyup(function() {
                tocSearchCourse(dataList);
            });

            // Handle keyboard navigation of search items.
            $("#toc-search-input").keydown(function(e) {
                var keyCode = e.keyCode || e.which;
                if (keyCode === 9) {
                    // 9 tab
                    // 13 enter
                    // 40 down arrow
                    // Register listener for exiting search result.
                    $('#toc-search-results a').last().blur(function() {
                        $(this).off('blur'); // unregister listener
                        $("#toc-search-input").val('');
                        $('#toc-search-results').html('');
                        $("#toc-search-input").removeClass('state-active');
                    });

                }
            });

            $(document).on("click", '#toc-search-results a', function() {
                $("#toc-search-input").val('');
                $('#toc-search-results').html('');
                $("#toc-search-input").removeClass('state-active');
            });

            /**
             * When the document is clicked, if the closest object that was clicked was not the search input then close
             * the search results.
             * Note that this is triggered also when you click on a search result as the results should no longer be
             * required at that point.
             */
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#toc-search-input').length) {
                    $("#toc-search-input").val('');
                    $('#toc-search-results').html('');
                    $("#toc-search-input").removeClass('state-active');
                }
            });

            // Add toggle class for hide/show activities/resources - additional to moodle adding dim.
            $(document).on("click", '[data-action=hide],[data-action=show]', function() {
                $(this).closest('li.activity').toggleClass('draft');
            });

            // Resource cards clickable.
            $(document).on('click', '.snap-resource', function(e) {
                var trigger = $(e.target),
                    hreftarget = '_self',
                    link = $(trigger).closest('.snap-resource').find('.snap-asset-link a'),
                    href = '';
                if (link.length > 0) {
                    href = $(link).attr('href');
                }

                // Excludes any clicks in the actions menu, on links or forms.
                if (!$(trigger).closest('form, a, input, label').length) {
                    if ($(this).hasClass('js-snap-media')) {
                        lightboxMedia(this);
                    } else {
                        if (href === '') {
                            return;
                        }
                        if ($(link).attr('target') === '_blank') {
                            hreftarget = '_blank';
                        }
                        window.open(href, hreftarget);
                    }
                    e.preventDefault();
                }
            });

            // Onclick for toggle of state-visible of admin block and mobile menu.
            $(document).on("click", "#admin-menu-trigger, #toc-mobile-menu-toggle", function(e) {
                var href = this.getAttribute('href');
                // Make this only happen for settings button
                if (this.getAttribute('id') == 'admin-menu-trigger') {
                    $(this).toggleClass('active');
                    $('#page').toggleClass('offcanvas');
                }
                $(href).attr('tabindex', '0');
                $(href).toggleClass('state-visible').focus();
                e.preventDefault();
            });

            // Mobile menu button.
            $(document).on("click", "#course-toc.state-visible a", function() {
                $('#course-toc').removeClass('state-visible');
            });

            // Page mod toggle content.
            var pageToggleSelector = ".modtype_page .instancename,.pagemod-readmore,.pagemod-content .snap-action-icon";
            $(document).on("click", pageToggleSelector, function(e) {
                var $pagemod = $(this).closest('.modtype_page');
                scrollToElement($pagemod);
                var isexpanded = $pagemod.hasClass('state-expanded');
                $pagemod.toggleClass('state-expanded');

                var readmore = $pagemod.find('.pagemod-readmore');

                var $pagemodcontent = $pagemod.find('.pagemod-content');
                if ($pagemodcontent.data('content-loaded') == 1) {
                    // Content is already available so reveal it immediately.
                    revealPageMod($pagemod);
                    var readPageUrl = M.cfg.wwwroot + '/theme/snap/rest.php?action=read_page&contextid=' +
                        readmore.data('pagemodcontext');
                    if (!isexpanded) {
                        $.ajax({
                            type: "GET",
                            async: true,
                            url: readPageUrl,
                            success: function(data) {
                                // Update completion html for this page mod instance.
                                updateModCompletion($pagemod, data.completionhtml);
                            }
                        });
                    }
                } else {
                    if (!isexpanded) {
                        // Content is not available so request it.
                        $pagemod.find('.contentafterlink').prepend(
                            '<div class="ajaxstatus alert alert-info">' + M.str.theme_snap.loading + '</div>'
                        );
                        var getPageUrl = M.cfg.wwwroot + '/theme/snap/rest.php?action=get_page&contextid=' +
                            readmore.data('pagemodcontext');
                        $.ajax({
                            type: "GET",
                            async: true,
                            url: getPageUrl,
                            success: function(data) {
                                $pagemodcontent.prepend(data.html);
                                $pagemodcontent.data('content-loaded', 1);
                                $pagemod.find('.contentafterlink .ajaxstatus').remove();
                                revealPageMod($pagemod, data.completionhtml);
                            }
                        });
                    } else {
                        revealPageMod($pagemod);
                    }
                }

                e.preventDefault();
            });

            $(document).on("click", ".news-article .toggle", function(e) {
                var $news = $(this).closest('.news-article');
                scrollToElement($news);
                $(".news-article").not($news).removeClass('state-expanded');
                $(".news-article-message").css('display', 'none');

                $news.toggleClass('state-expanded');
                $('.state-expanded').find('.news-article-message').slideDown("fast", function() {
                    // Animation complete.
                    if ($news.is('.state-expanded')) {
                        $news.find('.news-article-message').focus();
                        $news.attr('aria-expanded', 'true');
                    }
                    else {
                        $news.focus();
                        $news.attr('aria-expanded', 'false');
                    }
                });
                responsiveVideo.apply();
                e.preventDefault();
            });

            // Listen for window resize for videos.
            $(window).resize(function() {
                resizestamp = new Date().getTime();
                (function(timestamp) {
                    window.setTimeout(function() {
                        log.info('checking ' + timestamp + ' against ' + resizestamp);
                        if (timestamp === resizestamp) {
                            log.info('running resize hook functions');
                            responsiveVideo.apply();
                        } else {
                            log.info('skipping resize hook functions - timestamp has changed from ' +
                                timestamp + ' to ' + resizestamp);
                        }
                    }, 200); // wait 1/20th of a second before resizing
                })(resizestamp);
            });

            // Bootstrap js elements

            // Iniitalise core bootsrtap tooltip js
            $(function() {
                var supportsTouch = false;
                if ('ontouchstart' in window) {
                    //iOS & android
                    supportsTouch = true;
                } else if (window.navigator.msPointerEnabled) {
                    //Win8
                    supportsTouch = true;
                }
                if (!supportsTouch) {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });
        };

        /**
         * Initialise.
         */
        return {
            snapInit: function(courseconfig, pageHasCourseContent, siteMaxBytes) {

                // Set up
                M.theme_snap.courseid = courseconfig.id;
                M.theme_snap.courseconfig = courseconfig;
                M.cfg.context = courseconfig.contextid;

                // When document has loaded.
                $(document).ready(function() {
                    movePHPErrorsToHeader(); // boring
                    setForumStrings(); // whatever
                    addListeners(); // essential
                    applyBlockHash(); // change location hash if necessary
                    bodyClasses(); // add body classes

                    // AMD modules.
                    if (pageHasCourseContent) {
                        sectionAssetMovement.init();
                    }
                    coverImage(M.theme_snap.courseconfig.shortname, siteMaxBytes);

                    // SL - 19th aug 2014 - check we are in a course
                    if (onCoursePage()) {
                        showSection();
                    }

                    if ($('body').hasClass('snap-fixy-open')) {
                        personalMenu.update();
                    }

                    // SL - 19th aug 2014 - resposive video and snap search in exceptions.
                    // SHAME - make section name creation mandatory
                    if ($('#page-course-editsection.format-topics').length) {
                        var usedefaultname = document.getElementById('id_usedefaultname'),
                            sname = document.getElementById('id_name');
                        usedefaultname.value = '0';
                        usedefaultname.checked = false;
                        sname.required = "required";
                        sname.focus();
                        $('#id_name + span').css('display', 'none');

                        // Enable the cancel button.
                        $('#id_cancel').on('click', function() {
                            $('#id_name').removeAttr('required');
                            $('#mform1').submit();
                        });
                    }

                    if ($('.format-folderview').length) {
                        // Check if we are searching for a mod.
                        checkHashScrollToModule();
                    }

                    // Book mod print button.
                    if ($('#page-mod-book-view').length) {
                        var urlParams = getURLParams(location.href);
                        $('.block_book_toc').append('<p>' +
                            '<hr><a target="_blank" href="/mod/book/tool/print/index.php?id=' + urlParams.id + '">' +
                            M.util.get_string('printbook', 'booktool_print') +
                            '</a></p>');
                    }

                    var mod_settings_id_re = /^page-mod-.*-mod$/; // e.g. #page-mod-resource-mod or #page-mod-forum-mod
                    var on_mod_settings = mod_settings_id_re.test($('body').attr('id')) && location.href.indexOf("modedit") > -1;
                    var on_course_settings = $('body').attr('id') === 'page-course-edit';
                    var on_section_settings = $('body').attr('id') === 'page-course-editsection';

                    if (on_mod_settings || on_course_settings || on_section_settings) {
                        // Wrap advanced options in a div
                        var vital = [
                            ':first',
                            '#page-course-edit #id_descriptionhdr',
                            '#id_contentsection',
                            '#id_general + #id_general', // Turnitin duplicate ID bug.
                            '#id_content',
                            '#page-mod-choice-mod #id_optionhdr',
                            '#page-mod-assign-mod #id_availability',
                            '#page-mod-assign-mod #id_submissiontypes',
                            '#page-mod-workshop-mod #id_gradingsettings',
                            '#page-mod-choicegroup-mod #id_miscellaneoussettingshdr',
                            '#page-mod-choicegroup-mod #id_groups',
                            '#page-mod-scorm-mod #id_packagehdr',
                        ];
                        vital = vital.join();

                        $('#mform1 > fieldset').not(vital).wrapAll('<div class="snap-form-advanced col-md-4" />');

                        // Add expand all to advanced column
                        $(".snap-form-advanced").append($(".collapsible-actions"));

                        // Sanitize required input into a single fieldset
                        var main_form = $("#mform1 fieldset:first");
                        var append_to = $("#mform1 fieldset:first .fcontainer");

                        var required = $("#mform1 > fieldset").not("#mform1 > fieldset:first");
                        for (var i = 0; i < required.length; i++) {
                            var content = $(required[i]).find('.fcontainer');
                            $(append_to).append(content);
                            $(required[i]).remove();
                        }
                        $(main_form).wrap('<div class="snap-form-required col-md-8" />');

                        var description = $("#mform1 fieldset:first .fitem_feditor:not(.required)");

                        if (on_mod_settings && description) {
                            var editingassignment = $('body').attr('id') == 'page-mod-assign-mod';
                            var editingchoice = $('body').attr('id') == 'page-mod-choice-mod';
                            var editingturnitin = $('body').attr('id') == 'page-mod-turnitintool-mod';
                            var editingworkshop = $('body').attr('id') == 'page-mod-workshop-mod';
                            if (!editingchoice && !editingassignment && !editingturnitin && !editingworkshop) {
                                $(append_to).append(description);
                                $(append_to).append($('#fitem_id_showdescription'));
                            }
                        }

                        var savebuttons = $("#mform1 #fgroup_id_buttonar");
                        $(main_form).append(savebuttons);
                    }

                    $(window).on('load', function() {
                        // Add a class to the body to show js is loaded.
                        $('body').addClass('snap-js-loaded');
                        // Make video responsive.
                        // Note, if you don't do this on load then FLV media gets wrong size.
                        responsiveVideo.apply();
                    });
                });
            }
        };
    }
);
