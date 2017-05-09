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
 * @copyright Copyright (c) 2017 University of Portland
 * @author Jerome Mouneyrac - jerome@mouneyrac.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Category filter of the personal menu.
 */
define(['jquery', 'core/log', 'core/ajax', 'core/notification'],
    function($, log, ajax, notification) {

        /**
         * Personal Menu (courses menu).
         * @constructor
         */
        var CategoryFilter = function() {

            var self = this;

            this.viewingMode = function(value) {
                ajax.call([
                    {
                        methodname: 'theme_snap_user_viewing_mode',
                        args: {value: value},
                        done: function(response) {
                            if (value == "get") {
                                if (response.value == 'categories') {
                                    $('.courseinfo').css('display', 'none');
                                    catfilter_callback();
                                }
                            }
                        },
                        fail: function(response) {
                            notification.exception(response);
                        }
                    }
                ], true, true);
            };


            var doAjax = function(action, categoryid) {
                ajax.call([
                    {
                        methodname: 'theme_snap_user_categories',
                        args: {action: action, categoryid: categoryid},
                        done: function(response) {
                            
                            // Retrieve all categories.
                            var menucategories = {};
                            $(".pushy-content ul li").each(function(index) {
                                menucategories[$(this).find('input').attr('selected-categoryid')] = $(this).attr('aria-checked');
                            });

                            var categoriestitle = '';
                            var categories = JSON.parse(response.listing);

                              
                            // only display the selected categories
                            var firstcategory = true;

                            // Display the checked categories.
                            categories.forEach(
                                function(item, index) {
                                    
                                    // If the categories was previously unchecked, then declare it as checked.
                                    if (menucategories[item] === 'false') {
                                        $("[data-categoryid="+item+"]").css('display', 'inline');
                                        // $(".snap_pm_menu_mycategory[data-categoryid="+item+"]").addClass('menu_mycategory_selected');
                                        $("[selected-categoryid="+item+"]").prop('checked', true);
                                        $("#menu_mycategory_li_"+item).attr('aria-checked', 'true');
                                    }
                                    
                                    // Add the category name to the menu categories title.
                                    if (!firstcategory) {
                                        categoriestitle = categoriestitle + ', ';
                                    } else {
                                        firstcategory = false;
                                    }
                                    var categorymenuoption = '#menu_mycategory_'+item;
                                    categoriestitle = categoriestitle + $(categorymenuoption).attr('value');
                                }
                            );
                            
                            // Do not display unchecked categories.
                            for (var menucategoryid in menucategories) {
                                
                                if (categories.indexOf(parseInt(menucategoryid)) == -1) {
                                    // hide the category courses.
                                    $("[data-categoryid="+menucategoryid+"]").css('display', 'none');
                                    
                                    // uncheck the menu category only if it was previously checked.
                                    if (menucategories[menucategoryid] === 'true' ) {
                                        $("[selected-categoryid="+menucategoryid+"]").prop('checked', false);
                                        $("#menu_mycategory_li_"+menucategoryid).attr('aria-checked', 'false');
                                    }
                                }
                            }
                            
                            // If no categories are selected then automatically open the menu if it is not already open.    
                            if (categories.length == 0) {
                                // and open category selector
                               if($('.site-overlay').css('display') == 'none') {
                                   $('.snap_pm_editcat').click();
                               }
                            }     
                            
                            h2.text('Categories');

                            if (categoriestitle == '') {
                                categoriestitle = 'No categories selected';
                            }

                            $('.snap_pm_user_category_list').text(categoriestitle);

                            $('.snap_pm_editcat').css('visibility', 'visible');

                        },
                        fail: function(response) {
                            notification.exception(response);
                        }
                    }
                ], true, true);
            };

            var h2 = $('section#fixy-my-courses div h2');

            var menu_mycategory_li_callback = function(element) {

                var selectmenuoption = element.find( 'input' );

                if (selectmenuoption.is(":checked")) {

                    // TODO: this is a ugly fix because but actually when clicking on the category, this function is called twice!
                    //selectmenuoption.prop('checked', false);

                    doAjax('remove', selectmenuoption.attr('selected-categoryid'));
                } else {

                    // TODO: this is a ugly fix because but actually when clicking on the category, this function is called twice!
                    //selectmenuoption.prop('checked', true);

                    doAjax('add', selectmenuoption.attr('selected-categoryid'));
                }
            };

            var menu_mycategory_li_callback_this = function(event) {
                
                menu_mycategory_li_callback($(this));
                
                // Forbid the otherthing to happen (like triggering a second click on the element,
                // as the element attr changed with pushy adding classes to the element)
                event.stopPropagation();
                event.preventDefault();
            }

            $('.snap_pm_menu_mycategory_li').click(
                menu_mycategory_li_callback_this
            );

            $(".snap_pm_menu_mycategory_li").keypress(function() {
                if (event.which == 13 || event.which == 32) menu_mycategory_li_callback($(this));
            });

            h2.text('All courses');
            var allcourses_callback = function(){

                h2.text('All Courses');
                $('.snap_pm_editcat').css('visibility', 'hidden');
                $('.snap_pm_courses_section_title .snap_pm_category_filter_title').css('display', 'none');

                $(".courseinfo").css('display', 'inline-block');

                // TODO: use the Moodle string.
                h2.text('All courses');
                $(".snap_pm_user_category_list").css('display', 'none');
                $(".snap_pm_catfilter").removeClass('theme_snap_pm_active_link');
                $(".snap_pm_allcourses").addClass('theme_snap_pm_active_link');

                // set user preferences for the viewing mode.
                self.viewingMode('all');
            };
            $(".snap_pm_allcourses").click(
                allcourses_callback
            );
            $(".snap_pm_allcourses").keypress(function() {
                if (event.which == 13 || event.which == 32) allcourses_callback();
            });

            var catfilter_callback = function(){
                h2.text('Categories');

                doAjax('listing');

                $(".snap_pm_catfilter").addClass('theme_snap_pm_active_link');
                $(".snap_pm_user_category_list").css('display', 'inline-block');
                $(".snap_pm_allcourses").removeClass('theme_snap_pm_active_link');
                $('.snap_pm_courses_section_title .snap_pm_category_filter_title').css('display', 'block');

                // set user preferences for the viewing mode.
                self.viewingMode('categories');
            };

            $(".snap_pm_catfilter").click(catfilter_callback);

            $(".snap_pm_catfilter").keypress(function() {
                if (event.which == 13 || event.which == 32) catfilter_callback();
            });


            var editcat_callback = function(){
                // set the button as expanded.
                if($(".snap_pm_editcat").attr('aria-expanded') == 'true') {
                    $(".snap_pm_editcat").attr('aria-expanded', 'false');
                } else {
                    $(".snap_pm_editcat").attr('aria-expanded', 'true');
                }
            };
            $(".snap_pm_editcat").click(editcat_callback);
            $(".snap_pm_editcat").keypress(function() {
                //somehow do not need to use keypress as keypress on the pushy button triggers a click! Must be inside pushy code.
                //if (event.which == 13 || event.which == 32) editcat_callback();
            });

            // $(".snap_pm_editcat").keydown(function() {
            //
            //     // Do not exit the change category menu if menu is expanded.
            //     if (event.which == 9) {
            //         if($(".snap_pm_editcat").attr('aria-expanded') == 'true') {
            //             $('.pushy-content').focus();
            //         }
            //     }
            // });

            // Do not exist menu if expanded
            $(".pushy-content ul li:last-child").keydown(function() {
                // Do not exit the change category menu if menu is expanded.
                if (event.which == 9) {

                    if($(".snap_pm_editcat").attr('aria-expanded') == 'true') {
                        $('.pushy-content').focus();
                    }
                }
            });

            $("body").keydown(function() {
                if (event.which == 27) {
                    if($(".snap_pm_editcat").attr('aria-expanded') == 'true') {
                        $(".snap_pm_editcat").attr('aria-expanded', 'false')
                    }
                };
            });

            $(".site-overlay").click(
                function() {
                    $(".snap_pm_editcat").attr('aria-expanded', 'false')
                }
            );

            // Close off-canvas menu when pressing on the button.
            $(".pushy-close-icon").click(
                function() {
                    $('.site-overlay').click();
                }
            );

            // initialise the viewing mode if logged in.
            // TODO: better use the Moodle loggedin function somehow...
            if ($('#fixy-logout').length ) {
                self.viewingMode("get");

                $('.snap_pm_courses_section_title .snap_pm_category_filter_title').css('display', 'none');
            }

        }

        return new CategoryFilter();

    }
);