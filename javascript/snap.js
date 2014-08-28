/* exported snapInit */

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
    var logger = function(msg,obj){
        if (!loggingenabled){
            return;
        }
        if (console !== null && console.log !== null){
            if (obj){
                console.log(msg,obj);
            } else {
                console.log(msg);
            }
        }
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
                $(this).attr('action', $(this).attr('action') + '#blocks');
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

        // If required, apply #blocks hash to form action - this is so that on submitting form it returns to course
        // page on blocks tab.
        $(formselectors.join(', ')).each(function(){
            // Only apply the blocks hash if a hash is not already present in url.
            var formurl = $(this).attr('action');
            if (formurl.indexOf('#') === -1
                && (formurl.indexOf('/course/view.php') > -1)
                ){
                $(this).attr('action', $(this).attr('action') + '#blocks');
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
        var i;
        // TODO - for 2.7 process search string called too many times?
        var searchString = $("#toc-search-input").val();
        searchString = processSearchString(searchString);

        if(searchString.length === 0) {
            $('#toc-search-results').html('');
            $('#toc-search-input').removeClass('state-visible');
        } else {
            var matches = [];
            for (i = 0; i < dataList.length; i++) {
                var dataItem = dataList[i];
                if(processSearchString($(dataItem).text()).indexOf(searchString) > -1 ) {
                    matches.push(dataItem);
                }
            }
            if(matches.length) {
                $('#toc-search-input').addClass('state-visible');
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
     * Add deadlines, messages async'ly to the personal menu
     *
     * @param key - jsonencoded sesskey
     *
     * @author Stuart Lamour
     */
    var updatePersonalMenu = function(){
        var key = M.cfg.sesskey;

        // primary nav showing so hide the other dom parts
        $('#page, #moodle-footer').hide(0);
        var deadlinesContainer = $('#snap-personal-menu-deadlines');
        if($(deadlinesContainer).length) {
            var deadlines_key = key + "pmDeadlines";
            try {
                // Display old content while waiting, if not too old.
                if(window.sessionStorage[deadlines_key]) {
                    logger("using locally stored deadlines");
                    html = window.sessionStorage[deadlines_key];
                    $(deadlinesContainer).html(html);
                }
                logger("fetching deadlines");
                $.ajax({
                      type: "GET",
                      async:  true,
                      url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_deadlines&contextid=' + M.cfg.context,
                      success: function(data){
                        logger("fetched deadlines");
                        window.sessionStorage[deadlines_key] = data.html;
                        $(deadlinesContainer).html(data.html);
                      }
                });
            } catch(err) {
                sessionStorage.clear();
                logger(err);
                // $(deadlinesContainer).html("");
            }
        } // end deadlines div exists check

        var messagesContainer = $('#snap-personal-menu-messages');
        if($(messagesContainer).length) {
            var messages_key = key + "pmMessages";
            try {
                // Display old content while waiting, if not too old.
                if(window.sessionStorage[messages_key]) {
                    logger("using locally stored messages");
                    html = window.sessionStorage[messages_key];
                    $(messagesContainer).html(html);
                }
                logger("fetching messages");
                $.ajax({
                      type: "GET",
                      async:  true,
                      url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_messages&contextid=' + M.cfg.context,
                      success: function(data){
                        logger("fetched messages");
                        window.sessionStorage[messages_key] = data.html;
                        $(messagesContainer).html(data.html);
                      }
                });
            } catch(err) {
                sessionStorage.clear();
                logger(err);
                // $(messagesContainer).html("");
            }
        } // end messages div exists check
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
        }, 1000);
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
    };

    /**
     * Fat controller for when hashstate/popstate alters
     * Contains the logic and controllers for adding classes for current section &/|| search.
     * @return void
     */
    var showSection = function() {
        // primary use case
        if(onCoursePage()) {
            // check we are not in the moodle single section format
            if(!$('.moodle-single-section-format, .format-folderview').length){
                // reset visible section
                $('.course-content ul li.section').removeClass('state-visible');
                // if the hash is just section, can we skip all this?

                // we know the params at 0 is a section id
                // params will be in the format
                // #section-1&module-7255
                var urlParams = location.hash.split("&"),
                section = urlParams[0],
                mod = urlParams[1] || null;
                // we know that if we have a search modid will be param 1
                if(mod !== null) {
                    $(section).addClass('state-visible').focus();
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
            var visibleChapters = $('.course-content ul li.section').filter(':visible');
            if (!visibleChapters.length) {
                // show chapter 0
                $('#section-0').addClass('state-visible').focus();
            }

            applyResponsiveVideo();
            // add faux :current class to the relevant section in toc
            var currentSectionId = $('.section.state-visible').attr('id');
            // do something special for moodle single page formats
            if($('.moodle-single-section-format').length){
                // if not on section0 use section id as there is only one section
                if(!currentSectionId) {
                    currentSectionId = $('.single-section .section').attr('id');
                }

                // in single section when editing there is no current section
                if(currentSectionId) {
                    // urls are section=x in single section mode
                    currentSectionId = currentSectionId.replace('-','=');
                }
            }
            $('#chapters li').removeClass('current');
            $('#chapters a[href$="' + currentSectionId + '"]').parent('li').addClass('current');
        }
    };

    /**
     * Add listeners.
     *
     * just a wrapper for various snippets that add listeners
     */
    var addListeners = function() {

        var selectors = [
            'body:not(.editing):not(.format-folderview):not(.moodle-single-section-format) .chapters a',
            'body:not(.moodle-single-section-format):not(.format-folderview) .section_footer a',
            'body:not(.moodle-single-section-format):not(.format-folderview) #toc-search-results a'
        ];

        $(document).on('click', selectors.join(', '), function(e) {
            // If we are in blocks, we need to not be.
            if(location.hash === '#blocks') {
                location.hash = 'sections';
            }
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
            logger('hashchange');
            if(newHash !== lastHash){
                if(location.hash === '#primary-nav') {
                    updatePersonalMenu();
                }
                else{
                    $('#page, #moodle-footer').show(0);
                    if(onCoursePage()) {
                        // In folder view we sometimes get here - how?
                        logger('show section', e.target);
                        if($('.moodle-single-section-format, .format-folderview').length){
                            // Check if we are searching for a mod
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
        // When not signed in always show mr-nav?
        if(!$('.notloggedin').length) {
            headroom.init();
        }

        // Listener for toc search.
        var dataList = $("#toc-searchables").find('a').clone(true);
        $('#toc-search-input').keyup(function() {
            tocSearchCourse(dataList);
        });

        $("#toc-search-input").focus(function() {
            // Hide search results.
            $('#toc-search-results').html('');
        });

        // Handle keyboard navigation of search items.
        $("#toc-search-input").keydown(function(e) {
            var keyCode = e.keyCode || e.which;
            if (keyCode === 9) {
                //e.preventDefault();
                // call custom function here
                $(this).addClass('state-tabbed');

                // Register listener for exiting search result.
                $('#toc-search-results a').last().blur(function () {
                    $("#toc-search-input").removeClass('state-visible');
                    $("#toc-search-input").val('');
                    $(this).off('blur'); // unregister listener
                });

            }
        });

        // Listener for exiting search field.
        $('#toc-search-input').blur(function () {
            if ($(this).hasClass('state-tabbed')) {
                // We left on a tab event which means we should now be navigating the search results.
                // so don't close the search results.
                $(this).removeClass('state-tabbed');
                return;
            }
            $(this).removeClass('state-visible');
            $(this).val('');
        });

        // Add toggle class for hide/show activities/resources - additional to moodle adding dim.
        $(document).on("click", '[data-action=hide],[data-action=show]', function() {
             $(this).closest('li.activity').toggleClass('draft');
        });

        // Make cards clickable - data-href for resources.
        $(document).on('click', '.snap-resource[data-href]', function(e){
            // Stash event trigger
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

        // Listener for small screen showing of chapters & appendicies.
        $(document).on("click", '#course-toc a', function() {
            $('#chapters, #appendices').toggleClass('state-visible');
        });

        // Onclick for toggle of state-visible of admin block.
        $(document).on("click", ".settings-button", function() {
            var href = this.getAttribute('href');
            $(href).toggleClass('state-visible');
            e.preventDefault();
        });

        // Listen for close button to show page content.
        $(document).on("click", "#fixy-close", function() {
            $('#page, #moodle-footer').show();

        });

        // Listen for window resize for videos.
        $(window).resize(function() {
            resizestamp = new Date().getTime();
            (function(timestamp){
                window.setTimeout(function() {
                    logger ('checking ' + timestamp + ' against ' + resizestamp);
                    if (timestamp === resizestamp) {
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

    // SL - 19th aug 2014 - check we are in a course
    if(onCoursePage()) {
        showSection();
    }

    // SL - 24th july 2014 - if are looking at the personal menu we need to load data
    if(location.href.indexOf("primary-nav") > -1) {
        updatePersonalMenu();
    }

    // SL - 19th aug 2014 - resposive video and snap search in exceptions.
    $(window).on('load' , function() {
        // Make video responsive.
        // Note, if you don't do this on load then FLV media gets wrong size.
        applyResponsiveVideo();

        // When not in standard snap we need to do some things to enable search - post url load.
        if($('.moodle-single-section-format, .format-folderview').length){
            // Check if we are searching for a mod.
            checkHashScrollToModule();
        }
    });
} // End snap init
