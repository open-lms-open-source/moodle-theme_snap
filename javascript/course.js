/**
 * Created by gthomas2 on 6/18/15.
 */
M.theme_snap = M.theme_snap || {
    courseid : false
};
M.theme_snap.course = {
    init : function (Y, movenoticehtml) {

        /**
         * Items being moved - actual dom elements.
         * @type {array}
         */
        var movingobjects = [];

        /**
         * Item being moved - actual dom element.
         * @type {object}
         */
        var movingobject;

        /**
         * @type {boolean}
         */
        var ajaxing = false;

        /**
         * @type {*|jQuery|HTMLElement}
         */
        var snap_move_message = $('#snap-move-message');

        /**
         * Logging function.
         *
         * @param {string} msg
         * @param {object} object
         */
        var log = function (msg, object) {
            if (!M.cfg.developerdebug) {
                return;
            }
            if (object) {
                console.log(msg, object);
            } else {
                console.log(msg)
            }
        }

        /**
         * General move request
         *
         * @param {object}   params
         * @param {object}   target
         * @param {function} onsuccess
         * @param {bool}     finaltime
         */
        var ajax_req_move_general = function (params, target, onsuccess, finalitem) {
            if (ajaxing) {
                // Request already made.
                log('Skipping ajax request, one already in progress');
                return;
            }

            // Add spinner.
            add_ajax_loading($('#snap-move-message .snap-move-message-title'));

            // Set common params.
            params.sesskey = M.cfg.sesskey;
            params.courseId = M.theme_snap.courseid;
            params.field = 'move';

            log('Making course/rest.php request', params);
            var req = $.ajax({
                type: "POST",
                async: true,
                data: params,
                url: M.cfg.wwwroot + M.theme_snap.courseconfig.ajaxurl
            });
            req.done(function (data) {
                if (data.error) {
                    log('Ajax request fail');
                    move_failed();
                } else {
                    log('Ajax request successful');
                    if (onsuccess) {
                        onsuccess();
                    }
                    if (finalitem) {
                        stop_moving();
                    }
                }
            });
            req.fail(function () {
                move_failed();
            });

            if (finalitem) {
                req.complete(function () {
                    ajaxing = false;
                    $('#snap-move-message-title .spinner-three-quarters').remove();
                });
            }
        };

        /**
         * Add ajax loading to container
         * @param {object} container
         * @param {bool}   dark
         */
        var add_ajax_loading = function(container, dark){
            if ($(container).find('.loadingstat').length === 0) {
                var darkclass = dark ? ' spinner-dark' : '';
                $(container).append('<div class="loadingstat spinner-three-quarters' + darkclass +
                '">' + Y.Escape.html(M.util.get_string('loading', 'theme_snap')) + '</div>');
            }
        }

        /**
         * Show or hide an asset
         *
         * @param {object} e
         * @param {object} el
         * @param {bool}   show
         */
        var asset_show_hide = function(e, el, show) {
            e.preventDefault();
            var courserest = M.cfg.wwwroot+'/course/rest.php';
            var parent = $($(el).parents('.snap-asset')[0]);

            var id = parent.attr('id').replace('module-', '');

            add_ajax_loading($(parent).find('.snap-meta'), true);

            var courseid = M.theme_snap.courseid;

            $.ajax({
                type: "POST",
                async:  true,
                url:  courserest,
                dataType: 'html',
                complete : function() {
                    parent.find('.snap-meta .loadingstat').remove();
                },
                error : function(xhr,status,error) {
                    // TODO - localise.
                    alert('Failed to hide/show asset');
                },
                success : function(data){
                    if (show) {
                        parent.removeClass('draft');
                    } else {
                        parent.addClass('draft');
                    }
                },
                data: {
                    id : id,
                    'class' : 'resource',
                    field : 'visible',
                    sesskey : M.cfg.sesskey,
                    value : show ? 1 : 0,
                    courseId : courseid
                }
            });
        };

        /**
         * Ajax request to move asset to target.
         * @param {object} target
         */
        var ajax_req_move_asset = function (target) {
            var params = {};

            log('Move objects', movingobjects);

            // Prepare request parameters
            params['class'] = 'resource';

            update_moving_message();

            movingobject = movingobjects.shift();

            params.id = Number(movingobject.id.replace('module-', ''));

            if (target && !$(target).hasClass('snap-drop')) {
                params.beforeId = Number($(target)[0].id.replace('module-', ''));
            } else {
                params.beforeId = 0;
            }

            if (document.body.id === "page-site-index") {
                params.sectionId = 1;
            } else {
                if (target) {
                    params.sectionId = Number($(target).parents('li.section.main')[0].id.replace('section-', ''));
                } else {
                    params.sectionId = Number($(movingobject).parents('li.section.main')[0].id.replace('section-', ''));
                }
            }

            if (movingobjects.length > 0) {
                ajax_req_move_general(params, target, function () {
                    $(target).before($(movingobject));
                    // recurse
                    ajax_req_move_asset (target);
                }, false);
            } else {
                ajax_req_move_general(params, target, function () {
                    $(target).before($(movingobject));
                }, true);
            }

        }

        /**
         * Move fail - sad face :(.
         */
        var move_failed = function () {
            $('.snap-move-notice').addClass('movefail');
            $('.snap-move-notice .three-quarters').remove();
            var actname = $(movingobject).find('.instancename').html();

            $('#snap-move-message h5').html(M.util.get_string('movefailed', 'theme_snap', actname));
            // Stop moving in 2 seconds so that the user has time to see the failed moving notice.
            window.setTimeout(function () {
                // Don't pass in target, we want to abort the move!
                stop_moving(false);
            }, 2000);
        };

        /**
         * Listen for edit action clicks, hide, show, duplicate, etc..
         */
        var asset_edit_listeners = function() {
            $(document).on('click', '.snap-asset-actions .js_snap_hide', function(e) {
                asset_show_hide(e, this, false);
            });

            $(document).on('click', '.snap-asset-actions .js_snap_show', function(e) {
                asset_show_hide(e, this, true);
            });

            $(document).on('click', '.snap-asset-actions .js_snap_duplicate', function(e) {
                e.preventDefault();
                var parent = $($(this).parents('.snap-asset')[0]);
                var id = parent.attr('id').replace('module-', '');
                add_ajax_loading($(parent).find('.snap-meta'), true);

                var courseid = M.theme_snap.courseid;

                var courserest = M.cfg.wwwroot+'/course/rest.php';

                $.ajax({
                    type: "POST",
                    async:  true,
                    url:  courserest,
                    dataType: 'json',
                    complete : function() {
                        parent.find('.snap-meta .loadingstat').remove();
                    },
                    error : function(xhr,status,error) {
                        // TODO - localise.
                        alert('Failed to duplicate');
                    },
                    success : function(data){
                        $(data.fullcontent).insertAfter(parent);
                    },
                    data: {
                        'class' : 'resource',
                        field : 'duplicate',
                        id : id,
                        sr : 0,
                        sesskey : M.cfg.sesskey,
                        courseId : courseid
                    }
                });
            });
        };

        /**
         * When section move link is clicked, get the data we need and start the move.
         */
        var move_section_listener = function() {
            // Listen clicks on move links.
            $("#region-main").on('click', '.snap-section-editing.actions .snap-move', function(e) {
                e.stopPropagation();
                e.preventDefault();

                $('body').addClass('snap-move-inprogress');

                // Moving a section.
                var sectionid = $(this).data("id");
                log('Section is', sectionid);
                var section = $('#section-' + sectionid);
                var sectionname = section.find('.sectionname').text();

                log('Moving this section', sectionname);
                movingobjects = [section];

                // This should never happen, but just in case...
                $('.section-moving').removeClass('section-moving');
                section.addClass('section-moving');
                $('a[href$=#section-' + sectionid + ']').parent('li').addClass('section-moving');
                $('body').addClass('snap-move-section');

                var title = M.util.get_string('moving', 'theme_snap', sectionname);
                snap_move_message.find('.snap-move-message-title').html(title);
                snap_move_message.focus();

                $('.section-drop').each(function(){
                    var sectiondropmsg = M.util.get_string('movingdropsectionhelp', 'theme_snap',
                        {moving:sectionname, before:$(this).data('title')}
                    );
                    $(this).html(sectiondropmsg);
                });

                $('#snap-move-message p.sr-only').html(
                    M.util.get_string('movingstartedhelp', 'theme_snap', sectionname)
                );
            });
        };

        /**
         * Add drop zones at the end of sections.
         */
         var add_after_drops = function() {
             if (document.body.id === "page-site-index") {
                 $('#region-main .sitetopic ul.section').append('<li class="snap-drop asset-drop"><div class="asset-wrapper"><a href="#">' + M.util.get_string('movehere', 'theme_snap') + '</a></div></li>');
             } else {
                 $('li.section .content ul.section').append('<li class="snap-drop asset-drop"><div class="asset-wrapper"><a href="#">' + M.util.get_string('movehere', 'theme_snap') + '</a></div></li>');
             }
         }

        /**
         * Update moving message.
         */
        var update_moving_message = function() {
            if (movingobjects.length === 1) {
                var assetname = $(movingobjects[0]).find('.snap-asset-link .instancename').html();
                assetname = assetname || M.util.get_string('modulename', 'mod_label');
                var title = M.util.get_string('moving', 'theme_snap', assetname);
                snap_move_message.find('.snap-move-message-title').html(title);
            } else {
                snap_move_message.find('.snap-move-message-title').html(
                    M.util.get_string('movingcount', 'theme_snap', movingobjects.length)
                );
            }
            snap_move_message.focus();
        }

        /**
         * Remove moving object from moving objects array.
         * @param {object} obj
         */
        var remove_moving_object = function(obj) {
            var index = movingobjects.indexOf(obj);
            if (index > -1) {
                movingobjects.splice(index, 1);
            }
            update_moving_message();
        };

        /**
         * Add listener for move checkbox.
         */
        var asett_move_listener = function() {
            $("#region-main").on('change', '.js-snap-asset-move', function(e) {
                e.stopPropagation();

                var asset = $(this).parents('.snap-asset')[0];

                // Make sure after drop is at the end of section.
                var section = $(asset).parents('ul.section')[0];
                var afterdrop = $(section).find('li.snap-drop.asset-drop');
                $(section).append(afterdrop);

                if (movingobjects.length === 0) {
                    // Moving asset - activity or resource.
                    // Initiate move.
                    var assetname = $(asset).find('.snap-asset-link .instancename').html();

                    log('Moving this asset', assetname);

                    var classes = $(asset).attr('class');
                    var regex = /(?=snap-mime)([a-z0-9\-]*)/;
                    var assetclasses = regex.exec(classes);
                    var classes = '';
                    if (assetclasses) {
                        classes = assetclasses.join(' ');
                    }
                    log('Moving this class', classes);
                    $(asset).addClass('asset-moving');
                    $(asset).find('.js-snap-asset-move').prop('checked', 'checked');

                    $('body').addClass('snap-move-inprogress');
                    $('body').addClass('snap-move-asset');
                }

                if ($(this).prop('checked')) {
                    // Add asset to moving array.
                    movingobjects.push(asset);
                    $(asset).addClass('asset-moving');
                } else {
                    // Remove from moving array.
                    remove_moving_object(asset);
                    // Remove moving class
                    $(asset).removeClass('asset-moving');
                    if (movingobjects.length === 0) {
                        // Nothing is ticked for moving, cancel the move.
                        stop_moving();
                    }
                }
                update_moving_message();
            });
        }

        /**
         * When an asset or drop zone is clicked, execute move.
         */
        var move_place_listener = function() {
            $(document).on('click', '.snap-move-note, .snap-drop', function (e) {
                log('Snap drop clicked', e);
                if (movingobjects) {
                    e.stopPropagation();
                    e.preventDefault();
                    if ($('body').hasClass('snap-move-section')) {
                        ajax_req_move_section(this);
                    } else {
                        var target;
                        if ($(this).hasClass('snap-drop')) {
                            target = this;
                        } else {
                            target = $(this).closest('.snap-asset');
                        }
                        ajax_req_move_asset(target);
                    }
                }
            });
        };

        /**
         * Ajax request to move section to target.
         * @param target
         */
        var ajax_req_move_section = function (dropzone) {
            var targetsection = $(dropzone).data('id');
            var target = $('#section-' + targetsection);
            var currentsection = $(movingobjects[0]).attr('id').replace('section-', '');

            if (currentsection < targetsection) {
                targetsection -=1;
            }

            var params = {
                class: 'section',
                id: currentsection,
                value: targetsection
            };

            ajax_req_move_general(params, target, function () {
                var targetsection = params.value;

                // TODO - INT-8670 - ok, here we can do a page redirect / reload but should probably ajax if we have time!
                location.href = location.href.replace(location.hash, '')+'#section-'+targetsection;
                location.reload(true);
            }, true);
        };

        /**
         * Moving has stopped, clean up.
         */
        var stop_moving = function () {
            $('body').removeClass('snap-move-inprogress');
            $('body').removeClass('snap-move-section');
            $('body').removeClass('snap-move-asset');
            $('.section-moving').removeClass('section-moving');
            $('.asset-moving').removeClass('asset-moving');
            $('.js-snap-asset-move').removeAttr('checked');
            movingobjects = [];
        }

        /**
         * When cancel button is pressed in footer, cancel move.
         */
        var move_cancel_listener = function() {
            $(".snap-move-cancel").click(
                function (e) {
                    e.preventDefault();
                    stop_moving();
                }
            );
        };

        /**
         * Override core functions.
         */
        var override_core = function() {
            // Check M.course exists (doesn't exist in social format).
            if (M.course && M.course.resource_toolbox) {
                M.course.resource_toolbox.handle_resource_dim = function (button, activity, action) {
                    return (action === 'hide') ? 0 : 1;
                }
            }
        }

        /**
         * Add listeners.
         */
        var add_listeners = function() {
            move_section_listener();
            asett_move_listener();
            move_cancel_listener();
            move_place_listener();
            asset_edit_listeners();
            add_after_drops();
            $('body').addClass('snap-course-listening');
        }

        /**
         * Initialise script.
         */
        var initialise = function() {
            // If the move notice html was not output to the dom via php, then we need to add it here via js.
            // This is necessary for the front page which does not have a renderer that we can override.
            if (movenoticehtml) {
                $('#region-main').append(movenoticehtml);
                snap_move_message = $('#snap-move-message');
            }

            // Add listeners.
            add_listeners();

            // Override core functions
            override_core();
        };
        initialise();
    }
}
