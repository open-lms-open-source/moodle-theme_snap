$(document).ready(snapInit);

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
    var loggingenabled=false;

    /**
     * console.log wrapper - copes with old browsers
     * @param {string} msg
     * @param obj
     */
    var logger = function(msg,obj){
        if (!loggingenabled){
            return;
        }
        if (console!=null && console.log!=null){
            if (obj){
                console.log(msg,obj);
            } else {
                console.log(msg);
            }
        }
    }

    /**
     * Test if admin block exists and show link
     *
     * @author Stuart Lamour
     */
    var testAdminBlock = function(){

        // GT MOD 2014-04-30 - do not get settings block using #inst5 - use class instead
        var settingsblock=$('.block.block_settings');
        if (!settingsblock.length){
            return;
        }
        var settingsBlockHref= '#'+$(settingsblock).attr('id');
        // get settings block id
        // add as href
        
        
        // GT MOD 2014-04-30 - made close button use lang strings
        // if I replace href="#inst5" with something else then close doesn't work
        $(settingsblock).prepend("<a class='settings-button  snap-action-icon'><i class='icon icon-arrows-01'></i><small>"+M.util.get_string('close', 'theme_snap')+"</small></a>");
        $('.settings-button').css('display','inline-block').attr('href', settingsBlockHref);
    };

    /**
     * setup settings button
     *
     * @author Stuart Lamour
     */
    var setupSettingsButton = function(){
        $(document).on("click", ".settings-button", function(e) {
            var href = this.getAttribute('href');
            $(href).toggleClass('state-visible');
            e.preventDefault();
        });
    };

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
        if (ta.length<2){
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
        var hbparams=getHashBangParams(window.location.href);
        if (!hbparams || !hbparams.modid){
            showSection();
        } else {
            focusModule(window.location.href, animscroll);
        }
    };


    /**
     * show and focus section
     *
     * @author Stuart Lamour
     */
    var showSection = function() {
        // check we are in a course
        if(window.location.href.indexOf("course/view.php?id") > -1) {
            $('.course-content ul li.section').removeClass('state-visible');


            // GT MOD 2014-04-30 - we can't do the following, it won't work if we have a module in the section too
            // $(window.location.hash).addClass('state-visible').focus();

            var hbparams = getHashBangParams(window.location.href);
            // make sure params suit our needs
            if (hbparams.section!=null){
                // make desired section visible
                logger('show section '+hbparams.section);
                $('#section-'+hbparams.section).addClass('state-visible').focus();

                if (hbparams.modid==null){
                    /*
                    // scroll to top of section
                    var navheight = $('#mr-nav').outerHeight();
                    var scrtop = $('#section-'+hbparams.section).offset().top - navheight;
                    $('html, body').animate({
                        scrollTop: scrtop
                    }, 100);
                    */

                    // change of behaviour, just scroll to top of page with no animation
                    window.scrollTo(0,0);
                }
            }

            var visibleChapters = $('.course-content ul li.section').filter(':visible');
            if (visibleChapters.length < 1) {
                // show chapter 0
                $('#section-0').addClass('state-visible').focus();
            }

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
                if(containsSearchString(processSearchString($(dataItem).text()),searchString)) {
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
     * focus Module on page
     *
     * @author Guy Thomas
     * @param href
     */
    var focusModule = function(href, animscroll){

        // hide search box in case we have clicked a module link (can also be called by page load)
        $('#toc-search-input').removeClass('state-visible');
        var ta=href.split('#');
        if (ta.length<1){
            return; // invalid hashbang
        }

        var hbparams=getHashBangParams(href);
        if (!hbparams.section || !hbparams.modid){
            // error - no sction or mod
            return;
        }

        // make sure we are on the sections tab (can't navigate to mods if on appendices)
        location.hash='sections';

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
        $('#'+targsect).addClass('state-visible');

        // scroll to module
        var targmod = 'module-' + hbparams.modid;
        // http://stackoverflow.com/questions/6677035/jquery-scroll-to-element
        var mod = $("#" + targmod);
        var navheight = $('#mr-nav').outerHeight();
        var scrtop = mod.offset().top - navheight;

        if (animscroll){
            $('html, body').animate({
                scrollTop: scrtop
            }, 1000);
        } else {
            window.scrollTo(0, scrtop);
        }

        var searchpin = $("#searchpin");
        if (!searchpin.length){
            var searchpin = $('<i id="searchpin" class="icon-socialmedia-25"></i>');
        }

        $(mod).find('.activityinstance').prepend(searchpin);
        //$(searchpin).css('margin-left','-1em'); // removed this to fix INT-5997

        // reset search value
        $('#toc-search-input').val('');

        // hide search results
        $("#toc-search-results").html('');
    };


    /**
     * add listeners
     *
     * just a wrapper for various snippets that add listeners
     */
    var addListeners=function(){

        // listener for toc search //
        var dataList = $("#toc-searchables").find('a');
        $('#toc-search-input').keyup(function (e) {
            tocSearchCourse(dataList);
        });

        $("#toc-search-input").focus(function(e){
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



        // listener for clicking serach result //
        $(document).on("click", "#toc-search-results a", function(e){
            var href= this.getAttribute('href');
            focusModule(href, true);
            e.preventDefault();
        });

        // listen for popstate for back/fwd buttons //
        $(window).bind("popstate", function() {
            showSection();
        });

        // listen for click on chapter links where chapter not being edited //
        $(document).on("click", 'body:not(.editing) .chapters a', function(e){
            var href= this.getAttribute('href');
            $('.course-content ul li.section').removeClass('state-visible');
            // for mobile remove state on click
            $('#chapters, #appendices').removeClass('state-visible');
            $(href).addClass('state-visible').focus();
            if(history.pushState) {
                history.pushState(null, null, href);
            } else {
                location.hash = href;
            }
            e.preventDefault();
        });
        
        // listener for small screen showing of chapters & appendicies
        $(document).on("click", '#course-toc div[role="menubar"] a', function(e){
        	$('#chapters, #appendices').addClass('state-visible');
        });
    };



    // GO !!!!
    addListeners();
    testAdminBlock();
    setupSettingsButton();
    showPageSectionMod(true);

    $(window).on('load' , function() {
        // note we need to call showPageSectionMod again on window load or the page will jump to the top of the page!
        // this does work, however is there a more elegant fix?
        showPageSectionMod(false);
    });


}
