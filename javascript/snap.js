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

/**
 * Main snap initialising function.
 */
function snapInit() {

    'use strict';

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

    /**
     * console.log wrapper
     * @param {string} msg
     * @param obj
     */
    var logmsg = function(msg, obj, logtype){
        if (!logtype){
            // Set default log type.
            logtype = 'log';
        }
        // Make sure logtype is valid - i.e. log, warn or error and if not then just set it to log.
        var logtypes = ['log', 'warn', 'error'];
        if ($.inArray(logtype, logtypes) === -1){
            logtype = 'log';
        }
        if (!loggingenabled){
            return;
        }
        if (console !== null && console[logtype] !== null){
            if (obj){
                console[logtype](msg,obj);
            } else {
                console[logtype](msg);
            }
        }
    };

    /**
     * log error message
     * @param msg
     * @param obj
     */
    var logerror = function (msg, obj) {
        logmsg (msg, obj, 'error');
    };

    /**
     * log error message
     * @param msg
     * @param obj
     */
    var logwarn = function (msg, obj) {
        logmsg (msg, obj, 'warn');
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
        if (xdebugs.length){
            for (var x = 0; x < xdebugs.length; x++){
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
            var errorcont = $('<div id="footer-error-cont"><h3>' + M.util.get_string('debugerrors', 'theme_snap') + '</h3><hr></div>');
            $('#page-footer').append(errorcont);
            $('#footer-error-cont').append(msgs);
            // Add rulers
            $('.php-debug-footer').after ($('<hr>'));
            // Lets also add the error class to the header so we know there are some errors.
            $('#mr-nav').addClass('errors-found');
            // Lets add an error link to the header.
            var errorlink = $('<a class="footer-error-link btn btn-danger" href="#footer-error-cont">' +
            M.util.get_string('problemsfound', 'theme_snap') + ' <span class="badge">' + (msgs.length) + '</span></a>');
            $('#mr-nav').append(errorlink);
        }
    };

    /**
     * Are we on the course page?
     * Note: This doesn't mean that we are in a course - Being in a course could mean that you are on a module page.
     * This means that you are actually on the course page.
     */
    var onCoursePage = function () {
        return $('body').attr('id').indexOf('page-course-view-') === 0;
    };

    /**
     * Apply block hash to form actions etc if necessary.
     */
    var applyBlockHash = function(){
        // Add block hash to add block form.
        if (onCoursePage()){
            $('.block_adminblock form').each(function(){
                $(this).attr('action', $(this).attr('action') + '#coursetools');
            });
        }

        if (location.hash !== ''){
            return;
        }

        var urlParams = getURLParams(location.href);

        // If calendar navigation has been clicked then go back to calendar
        if (onCoursePage() && typeof(urlParams.time) !== 'undefined'){
            location.hash = 'blocks';
            if ($('.block_calendar_month')) {
                scrollToElement($('.block_calendar_month'));
            }
        }

        // Form selectors for applying blocks hash
        var formselectors = [
            'body.path-blocks-collect #notice form'
        ];

        // There is no decent selector for block deletion so we have to add the selector if the current url has the
        // appropriate parameters.
        var paramchecks = ['bui_deleteid', 'bui_editid'];
        for (var p in paramchecks){
            var param = paramchecks[p];
            if (typeof(urlParams[param]) !== 'undefined'){
                formselectors.push('#notice form');
                break;
            }
        }

        // If required, apply #coursetools hash to form action - this is so that on submitting form it returns to course
        // page on blocks tab.
        $(formselectors.join(', ')).each(function(){
            // Only apply the blocks hash if a hash is not already present in url.
            var formurl = $(this).attr('action');
            if (formurl.indexOf('#') === -1
                && (formurl.indexOf('/course/view.php') > -1)
                ){
                $(this).attr('action', $(this).attr('action') + '#coursetools');
            }
        });
    };

    /**
     * Code to add an internal link to the admin block & a close button
     * @author Mark Neilson & Sam Chaffee
     */
    var testAdminBlock = function(){
        // Get admin block via class.
        var settingsblock = $('.block.block_settings');
        if (!settingsblock.length){
            return;
        }

        // Add close button for admin block with close text/string from moodle lang file.
        $(settingsblock).prepend("<a class='settings-button  snap-action-icon'><i class='icon icon-arrows-01'></i><small>" +
        M.util.get_string('close', 'theme_snap') + "</small></a>");

        // Get settings block id.
        var settingsBlockHref = '#' + $(settingsblock).attr('id');

        // Add as href for link.
        $('.settings-button').css('display','inline-block').attr('href', settingsBlockHref);
    };

    /**
     * Apply responsive video to non HTML5 video elements.
     *
     * @author Guy Thomas
     * @date 2014-06-09
     */
    var applyResponsiveVideo = function () {
        // Should we be targeting all elements of this type, or should we be more specific?
        // E.g. for externally embedded video like youtube we have to go with iframes but what happens if there is
        // an iframe and it isn't a video iframe - it still gets processed by this script.
        $('.mediaplugin object, .mediaplugin embed, iframe').not( "[data-iframe-srcvideo='value']").each(function() {
            var width,
                height,
                aspectratio;

            var tagname = this.tagName.toLowerCase();
            if (tagname === 'iframe') {
                var supportedsites = ['youtube.com', 'youtu.be', 'vimeo.com', 'youtube-nocookie.com', 'embed.ted.com', 'kickstarter.com', 'video.html'];
                var supported = false;
                for (var s in supportedsites) {
                    if (this.src.indexOf(supportedsites[s]) > -1){
                        supported = true;
                        break;
                    }
                }
                this.setAttribute('data-iframe-srcvideo', (supported ? '1' : '0'));
                if (!supported){
                    return true; // Skip as not supported.
                }
                // Set class.
                $(this).parent().addClass('videoiframe');
            }

            aspectratio = this.getAttribute('data-aspect-ratio');
            if (aspectratio === null){ // Note, an empty attribute should evaluate to === null.
                // Calculate aspect ratio.
                width = this.width || this.offsetWidth;
                width = parseInt(width);
                height = this.height || this.offsetHeight;
                height = parseInt(height);
                aspectratio = height / width;
                this.setAttribute('data-aspect-ratio', aspectratio);
            }

            if (tagname === 'iframe'){
                // Remove attributes.
                $(this).removeAttr('width');
                $(this).removeAttr('height');
            }

            // Get width again.
            width = parseInt(this.offsetWidth);
            // Set height based on width and aspectratio
            var style = {height: (width * aspectratio) + 'px'};
            $(this).css(style);
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
        $('.path-mod-forum tr.discussion td.topic.starter').attr('data-cellname', M.util.get_string('forumtopic', 'theme_snap'));
        $('.path-mod-forum tr.discussion td.picture:not(\'.group\')').attr('data-cellname', M.util.get_string('forumauthor', 'theme_snap'));
        $('.path-mod-forum tr.discussion td.picture.group').attr('data-cellname', M.util.get_string('forumpicturegroup', 'theme_snap'));
        $('.path-mod-forum tr.discussion td.replies').attr('data-cellname', M.util.get_string('forumreplies', 'theme_snap'));
        $('.path-mod-forum tr.discussion td.lastpost').attr('data-cellname', M.util.get_string('forumlastpost', 'theme_snap'));
    };

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
        for (var i = 0; i < items.length; i++){
            var item = items[i].split('=');
            var key = item[0];
            var val = item[1];
            params[key] = val;
        }
        return (params);
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
        if (ua.indexOf('MSIE ') || ua.indexOf('Trident/')){
            // We have reclone datalist over again for IE, or the same search fails the second time round.
            var dataList = $("#toc-searchables").find('li').clone(true);
        }

        // TODO - for 2.7 process search string called too many times?
        var searchString = $("#toc-search-input").val();
        searchString = processSearchString(searchString);

        if(searchString.length === 0) {
            $('#toc-search-results').html('');
            $("#toc-search-input").removeClass('state-active');

        } else {
            $("#toc-search-input").addClass('state-active');
            var matches = [];
            for (i = 0; i < dataList.length; i++) {
                var dataItem = dataList[i];
                if(processSearchString($(dataItem).text()).indexOf(searchString) > -1 ) {
                    matches.push(dataItem);
                }
            }
            $('#toc-search-results').html(matches);
        }
    };

    /**
     * Process toc search string - trim, remove case sensitivity etc.
     *
     * @author Guy Thomas
     * @param string searchString
     * @returns {string}
     */
    var processSearchString = function(searchString){
        searchString = searchString.trim().toLowerCase();
        return (searchString);
    };

    /**
     * Do polyfill stuff.
     *
     * NOTE - would be better to be using yep / nope to load just the scripts we need, however scripts in moodle are
     * typically grouped together and compressed based on the javascript arrays in config.php.
     *
     * @author Guy Thomas
     * @date 2014-06-19
     */
    var polyfills = function() {
        if(!Modernizr.input.placeholder) {
            $('input, textarea').placeholder();
        }
    };

    /**
     * Add deadlines, messages, grades & grading,  async'ly to the personal menu
     *
     * @author Stuart Lamour
     */
    var updatePersonalMenu = function(){
        $('#primary-nav').focus();
        // primary nav showing so hide the other dom parts
        $('#page, #moodle-footer').hide(0);

        /**
         * Load ajax info into personal menu.
         *
         */
        var loadajaxinfo = function(type){
            var container = $('#snap-personal-menu-' + type);
            if($(container).length) {
                var cache_key = M.cfg.sesskey + 'personal-menu-' + type;
                try {
                    // Display old content while waiting
                    if(window.sessionStorage[cache_key]) {
                        logmsg('using locally stored ' + type);
                        var html = window.sessionStorage[cache_key];
                        $(container).html(html);
                    }
                    logmsg('fetching ' + type);
                    $.ajax({
                          type: "GET",
                          async:  true,
                          url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_' + type +'&contextid=' + M.cfg.context,
                          success: function(data){
                            logmsg('fetched ' + type);
                            window.sessionStorage[cache_key] = data.html;
                            $(container).html(data.html);
                          }
                    });
                } catch(err) {
                    sessionStorage.clear();
                    logerror(err);
                }
            }
        };

        loadajaxinfo('deadlines');
        loadajaxinfo('graded');
        loadajaxinfo('grading');
        loadajaxinfo('messages');


        // Load course information via ajax.
        var courseids = [];
        var courseinfo_key = M.cfg.sesskey + 'courseinfo';
        $('.courseinfo .dynamicinfo').each(function() {
            courseids.push ($(this).attr('data-courseid'));
        });
        if (courseids.length > 0) {

            /**
             * Apply course information to courses in personal menu.
             *
             * @param crsinfo
             */
            var applycourseinfo = function(crsinfo){
                for (var i in crsinfo){
                    var info = crsinfo[i];
                    logmsg('applying course data for courseid '+info.courseid);
                    var courseinfohtml = info.progress.progresshtml;
                    if (info.feedback && info.feedback.feedbackhtml){
                        courseinfohtml += info.feedback.feedbackhtml;
                    }
                    $('.courseinfo [data-courseid="'+info.courseid+'"]').html(courseinfohtml);
                }
            };

            // OK - lets see if we have grades/progress in session storage we can use before ajax call.
            if (window.sessionStorage[courseinfo_key]){
                var courseinfo = JSON.parse(window.sessionStorage[courseinfo_key]);
                applycourseinfo (courseinfo);
            }

            // Get course info via ajax.
            var courseiddata = 'courseids='+courseids.join(',');
            logmsg("fetching course data");
            $.ajax({
                type: "GET",
                async:  true,
                url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_courseinfo&contextid=' + M.cfg.context,
                data: courseiddata,
                success: function(data){
                    if (data.info) {
                        logmsg('fetched coursedata', data.info);
                        window.sessionStorage[courseinfo_key] = JSON.stringify(data.info);
                        applycourseinfo(data.info);
                    } else {
                        logwarn('fetched coursedata with error: JSON data object is missing info property', data);
                    }
                }
            });
        }
    };

    /**
     * Scroll to an element on page.
     * Only ever called by scrollToModule
     * @param jqueryCollection el
     * @return void
     */
    var scrollToElement = function(el){
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
     * Check hash and see if we should scroll to the module
     */
    var checkHashScrollToModule = function(){
        if(location.hash.indexOf("#module") === 0) {
            // we know the hash here is the modid
            scrollToModule(location.hash);
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
        var targmod = $("#" + modid.replace('#',''));
        // http://stackoverflow.com/questions/6677035/jquery-scroll-to-element
        scrollToElement(targmod);

        var searchpin = $("#searchpin");
        if (!searchpin.length){
            searchpin = $('<i id="searchpin" class="icon icon-office-01"></i>');
        }

        $(targmod).find('.instancename').prepend(searchpin);
        $(targmod).attr('tabindex','-1').focus();
    };

    /**
     * Fat controller for when hashstate/popstate alters
     * Contains the logic and controllers for adding classes for current section &/|| search.
     * @return void
     */
    var showSection = function() {
        // primary use case
        if(onCoursePage()) {
            // check we are not in folder view
            if(!$('.format-folderview').length){
                // reset visible section & blocks
                $('.course-content .main, #moodle-blocks,#coursetools').removeClass('state-visible');
                // if the hash is just section, can we skip all this?

                // we know the params at 0 is a section id
                // params will be in the format
                // #section-1&module-7255
                var urlParams = location.hash.split("&"),
                section = urlParams[0],
                mod = urlParams[1] || null;
                // Course tools special section.

                if(section == '#coursetools') {
                    $('#moodle-blocks').addClass('state-visible');
                }

                // we know that if we have a search modid will be param 1
                if(mod !== null) {
                    $(section).addClass('state-visible');
                    scrollToModule(mod);
                } else if(!$('.editing').length){
                    $(section).addClass('state-visible').focus();
                    // faux link click behaviour - scroll to page top
                    window.scrollTo(0, 0);
                }

                // initial investigation seems to indicate this is not needed
                // event.preventDefault();
            }

            // default niceties to perform
            var visibleChapters = $('.course-content .main, #coursetools').filter(':visible');
            if (!visibleChapters.length) {
                // show chapter 0
                $('#section-0').addClass('state-visible').focus();
                window.scrollTo(0, 0);
            }

            applyResponsiveVideo();
            // add faux :current class to the relevant section in toc
            var currentSectionId = $('.main.state-visible, #coursetools.state-visible').attr('id');
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
            logmsg('hashchange');
            if(newHash !== lastHash){
                if(location.hash === '#primary-nav') {
                    updatePersonalMenu();
                    // GT - 2014-10-16 - Add ellipsis to long course titles
                    $('.courseinfo-body h3 a').ellipsis();
                }
                else{
                    $('#page, #moodle-footer').show(0);
                    if(onCoursePage()) {
                        // In folder view we sometimes get here - how?
                        logmsg('show section', e.target);
                        if($('.format-folderview').length){
                            checkHashScrollToModule();
                        }
                        else{
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
                initial : "headroom",
                // when scrolling up
                pinned : "headroom--pinned",
                // when scrolling down
                unpinned : "headroom--unpinned",
                // when above offset
                top : "headroom--top",
                // when below offset
                notTop : "headroom--not-top"
          }
        });
        // When not signed in always show mr-nav?
        if(!$('.notloggedin').length) {
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
                $('#toc-search-results a').last().blur(function () {
                    $(this).off('blur'); // unregister listener
                    $("#toc-search-input").val('');
                    $('#toc-search-results').html('');
                    $("#toc-search-input").removeClass('state-active');
                });

            }
        });

        $(document).on("click",'#toc-search-results a', function(){
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

        // Make cards - in personal menu and on course - clickable, data-href for resources.
        $(document).on('click', '.courseinfo[data-href], .snap-resource[data-href]', function(e){
            var trigger = $(e.target),
                hreftarget = '_self'; // assume browser can open resource
            // Excludes any clicks in the actions menu, on links or forms.
            if(!$(trigger).closest('.actions, form, a').length) {
                // TODO - add a class in the renderer to set target to blank for none-web docs or external links
                if($(trigger).closest('.snap-resource').is('.target-blank')){
                    hreftarget = '_blank';
                }
                window.open($(this).data('href'), hreftarget);
                e.preventDefault();
            }
        });



        // Onclick for toggle of state-visible of admin block and mobile menu.
        $(document).on("click", ".settings-button, #toc-mobile-menu-toggle", function(e) {
            var href = this.getAttribute('href');
            $(href).toggleClass('state-visible').focus();
            e.preventDefault();
        });

        // Listen for close button to show page content.
        $(document).on("click", "#fixy-close", function() {
            $('#page, #moodle-footer').show();

        });

        // Mobile menu button
        $(document).on("click", "#course-toc.state-visible a", function(e){
            $('#course-toc').removeClass('state-visible');
        });

        // Page mod toggle content
        $(document).on("click", ".modtype_page .instancename,.pagemod-readmore,.pagemod-content .snap-action-icon", function(e) {
            var $pagemod = $(this).closest('.modtype_page');
            scrollToElement($pagemod);
            $pagemod.toggleClass('state-expanded');
            $pagemod.find('.pagemod-content').slideToggle("fast", function() {
                // Animation complete.
                if($pagemod.is('.state-expanded')){
                    $pagemod.attr('aria-expanded', 'true');
                    $pagemod.find('.pagemod-content').focus();

                }
                else {
                    $pagemod.attr('aria-expanded', 'false');
                    $pagemod.focus();
                }

            });
            applyResponsiveVideo();
            e.preventDefault();
        });

        $(document).on("click", ".news-article .toggle", function(e) {
            var $news = $(this).closest('.news-article');
            scrollToElement($news);
            $(".news-article").not($news).removeClass('state-expanded');
            $(".news-article-message").css('display','none');

            $news.toggleClass('state-expanded');
            $('.state-expanded').find('.news-article-message').slideDown("fast", function() {
                // Animation complete.
                if($news.is('.state-expanded')){
                    $news.find('.news-article-message').focus();
                    $news.attr('aria-expanded', 'true');
                }
                else {
                    $news.focus();
                    $news.attr('aria-expanded', 'false');
                }
            });
            applyResponsiveVideo();
            e.preventDefault();
        });




        // Listen for window resize for videos.
        $(window).resize(function() {
            resizestamp = new Date().getTime();
            (function(timestamp){
                window.setTimeout(function() {
                    logmsg ('checking ' + timestamp + ' against ' + resizestamp);
                    if (timestamp === resizestamp) {
                        logmsg('running resize hook functions');
                        applyResponsiveVideo();
                        // Apply another short delay for adding ellipsis.
                        window.setTimeout(function() {$('.courseinfo-body h3 a').ellipsis();}, 200);
                    } else {
                        logmsg('skipping resize hook functions - timestamp has changed from ' + timestamp + ' to ' + resizestamp);
                    }
                },200); // wait 1/20th of a second before resizing
            })(resizestamp);
        });
    };

    // GO !!!!
    movePHPErrorsToHeader(); // boring
    polyfills(); // for none evergreen
    testAdminBlock(); // dull
    setForumStrings(); // whatever
    addListeners(); // essential
    applyBlockHash(); // change location hash if necessary
    bodyClasses(); // add body classes

    // SL - 19th aug 2014 - check we are in a course
    if(onCoursePage()) {
        showSection();
    }

    // SL - 24th july 2014 - if are looking at the personal menu we need to load data
    if(location.href.indexOf("primary-nav") > -1) {
        updatePersonalMenu();
    }

    // GT - 2014-10-16 - Add ellipsis to long course titles
    $('.courseinfo-body h3 a').ellipsis();

    // SL - 19th aug 2014 - resposive video and snap search in exceptions.
    $(document).ready(function() {
        // SHAME - make section name creation mandatory
        if($('#page-course-editsection.format-topics').length) {
            var usedefaultname = document.getElementById('id_usedefaultname'),
                sname = document.getElementById('id_name');
            usedefaultname.value = '0';
            usedefaultname.checked = false;
            sname.required = "required";
            sname.focus();
            $('#id_name + span').css('display','none');
        }

        if($('.format-folderview').length) {
            // Check if we are searching for a mod.
            checkHashScrollToModule();
        }
    });

    $(window).on('load' , function() {

        // Make video responsive.
        // Note, if you don't do this on load then FLV media gets wrong size.
        applyResponsiveVideo();

        // Add ellipsis to long course titles.
        if (window.location.hash == '#primary-nav') {
            $('.courseinfo-body h3 a').ellipsis();
        }

    });

} // End snap init
