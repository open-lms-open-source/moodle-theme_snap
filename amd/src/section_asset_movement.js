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

define(['jquery', 'core/log', 'core/templates', 'core/notification'], function($, log, templates, notification) {

    return {
        init: function() {

            /**
             * Items being moved - actual dom elements.
             * @type {array}
             */
            var movingObjects = [];

            /**
             * Item being moved - actual dom element.
             * @type {object}
             */
            var movingObject;

            /**
             * @type {boolean}
             */
            var ajaxing = false;

            /**
             * @type {*|jQuery|HTMLElement}
             */
            var snapMoveMessage = $('#snap-move-message');

            /**
             * Moving has stopped, clean up.
             */
            var stopMoving = function() {
                $('body').removeClass('snap-move-inprogress');
                $('body').removeClass('snap-move-section');
                $('body').removeClass('snap-move-asset');
                $('.section-moving').removeClass('section-moving');
                $('.asset-moving').removeClass('asset-moving');
                $('.js-snap-asset-move').removeAttr('checked');
                movingObjects = [];
            };

            /**
             * Move fail - sad face :(.
             */
            var moveFailed = function() {
                $('.snap-move-notice').addClass('movefail');
                $('.snap-move-notice .three-quarters').remove();
                var actname = $(movingObject).find('.instancename').html();

                $('#snap-move-message h5').html(M.util.get_string('movefailed', 'theme_snap', actname));
                // Stop moving in 2 seconds so that the user has time to see the failed moving notice.
                window.setTimeout(function() {
                    // Don't pass in target, we want to abort the move!
                    stopMoving(false);
                }, 2000);
            };

            /**
             * Update moving message.
             */
            var updateMovingMessage = function() {
                if (movingObjects.length === 1) {
                    var assetname = $(movingObjects[0]).find('.snap-asset-link .instancename').html();
                    assetname = assetname || M.str.label.pluginname;
                    var title = M.util.get_string('moving', 'theme_snap', assetname);
                    snapMoveMessage.find('.snap-move-message-title').html(title);
                } else {
                    snapMoveMessage.find('.snap-move-message-title').html(
                        M.util.get_string('movingcount', 'theme_snap', movingObjects.length)
                    );
                }
                snapMoveMessage.focus();
            };

            /**
             * Remove moving object from moving objects array.
             * @param {object} obj
             */
            var removeMovingObject = function(obj) {
                var index = movingObjects.indexOf(obj);
                if (index > -1) {
                    movingObjects.splice(index, 1);
                }
                updateMovingMessage();
            };

            /**
             * Add ajax loading to container
             * @param {object} container
             * @param {bool}   dark
             */
            var addAjaxLoading = function(container, dark) {
                if ($(container).find('.loadingstat').length === 0) {
                    var darkclass = dark ? ' spinner-dark' : '';
                    $(container).append('<div class="loadingstat spinner-three-quarters' + darkclass +
                        '">' + M.util.get_string('loading', 'theme_snap') + '</div>');
                }
            };

            /**
             * General move request
             *
             * @param {object}   params
             * @param {object}   target
             * @param {function} onsuccess
             * @param {bool}     finaltime
             */
            var ajaxReqMoveGeneral = function(params, target, onSuccess, finalItem) {
                if (ajaxing) {
                    // Request already made.
                    log.debug('Skipping ajax request, one already in progress');
                    return;
                }

                // Add spinner.
                addAjaxLoading($('#snap-move-message .snap-move-message-title'));

                // Set common params.
                params.sesskey = M.cfg.sesskey;
                params.courseId = M.theme_snap.courseid;
                params.field = 'move';

                log.debug('Making course/rest.php request', params);
                var req = $.ajax({
                    type: "POST",
                    async: true,
                    data: params,
                    url: M.cfg.wwwroot + M.theme_snap.courseconfig.ajaxurl
                });
                req.done(function(data) {
                    if (data.error) {
                        log.debug('Ajax request fail');
                        moveFailed();
                    } else {
                        log.debug('Ajax request successful');
                        if (onSuccess) {
                            onSuccess();
                        }
                        if (finalItem) {
                            stopMoving();
                        }
                    }
                });
                req.fail(function() {
                    moveFailed();
                });

                if (finalItem) {
                    req.complete(function() {
                        ajaxing = false;
                        $('#snap-move-message-title .spinner-three-quarters').remove();
                    });
                }
            };

            /**
             * Show or hide an asset
             *
             * @param {object} e
             * @param {object} el
             * @param {bool}   show
             */
            var assetShowHide = function(e, el, show) {
                e.preventDefault();
                var courserest = M.cfg.wwwroot + '/course/rest.php';
                var parent = $($(el).parents('.snap-asset')[0]);

                var id = parent.attr('id').replace('module-', '');

                addAjaxLoading($(parent).find('.snap-meta'), true);

                var courseid = M.theme_snap.courseid;

                $.ajax({
                    type: "POST",
                    async: true,
                    url: courserest,
                    dataType: 'html',
                    complete: function() {
                        parent.find('.snap-meta .loadingstat').remove();
                    },
                    error: function() {
                        var message = M.util.get_string('error:failedtochangeassetvisibility', 'theme_snap');
                        notification.alert(null, message, M.util.get_string('ok'));
                    },
                    success: function() {
                        if (show) {
                            parent.removeClass('draft');
                        } else {
                            parent.addClass('draft');
                        }
                    },
                    data: {
                        id: id,
                        'class': 'resource',
                        field: 'visible',
                        sesskey: M.cfg.sesskey,
                        value: show ? 1 : 0,
                        courseId: courseid
                    }
                });
            };

            /**
             * Ajax request to move asset to target.
             * @param {object} target
             */
            var ajaxReqMoveAsset = function(target) {
                var params = {};

                log.debug('Move objects', movingObjects);

                // Prepare request parameters
                params['class'] = 'resource';

                updateMovingMessage();

                movingObject = movingObjects.shift();

                params.id = Number(movingObject.id.replace('module-', ''));

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
                        params.sectionId = Number($(movingObject).parents('li.section.main')[0].id.replace('section-', ''));
                    }
                }

                if (movingObjects.length > 0) {
                    ajaxReqMoveGeneral(params, target, function() {
                        $(target).before($(movingObject));
                        // recurse
                        ajaxReqMoveAsset(target);
                    }, false);
                } else {
                    ajaxReqMoveGeneral(params, target, function() {
                        $(target).before($(movingObject));
                    }, true);
                }

            };

            /**
             * Ajax request to move section to target.
             * @param target
             */
            var ajaxReqMoveSection = function(dropzone) {
                var targetsection = $(dropzone).data('id');
                var target = $('#section-' + targetsection);
                var currentsection = $(movingObjects[0]).attr('id').replace('section-', '');

                if (currentsection < targetsection) {
                    targetsection -= 1;
                }

                var params = {
                    class: 'section',
                    id: currentsection,
                    value: targetsection
                };

                ajaxReqMoveGeneral(params, target, function() {
                    var targetsection = params.value;

                    // TODO - INT-8670 - ok, here we can do a page redirect / reload but should probably ajax if we have time!
                    location.href = location.href.replace(location.hash, '') + '#section-' + targetsection;
                    location.reload(true);
                }, true);
            };

            /**
             * Listen for edit action clicks, hide, show, duplicate, etc..
             */
            var assetEditListeners = function() {
                $(document).on('click', '.snap-asset-actions .js_snap_hide', function(e) {
                    assetShowHide(e, this, false);
                });

                $(document).on('click', '.snap-asset-actions .js_snap_show', function(e) {
                    assetShowHide(e, this, true);
                });

                $(document).on('click', '.snap-asset-actions .js_snap_duplicate', function(e) {
                    e.preventDefault();
                    var parent = $($(this).parents('.snap-asset')[0]);
                    var id = parent.attr('id').replace('module-', '');
                    addAjaxLoading($(parent).find('.snap-meta'), true);

                    var courseid = M.theme_snap.courseid;

                    var courserest = M.cfg.wwwroot + '/course/rest.php';

                    $.ajax({
                        type: "POST",
                        async: true,
                        url: courserest,
                        dataType: 'json',
                        complete: function() {
                            parent.find('.snap-meta .loadingstat').remove();
                        },
                        error: function() {
                            var message = M.util.get_string('error:failedtoduplicateasset', 'theme_snap');
                            notification.alert(null, message, M.util.get_string('ok'));
                        },
                        success: function(data) {
                            $(data.fullcontent).insertAfter(parent);
                        },
                        data: {
                            'class': 'resource',
                            field: 'duplicate',
                            id: id,
                            sr: 0,
                            sesskey: M.cfg.sesskey,
                            courseId: courseid
                        }
                    });
                });
            };

            /**
             * When section move link is clicked, get the data we need and start the move.
             */
            var moveSectionListener = function() {
                // Listen clicks on move links.
                $("#region-main").on('click', '.snap-section-editing.actions .snap-move', function(e) {
                    e.stopPropagation();
                    e.preventDefault();

                    $('body').addClass('snap-move-inprogress');

                    // Moving a section.
                    var sectionid = $(this).data("id");
                    log.debug('Section is', sectionid);
                    var section = $('#section-' + sectionid);
                    var sectionname = section.find('.sectionname').text();

                    log.debug('Moving this section', sectionname);
                    movingObjects = [section];

                    // This should never happen, but just in case...
                    $('.section-moving').removeClass('section-moving');
                    section.addClass('section-moving');
                    $('a[href$=#section-' + sectionid + ']').parent('li').addClass('section-moving');
                    $('body').addClass('snap-move-section');

                    var title = M.util.get_string('moving', 'theme_snap', sectionname);
                    snapMoveMessage.find('.snap-move-message-title').html(title);
                    snapMoveMessage.focus();

                    $('.section-drop').each(function() {
                        var sectiondropmsg = M.util.get_string('movingdropsectionhelp', 'theme_snap',
                            {moving: sectionname, before: $(this).data('title')}
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
            var addAfterDrops = function() {
                if (document.body.id === "page-site-index") {
                    $('#region-main .sitetopic ul.section').append(
                        '<li class="snap-drop asset-drop">' +
                        '<div class="asset-wrapper">' +
                        '<a href="#">' +
                        M.util.get_string('movehere', 'theme_snap') +
                        '</a>' +
                        '</div>' +
                        '</li>');
                } else {
                    $('li.section .content ul.section').append(
                        '<li class="snap-drop asset-drop">' +
                        '<div class="asset-wrapper">' +
                        '<a href="#">' +
                        M.util.get_string('movehere', 'theme_snap') +
                        '</a>' +
                        '</div>' +
                        '</li>');
                }
            };

            /**
             * Add listener for move checkbox.
             */
            var asettMoveListener = function() {
                $("#region-main").on('change', '.js-snap-asset-move', function(e) {
                    e.stopPropagation();

                    var asset = $(this).parents('.snap-asset')[0];

                    // Make sure after drop is at the end of section.
                    var section = $(asset).parents('ul.section')[0];
                    var afterdrop = $(section).find('li.snap-drop.asset-drop');
                    $(section).append(afterdrop);

                    if (movingObjects.length === 0) {
                        // Moving asset - activity or resource.
                        // Initiate move.
                        var assetname = $(asset).find('.snap-asset-link .instancename').html();

                        log.debug('Moving this asset', assetname);

                        var classes = $(asset).attr('class'),
                            regex = /(?=snap-mime)([a-z0-9\-]*)/;
                        var assetclasses = regex.exec(classes);
                        classes = '';
                        if (assetclasses) {
                            classes = assetclasses.join(' ');
                        }
                        log.debug('Moving this class', classes);
                        $(asset).addClass('asset-moving');
                        $(asset).find('.js-snap-asset-move').prop('checked', 'checked');

                        $('body').addClass('snap-move-inprogress');
                        $('body').addClass('snap-move-asset');
                    }

                    if ($(this).prop('checked')) {
                        // Add asset to moving array.
                        movingObjects.push(asset);
                        $(asset).addClass('asset-moving');
                    } else {
                        // Remove from moving array.
                        removeMovingObject(asset);
                        // Remove moving class
                        $(asset).removeClass('asset-moving');
                        if (movingObjects.length === 0) {
                            // Nothing is ticked for moving, cancel the move.
                            stopMoving();
                        }
                    }
                    updateMovingMessage();
                });
            };

            /**
             * When an asset or drop zone is clicked, execute move.
             */
            var movePlaceListener = function() {
                $(document).on('click', '.snap-move-note, .snap-drop', function(e) {
                    log.debug('Snap drop clicked', e);
                    if (movingObjects) {
                        e.stopPropagation();
                        e.preventDefault();
                        if ($('body').hasClass('snap-move-section')) {
                            ajaxReqMoveSection(this);
                        } else {
                            var target;
                            if ($(this).hasClass('snap-drop')) {
                                target = this;
                            } else {
                                target = $(this).closest('.snap-asset');
                            }
                            ajaxReqMoveAsset(target);
                        }
                    }
                });
            };

            /**
             * When cancel button is pressed in footer, cancel move.
             */
            var moveCancelListener = function() {
                $(".snap-move-cancel").click(
                    function(e) {
                        e.preventDefault();
                        stopMoving();
                    }
                );
            };

            /**
             * Override core functions.
             */
            var overrideCore = function() {
                // Check M.course exists (doesn't exist in social format).
                if (M.course && M.course.resource_toolbox) {
                    M.course.resource_toolbox.handle_resource_dim = function(button, activity, action) {
                        return (action === 'hide') ? 0 : 1;
                    };
                }
            };

            /**
             * Add listeners.
             */
            var addListeners = function() {
                moveSectionListener();
                asettMoveListener();
                moveCancelListener();
                movePlaceListener();
                assetEditListeners();
                addAfterDrops();
                $('body').addClass('snap-course-listening');
            };

            /**
             * Initialise script.
             */
            var initialise = function() {
                // If the move notice html was not output to the dom via php, then we need to add it here via js.
                // This is necessary for the front page which does not have a renderer that we can override.
                if (!$('#snap-move-message').length) {
                    templates.render('theme_snap/snap_move_notice', {})
                        .done(function(result) {
                            $('#region-main').append(result);
                            snapMoveMessage = $('#snap-move-message');
                        });
                }

                // Add listeners.
                addListeners();

                // Override core functions
                overrideCore();
            };
            initialise();
        }
    };

});
