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

define(['jquery', 'core/log', 'core/ajax', 'core/templates', 'core/notification', 'theme_snap/util'],
    function($, log, ajax, templates, notification, util) {

    return {
        init: function(courseLib) {

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
             * Get the section number for an element within a section.
             * @param {object} el
             */
            var elementSectionNumber = function(el) {
                return parseInt($(el).parents('li.section')[0].id.replace('section-', ''));
            };

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
             * @param {function} onsuccess
             * @param {bool}     finaltime
             */
            var ajaxReqMoveGeneral = function(params, onSuccess, finalItem) {
                if (ajaxing) {
                    // Request already made.
                    log.debug('Skipping ajax request, one already in progress');
                    return;
                }

                // Add spinner.
                addAjaxLoading($('#snap-move-message .snap-move-message-title'));

                // Set common params.
                params.sesskey = M.cfg.sesskey;
                params.courseId = courseLib.courseConfig.id;
                params.field = 'move';

                log.debug('Making course/rest.php request', params);
                var req = $.ajax({
                    type: "POST",
                    async: true,
                    data: params,
                    url: M.cfg.wwwroot + courseLib.courseConfig.ajaxurl
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
                            if (params.class === 'resource') {
                                // Only stop moving for resources, sections handle this later once the TOC is reloaded.
                                stopMoving();
                            }
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

                var courseid = courseLib.courseConfig.id;

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
                        notification.alert(null, message, M.util.get_string('ok', 'moodle'));
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
                    ajaxReqMoveGeneral(params, function() {
                        $(target).before($(movingObject));
                        // recurse
                        ajaxReqMoveAsset(target);
                    }, false);
                } else {
                    ajaxReqMoveGeneral(params, function() {
                        $(target).before($(movingObject));
                    }, true);
                }

            };

            /**
             * Ajax request to move section to target.
             * @param {str|object} dropzone
             */
            var ajaxReqMoveSection = function(dropzone) {
                var domTargetSection = parseInt($(dropzone).parents('li.section')[0].id.replace('section-', ''));
                var currentSection = parseInt($(movingObjects[0]).attr('id').replace('section-', ''));
                var targetSection = currentSection < domTargetSection ?
                        domTargetSection - 1 :
                        domTargetSection;

                var params = {
                    class: 'section',
                    id: currentSection,
                    value: targetSection
                };

                ajaxReqMoveGeneral(params, function() {

                    // Update TOC chapters.
                    ajax.call([
                        {
                            methodname: 'theme_snap_course_toc_chapters',
                            args: {
                                courseshortname: courseLib.courseConfig.shortname
                            },
                            done: function(response) {
                                // Update TOC.
                                templates.render('theme_snap/course_toc_chapters', response.chapters)
                                    .done(function(result) {
                                        // Update chapters.
                                        $('#chapters').replaceWith(result);

                                        // Move current section before target section.
                                        $('#section-'+domTargetSection).before($('#section-'+currentSection));

                                        // Renumber section ids and rename section titles.
                                        $.each($('#region-main .course-content > ul li.section'), function(idx, obj){
                                            $(obj).attr('id', 'section-' + idx);
                                            // Get title from TOC (note that its idx + 1 because first entry is
                                            // introduction.
                                            var chapterTitle = $('#chapters li:nth-of-type(' + (idx + 1) + ') .chapter-title')
                                                    .html();
                                            // Update section title with corresponding TOC title - this is necessary
                                            // for weekly topic courses where the section title needs to stay the
                                            // same as the TOC.
                                            $('#section-' + idx + ' .content .sectionname').html(chapterTitle);
                                        });

                                        // Navigate to section in its new location.
                                        location.hash = 'section-' + targetSection;
                                        courseLib.showSection();

                                        // Finally, we have finished moving the section!
                                        stopMoving();
                                    });
                            },
                            fail: function(response) {
                                notification.exception(response);
                                stopMoving();
                            }
                        }
                    ], true, true);

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

                    var courseid = courseLib.courseConfig.id;

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
                            notification.alert(null, message, M.util.get_string('ok', 'moodle'));
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
             * Implement always for promise (missing in core AJAX as of Moodle 3.1).
             * @param {promise} promise
             * @param {object} callbacks
             */
            var promiseHandler = function(promise, callbacks) {

                if (callbacks.done && typeof(callbacks.done) === 'function') {
                    promise.done(function(result) {
                        callbacks.done(result);
                        // Implement always callback.
                        if (callbacks.always && typeof(callbacks.always) === 'function') {
                            callbacks.always(result);
                        }
                    });
                }

                if (callbacks.fail && typeof(callbacks.fail) === 'function') {
                    promise.fail(function(result) {
                        callbacks.fail(result);
                        // Implement always callback.
                        if (callbacks.always && typeof(callbacks.always) === 'function') {
                            callbacks.always(result);
                        }
                    });
                }

            };

            /**
             * Generic section action handler.
             *
             * @param {string} action visibility, highlight
             * @param {null|function} callback for when completed.
             */
            var sectionActionListener = function(action, onComplete) {

                $('#region-main').on('click', '.snap-section-editing.actions .snap-' + action, function(e) {

                    e.stopPropagation();
                    e.preventDefault();

                    /**
                     * Invalid section action exception.
                     *
                     * @param {string} action
                     */
                    var InvalidActionException = function(action) {
                        this.message = 'Invalid section action: ' + action;
                        this.name = 'invalidActionException';
                    };

                    // Check action is valid.
                    var validactions = ['visibility', 'highlight'];
                    if (validactions.indexOf(action) === -1) {
                        throw new InvalidActionException(action);
                    }

                    // Only allow 1 request to be made at a time.
                    // Note, this is still async - just limited to one request in queue.
                    if (ajaxing) {
                        // Request already made.
                        log.debug('Skipping ajax request, one already in progress');
                        return;
                    }
                    ajaxing = true;

                    var toggler = action === 'visibility' ? 'snap-show' : 'snap-marker';
                    var toggle = $(this).hasClass(toggler) ? 1 : 0;

                    var sectionNumber = elementSectionNumber(this);
                    var sectionActionsSelector = '#section-' + sectionNumber + ' .snap-section-editing';
                    var actionSelector = sectionActionsSelector + ' .snap-' + action;

                    // Add spinner.
                    addAjaxLoading(sectionActionsSelector, true);

                    // Make ajax call.
                    var ajaxPromises = ajax.call([
                        {
                            methodname: 'theme_snap_course_sections',
                            args: {
                                courseshortname: courseLib.courseConfig.shortname,
                                action: action,
                                sectionnumber: sectionNumber,
                                value: toggle
                            }
                        }
                    ], true, true);

                    // Handle ajax promises.
                    promiseHandler(ajaxPromises[0], {

                        done: function(response) {

                            // Update section action and then reload TOC.
                            promiseHandler(templates.render('theme_snap/course_action_section', response.actionmodel), {
                                done: function(result) {
                                    $(actionSelector).replaceWith(result);
                                    $(actionSelector).focus();
                                    // Update TOC.
                                    promiseHandler(templates.render('theme_snap/course_toc', response.toc), {
                                            done: function(result) {
                                                $('#course-toc').replaceWith(result);
                                                if (onComplete && typeof(onComplete) === 'function') {
                                                    onComplete(sectionNumber, toggle);
                                                }
                                            },
                                            always: function() {
                                                // Cancel spinner always!
                                                $(sectionActionsSelector + ' .loadingstat').remove();
                                            }
                                        }
                                    );
                                },
                                fail: function() {
                                    // Cancel spinner on fail only (toc reload promise takes care of this always).
                                    $(sectionActionsSelector + ' .loadingstat').remove();
                                }

                            });
                        },

                        fail: function() {
                            var message;
                            if (action === 'visibility') {
                                message = M.util.get_string('error:failedtochangesectionvisibility', 'theme_snap');
                            } else {
                                message = M.util.get_string('error:failedtohighlightsection', 'theme_snap');
                            }
                            notification.alert(null, message, M.util.get_string('ok', 'moodle'));
                            // Cancel spinner on fail only (nested functions take care of spinner).
                            $(sectionActionsSelector + ' .loadingstat').remove();
                        },

                        always: function() {
                            // Allow another request now this has finished.
                            ajaxing = false;
                        }

                    });
                });
            };

            /**
             * Highlight section on click.
             */
            var highlightSectionListener = function() {
                sectionActionListener('highlight');
            };

            /**
             * Toggle section visibility on click.
             */
            var toggleSectionListener = function() {
                var manageHiddenClass = function(sectionNumber, toggle) {
                    if (toggle === 0) {
                        $('#section-' + sectionNumber).addClass('hidden');
                    } else {
                        $('#section-' + sectionNumber).removeClass('hidden');
                    }
                };
                sectionActionListener('visibility', manageHiddenClass);
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
                    var sectionNumber = elementSectionNumber(this);
                    log.debug('Section is', sectionNumber);
                    var section = $('#section-' + sectionNumber);
                    var sectionName = section.find('.sectionname').text();

                    log.debug('Moving this section', sectionName);
                    movingObjects = [section];

                    // This should never happen, but just in case...
                    $('.section-moving').removeClass('section-moving');
                    section.addClass('section-moving');
                    $('a[href$=#section-' + sectionNumber + ']').parent('li').addClass('section-moving');
                    $('body').addClass('snap-move-section');

                    var title = M.util.get_string('moving', 'theme_snap', sectionName);
                    snapMoveMessage.find('.snap-move-message-title').html(title);
                    snapMoveMessage.focus();

                    $('.section-drop').each(function() {
                        var sectionDropMsg = M.util.get_string('movingdropsectionhelp', 'theme_snap',
                            {moving: sectionName, before: $(this).data('title')}
                        );
                        $(this).html(sectionDropMsg);
                    });

                    $('#snap-move-message p.sr-only').html(
                        M.util.get_string('movingstartedhelp', 'theme_snap', sectionName)
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
            var assetMoveListener = function() {
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
             * Add listeners.
             */
            var addListeners = function() {
                moveSectionListener();
                toggleSectionListener();
                highlightSectionListener();
                assetMoveListener();
                moveCancelListener();
                movePlaceListener();
                assetEditListeners();
                addAfterDrops();
                $('body').addClass('snap-course-listening');
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
                util.whenTrue(function(){
                    return M.course && M.course.init_section_toolbox;
                }, function(){overrideCore();}, true);

            };
            initialise();
        }
    };

});
