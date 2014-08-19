/**
 * Main initialise function for snap theme
 *
 * @author Stuart Lamour / Guy Thomas
 */
function snapInit(){

    /**
     * master switch for logging
     * @type {boolean}
     */
    var loggingenabled = false;

    /**
     * height of navigation bar
     * @type {*|jQuery}
     */
    var navheight = $('#mr-nav').outerHeight();

    /**
     * does an admin block exist
     * @type {boolean |jQuery}
     */
    var adminblock = false;

    /**
     * timestamp for when window was last resized
     * @type {null}
     */
    var resizestamp = null;

    /**
     * console.log wrapper - copes with old browsers
     * @param {string} msg
     * @param obj
     */
    var logger = function(msg,obj){
        if (!loggingenabled){
            return;
        }
        if (console != null && console.log != null){
            if (obj){
                console.log(msg,obj);
            } else {
                console.log(msg);
            }
        }
    }

    /**
     * move PHP errors into header
     *
     * @author Guy Thomas
     * @date 2014-05-19
     * @return void
     */
    var movePHPErrorsToHeader = function() {
        // remove <br> tags inserted before xdebug-error
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
            var errorlink = $('<a class="footer-error-link btn btn-danger" href="#footer-error-cont">' + M.util.get_string('problemsfound', 'theme_snap') + ' <span class="badge">' + (msgs.length) + '</span></a>');
            $('#mr-nav').append(errorlink);
            errorlink.click(function(e){
                e.preventDefault();
                // Scroll to footer error container but don't bother animating.
                scrolltoElement($('#footer-error-cont'), false);
            });
        }
    };

    var applyBlockHash = function(){

        // Add block hash to add block form.
        $('.block_adminblock form').each(function(){
            $(this).attr('action', $(this).attr('action') + '#blocks');
        });

        if (location.hash != ''){
            return;
        }

        var urlParams = getURLParams(location.href);

        // If calendar navigation has been clicked then go back to calendar
        if (typeof(urlParams['time']) != 'undefined'){
            location.hash = 'blocks';
            if ($('.block_calendar_month')) {
                scrolltoElement($('.block_calendar_month'), true);
            }
        }

        // If block configuration / deletion selected then add blocks hash to form action.
        var paramchecks = ['bui_deleteid', 'bui_editid'];
        var applyhashtoform = false;
        for (var p in paramchecks){
            var param = paramchecks[p];
            if (typeof(urlParams[param]) != 'undefined'){
                applyhashtoform = true;
                break;
            }
        }
        // URL whitelist for applying hash to form.
        var urlstohash = [
            'blocks/collect/view.php'
        ];
        for (var u in urlstohash){
            var url = urlstohash[u];
            if (location.href.indexOf(url) > -1){
                applyhashtoform = true;
            }
        }

        // If required, apply #blocks hash to form action - this is so that on submitting form it returns to course
        // page on blocks tab.
        if (applyhashtoform){
            $('form').each(function(){
                // Only apply the blocks hash if a hash is not already present in url.
                if ($(this).attr('action').indexOf('#') == -1){
                    $(this).attr('action', $(this).attr('action') + '#blocks');
                }
            });
        }
    }

    /**
     * Convoluted code to add an internal link to the admin block & a close button
     *
     *
     * @author Stuart Lamour (close button) & Mark Neilson (the rest)
     */
    var testAdminBlock = function(){

        // get admin block via class
        var settingsblock = $('.block.block_settings'),
        adminblock = settingsblock[0];
        if (!settingsblock.length){
            return;
        }

        // add close button for admin block with close text/string from moodle lang file
        $(settingsblock).prepend("<a class='settings-button  snap-action-icon'><i class='icon icon-arrows-01'></i><small>" + M.util.get_string('close', 'theme_snap') + "</small></a>");

        // get settings block id
        var settingsBlockHref = '#' + $(settingsblock).attr('id');
        // add as href for link
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

            var tagname = this.tagName.toLowerCase();
            if (tagname == 'iframe') {
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

            var aspectratio = this.getAttribute('data-aspect-ratio');
            if (aspectratio == null){
                // calculate aspect ratio
                var width = this.width || this.offsetWidth;
                width = parseInt(width);
                var height = this.height || this.offsetHeight;
                height = parseInt(height);
                var aspectratio = height / width;
                this.setAttribute('data-aspect-ratio', aspectratio);
            }

            if (tagname == 'iframe'){
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
        var ta = href.split('?');
        if (ta.length < 2) {
            return false; // No url params
        }
        var href = ta[1];
        var ta = href.split('#');
        href = ta[0];
        var items = href.split('&');
        var params = [];
        for (var i = 0; i < items.length; i++){
            var item = items[i];

            var ta = item.split('=');
            var key = ta[0];
            var val = ta[1];
            params[key] = val;
        }
        return (params);
    }

    /**
     * get params from hash bang
     * e.g. http://joule2.dev/course/view.php?id=160#section-1&modid-6917 becomes
     * {section:'1',modid:'6917'}
     *
     * @author Guy Thomas
     * @date 2014-04-30
     * @param {String} href
     * @return boolean|array
     */
     var getHashBangParams = function(href){
        var ta = href.split('#');
        if (ta.length < 2){
            return false; // invalid hashbang
        }

        var hash = ta[1];
        var items = hash.split('&');
        var params = [];
        for (var i = 0; i < items.length; i++){
            var item = items[i];
            var ta = item.split('-');
            var key = ta[0];
            var val = ta[1];
            params[key] = val;
        }
        return params;
    };

    /**
     * show page section / mod according to current href
     *
     * @author Guy Thomas
     * @date 2014-04-30
     */
    var showPageSectionMod = function(animscroll){
        var hbparams = getHashBangParams(window.location.href);
        if (!hbparams || !hbparams.modid){
            showSection();
        } else {
            focusModule(window.location.href, animscroll);
        }
    };

    /**
     * search course modules
     *
     * @author Stuart Lamour
     * @param {array} dataList
     */
    var tocSearchCourse = function(dataList) {
        var searchString = $("#toc-search-input").val();
        searchString = processSearchString(searchString);

        if(searchString.length === 0) {
            $('#toc-search-results').html('');
            $('#toc-search-input').removeClass('state-visible');
        } else {
            var matches = [];
            var matches_html = [];
            for (var i = 0; i < dataList.length; i++) {
                var dataItem = dataList[i];
                if(containsSearchString(processSearchString($(dataItem).text()), searchString)) {
                    matches.push(dataItem);
                }
            }
            for (var i = 0; i < matches.length; i++) {
                var match = matches[i];
                matches_html.push(match);
            }
            if(matches.length) {
                $('#toc-search-input').addClass('state-visible');
            }
            $('#toc-search-results').html(matches_html);
        }
    };

    /**
     * process toc search strings - trim, remove case sensitivity etc
     *
     * @author Guy Thomas
     * @param string searchString
     * @returns {string}
     */
    var processSearchString = function(searchString){
        searchString = searchString.trim();
        searchString = searchString.toLowerCase();
        return (searchString);
    };

    /**
     * @author Stuart Lamour
     *
     * @param dataItem
     * @param searchString
     * @returns {boolean}
     */
    var containsSearchString = function(dataItem,searchString) {
        var result = dataItem.indexOf(searchString);
        return (result !== -1);
    };

    /**
     * Scroll to element on page.
     *
     * @param node el
     * @param bool animate
     * @param integer ms
     * @return void
     */
    var scrolltoElement = function(el, animate, ms){
        if (!el.length){
            return;
        }
        if (ms == null){
            ms = 1000;
        }
        logger('scrolling to ', el);
        var scrtop = el.offset().top - navheight;

        if (animate){
            $('html, body').animate({
                scrollTop: scrtop
            }, ms);
        } else {
            window.scrollTo(0, scrtop);
        }
    };

    /**
     * Scroll to a module.
     * @param modid
     * @param animscroll
     */
    var scrollToModule = function(modid, animscroll) {
        var targmod = 'module-' + modid;
        // http://stackoverflow.com/questions/6677035/jquery-scroll-to-element
        var mod = $("#" + targmod);
        scrolltoElement(mod, animscroll);

        var searchpin = $("#searchpin");
        if (!searchpin.length){
            var searchpin = $('<i id="searchpin" class="icon icon-office-01"></i>');
        }

        $(mod).find('.instancename').prepend(searchpin);
    }

    /**
     * focus Module on page
     *
     * @author Guy Thomas
     * @param href
     */
    var focusModule = function(href, animscroll){
        // hide search box in case we have clicked a module link (can also be called by page load)
        $('#toc-search-input').removeClass('state-visible');
        var ta = href.split('#');
        if (ta.length < 1){
            return; // invalid hashbang
        }

        var hbparams = getHashBangParams(href);
        if (!hbparams.section){
            var urlParams = getURLParams(href);
            var section = null;
            if (typeof(urlParams['section']) !== 'undefined'){
                var section = urlParams.section;
                if ($('body').hasClass('format-folderview')) {
                    if (!$('#section-' + section).hasClass('expanded')) {
                        $('#section-' + section + ' a.foldertoggle')[0].click();
                    }
                }
                scrollToModule(hbparams.modid, animscroll);

                $('#toc-search-input').val('');

                $("#toc-search-results").html('');

                return;
            }
        }
        if (!hbparams.section || !hbparams.modid){
            // error - no section or mod
            return;
        }

        // make sure we are on the sections tab (can't navigate to mods if on appendices)
        location.hash = 'sections';

        // set browser location and add to history
        if(history.pushState) {
            history.pushState(null, null, href);
        } else {
            location.hash = href;
        }

        // hide all sections
        $('.course-content ul li.section').removeClass('state-visible');
        // make desired section visible
        var targsect = 'section-' + hbparams.section;
        $('#' + targsect).addClass('state-visible');
        // set as :current in toc
        $('#chapters li').removeClass('current');
        $('#chapters a[href="#' + targsect + '"]').parent('li').addClass('current');

        // scroll to module
        scrollToModule(hbparams.modid, animscroll);

        // reset search value
        $('#toc-search-input').val('');

        // hide search results
        $("#toc-search-results").html('');
    };

    /**
     * Do polyfill stuff.
     *
     * NOTE - would be better to be using yep / nope to load just the scripts we need, however scripts in moodle
     * are typically grouped together and compressed based on the javascript arrays in config.php.
     *
     * @author Guy Thomas
     * @date 2014-06-19
     */
    var polyfills = function() {
        if(!Modernizr.input.placeholder) {
            $('input, textarea').placeholder();
        }
    }

    /**
     * Add deadlines, messages async'ly to the personal menu
     *
     * @param key - jsonencoded sesskey
     *
     * @author Stuart Lamour
     */
    var updatePersonalMenu = function(key){
        // check if the primary nav is in the url..
        // not triggering for some reason when you click personal menu button
        // dosn't seem to be too bad leaving out
        // if(window.location.href.indexOf("primary-nav") !== -1) {

        // alert('in update personal menu');
        // primary nav showing so hide the other dom parts
        $('#page, #moodle-footer').hide(0);

            var deadlinesContainer = $('#snap-personal-menu-deadlines');
            if($(deadlinesContainer).length) {
                var deadlines_key = key + "pmDeadlines";
                var deadlines_key_time = deadlines_key + "time";
                try {
                    // Display old content while waiting, if not too old.
                    var refreshbydate = new Date().getTime() - 1 * 60 * 60 * 1000;
                    if(window.localStorage[deadlines_key]
                        && window.localStorage[deadlines_key_time]
                        && window.localStorage[deadlines_key_time] > refreshbydate) {
                        logger("using locally stored deadlines");
                        html = window.localStorage[deadlines_key];
                        $(deadlinesContainer).html(html);
                    }
                    logger("fetching deadlines");
                    $.ajax({
                          type: "GET",
                          async:  true,
                          url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_deadlines&contextid=' + M.cfg.context,
                          success: function(data){
                            logger("fetched deadlines");
                            window.localStorage[deadlines_key] = data.html;
                            window.localStorage[deadlines_key_time] = new Date().getTime();
                            $(deadlinesContainer).html(data.html);
                          }
                    });
                } catch(err) {
                    localStorage.clear();
                    logger(err);
                    // $(deadlinesContainer).html("");
                }
            } // end deadlines div exists check

            var messagesContainer = $('#snap-personal-menu-messages');
            if($(messagesContainer).length) {
                var messages_key = key + "pmMessages";
                var messages_key_time = messages_key + "time";
                try {
                    // Display old content while waiting, if not too old.
                    var refreshbydate = new Date().getTime() - 10 * 60 * 1000;
                    if(window.localStorage[messages_key]
                        && window.localStorage[messages_key_time]
                        && window.localStorage[messages_key_time] > refreshbydate) {
                        logger("using locally stored messages");
                        html = window.localStorage[messages_key];
                        $(messagesContainer).html(html);
                    }
                    logger("fetching messages");
                    $.ajax({
                          type: "GET",
                          async:  true,
                          url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_messages&contextid=' + M.cfg.context,
                          success: function(data){
                            logger("fetched messages");
                            window.localStorage[messages_key] = data.html;
                            window.localStorage[messages_key_time] = new Date().getTime();
                            $(messagesContainer).html(data.html);
                          }
                    });
                } catch(err) {
                    localStorage.clear();
                    logger(err);
                    // $(messagesContainer).html("");
                }
            } // end messages div exists check

        // } // end primary nav shown check
    }

    /**
     * show and focus section
     *
     * @author Guy Thomas
     */
    var showSection = function() {
        // check we are in a course
        if(window.location.href.indexOf("course/view.php?id") > -1) {
            $('.course-content ul li.section').removeClass('state-visible'); // reset visible section
            // check we are not searching
            if(window.location.href.indexOf("modid") < 0) {
                $(window.location.hash).addClass('state-visible').focus();
                // or editing
                if(!$('.editing').length){
                    window.scrollTo(0, 0);
                }
            }
            //
            else{
                var hbparams = getHashBangParams(window.location.href);
                if (hbparams.section != null){
                    $('#section-' + hbparams.section).addClass('state-visible').focus();
                    // if ((!$('body').hasClass('format-topics') && !$('body').hasClass('format-weeks'))|| !$('body').hasClass('editing')) {
                    if(!$('.editing, .format-topics, .format-weeks').length){
                            window.scrollTo(0, 0);
                    } else {
                        // Scroll to section taking into account navbar
                        scrolltoElement($('#section-' + hbparams.section), false, 0);
                    }
                }
            }
            // default niceties to perform
            var visibleChapters = $('.course-content ul li.section').filter(':visible');
            //if no visible chapter
            if (!visibleChapters.length) {
                // show chapter 0
                $('#section-0').addClass('state-visible').focus();
            }
            // add :current class to the relevant section in toc
            $('#chapters li').removeClass('current');
            var currentSectionId = $('.state-visible').attr('id');
            $('#chapters a[href="#' + currentSectionId + '"]').parent('li').addClass('current');

            // Need to call this here as video could have been hidden at the point it was made responsive which means
            // we need to reset width and height now its visible.
            applyResponsiveVideo();
        }
    };

    /**
     * Add listeners.
     *
     * just a wrapper for various snippets that add listeners
     */
    var addListeners = function() {

        // be nice - tidy up after ourselves by clearing localstorage on login/logout
        $(document).on('click','#loginbtn, .logout', function(){
            localStorage.clear();
        });

        // Listen for click on chapter links when chapter not being edited.
        $(document).on("click", 'body:not(.editing) .chapters a, #section_footer a', function(e) {
            var href = this.getAttribute('href');
            $('.course-content ul li.section').removeClass('state-visible');
            // for mobile remove state on click
            $('#chapters, #appendices').removeClass('state-visible');
            $(href).addClass('state-visible').focus();
            if (window.history && window.history.pushState) {
                history.pushState(null, null, href);
            } else {
                location.hash = href;
            }
            // add :current class to the relevant section in toc
            $('#chapters li').removeClass('current');
            $(this).parent('li').addClass('current');

            // Need to call this here as video could have been hidden at the point it was made responsive which means
            // we need to reset width and height now its visible.
            applyResponsiveVideo();
            e.preventDefault();
        });

        // listen for popstates - back/fwd
        //this is fragile
        var lastHash = location.hash;
        $(window).bind("hashchange popstate", function(e) {
            var newHash = location.hash;
            if(newHash !== lastHash){
                if(window.location.href.indexOf("primary-nav") > -1) {
                        updatePersonalMenu($('#js-personal-menu-trigger').data('key'));
                }
                else{
                    $('#page, #moodle-footer').show(0);
                    if(window.location.href.indexOf("course/view.php?id") > -1) {
                        showSection();
                    }
                }
            }

            //At the end of the func:
            lastHash = newHash;
        });

        // show fixed header on scroll down
        // using headroom js - http://wicky.nillia.ms/headroom.js/
        var myElement = document.querySelector("#mr-nav");
        // construct an instance of Headroom, passing the element
        var headroom = new Headroom(myElement, {
          "tolerance": 5,
          "offset": 205,
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
        // when not signed in always show mr-nav?
        if(!$('.notloggedin').length) {
            headroom.init();
        }

        // listener for toc search //
        var dataList = $("#toc-searchables").find('a');
        $('#toc-search-input').keyup(function (e) {
            tocSearchCourse(dataList);
        });

        $("#toc-search-input").focus(function(e) {
            // hide search results
            $('#toc-search-results').html('');
        });

        // handle keyboard navigation of search items
        $("#toc-search-input").keydown(function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode == 9) {
                //e.preventDefault();
                // call custom function here
                $(this).addClass('state-tabbed');

                // register listener for exiting search result //
                $('#toc-search-results a').last().blur(function (e) {
                    $("#toc-search-input").removeClass('state-visible');
                    $("#toc-search-input").val('');
                    $(this).off('blur'); // unregister listener
                });

            }
        });

        // listener for exiting search field //
        $('#toc-search-input').blur(function (e) {
            if ($(this).hasClass('state-tabbed')) {
                // We left on a tab event which means we should now be navigating the search results
                // so don't close the search results.
                $(this).removeClass('state-tabbed');
                return;
            }
            $(this).removeClass('state-visible');
            $(this).val('');
        });

        // listener for clicking search results //
        $(document).on("click", "#toc-search-results a", function(e){
            var href = this.getAttribute('href');
            focusModule(href, true);
            e.preventDefault();
        });

        // Add toggle class for hide/show activities/resources - additional to moodle adding dim
        $(document).on("click", '[data-action=hide],[data-action=show]', function() {
             $(this).closest('li.activity').toggleClass('draft');
        });

        // Make cards clickable - data-href for resources
        $(document).on('click', '.snap-resource[data-href]', function(e){
            // stash event trigger
            var trigger = $(e.target),
                hreftarget = '_self'; // assume browser can open resource
            // excluse any clicks in the actions menu, on links or forms
            if(!$(trigger).closest('.actions, form, a').length) {
                // TODO - add a class in the renderer to set target to blank for none-web docs or external links
                if($(trigger).closest('.snap-resource').is('.target-blank')){
                    hreftarget = '_blank';
                }
                window.open($(this).data('href'), hreftarget);
                e.preventDefault();
            }
        });

        // Listener for small screen showing of chapters & appendicies.
        $(document).on("click", '#course-toc div[role="menubar"] a', function(e) {
        	$('#chapters, #appendices').toggleClass('state-visible');
        });

        // onclick for toggle of state-visible of admin block
        $(document).on("click", ".settings-button", function(e) {
            var href = this.getAttribute('href');
            $(href).toggleClass('state-visible');
            e.preventDefault();
        });
        /*
        // Listen for fixy trigger to hide other page content
        $('.fixy-trigger').click(function() {
            $('#page').hide();
            $('#moodle-footer').hide();
        });
        */

        // Listen for close button to show all page content.
        $(document).on("click", "#fixy-close", function() {
            $('#page, #moodle-footer').show();

        });

        // Listen for window resize for videos.
        $(window).resize(function(e) {
            resizestamp = new Date().getTime();
            (function(timestamp){
                window.setTimeout(function() {
                    logger ('checking ' + timestamp + ' against ' + resizestamp);
                    if (timestamp == resizestamp) {
                        logger('applying video resize');
                        applyResponsiveVideo();
                    } else {
                        logger('skipping video resize - timestamp has changed from ' + timestamp + ' to ' + resizestamp);
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

    // SL - 24th july 2014 - check we are in a course
    if(window.location.href.indexOf("course/view.php?id") > -1) {
        showPageSectionMod(true);
    }

    // SL - 24th july 2014 - if are looking at the personal menu we need to "fire it up" (flipmode)
    if(window.location.href.indexOf("primary-nav") > -1) {
        updatePersonalMenu($('#js-personal-menu-trigger').data('key'));
    }

    $(window).on('load' , function() {
        // TODO check with guy, unsure this is necessary except for reeeeeefreeeesh/back/fwd...
        if(window.location.href.indexOf("modid") > -1) {
            window.setTimeout(function(){showPageSectionMod(false);}, 100);
        }

        // Make video responsive.
        // Note, if you don't do this on load then FLV media gets wrong size.
        applyResponsiveVideo();
    });

} // end snap init
