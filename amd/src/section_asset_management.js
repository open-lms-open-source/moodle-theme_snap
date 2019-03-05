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
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log', 'core/ajax', 'core/str', 'core/templates', 'core/notification',
    'theme_snap/util', 'theme_snap/ajax_notification', 'theme_snap/footer_alert'],
    function($, log, ajax, str, templates, notification, util, ajaxNotify, footerAlert) {

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

            var ajaxTracker;

            /**
             * AJAX tracker class - for tracking chained AJAX requests (prevents behat intermittent faults).
             * Also, sets and unsets ajax classes on trigger element / child of trigger if specified.
             */
            var AjaxTracker = function() {

                var triggersByKey = {};

                /**
                 * Starts tracking.
                 * @param {string} jsPendingKey
                 * @param {domElement} trigger
                 * @param {string} subSelector
                 * @returns {boolean}
                 */
                this.start = function(jsPendingKey, trigger, subSelector) {
                    if (this.ajaxing(jsPendingKey)) {
                        log.debug('Skipping ajax request for ' + jsPendingKey + ', AJAX already in progress');
                        return false;
                    }
                    M.util.js_pending(jsPendingKey);
                    triggersByKey[jsPendingKey] = {trigger: trigger, subSelector: subSelector};
                    if (trigger) {
                        if (subSelector) {
                            $(trigger).find(subSelector).addClass('ajaxing');
                        } else {
                            $(trigger).addClass('ajaxing');
                        }
                    }
                    return true;
                };

                /**
                 * Is there an AJAX request in progress.
                 * @param {string} jsPendingKey
                 * @returns {boolean}
                 */
                this.ajaxing = function(jsPendingKey) {
                    return M.util.pending_js.indexOf(jsPendingKey) > -1;
                };

                /**
                 * Completes tracking.
                 * @param {string} jsPendingKey
                 */
                this.complete = function(jsPendingKey) {
                    var trigger, subSelector;
                    if (triggersByKey[jsPendingKey]) {
                        trigger = triggersByKey[jsPendingKey].trigger;
                        subSelector = triggersByKey[jsPendingKey].subSelector;
                    }
                    if (trigger) {
                        if (subSelector) {
                            $(trigger).find(subSelector).removeClass('ajaxing');
                        } else {
                            $(trigger).removeClass('ajaxing');
                        }
                    }
                    delete triggersByKey[jsPendingKey];
                    M.util.js_complete(jsPendingKey);
                };
            };

            ajaxTracker = new AjaxTracker();

            /**
             * Get the section number from a section element.
             * @param {jQuery|object} el
             * @returns {number}
             */
            var sectionNumber = function(el) {
                return (parseInt($(el).attr('id').replace('section-', '')));
            };

            /**
             * Get the section number for an element within a section.
             * @param {object} el
             * @returns {number}
             */
            var parentSectionNumber = function(el) {
                return sectionNumber($(el).parents('li.section.main')[0]);
            };

            /**
             * Moving has stopped, clean up.
             */
            var stopMoving = function() {
                $('body').removeClass('snap-move-inprogress');
                $('body').removeClass('snap-move-section');
                $('body').removeClass('snap-move-asset');
                footerAlert.hideAndReset();
                $('.section-moving').removeClass('section-moving');
                $('.asset-moving').removeClass('asset-moving');
                $('.snap-asset a').removeAttr('tabindex');
                $('.snap-asset button').removeAttr('disabled');
                $('.js-snap-asset-move').removeAttr('checked');
                movingObjects = [];
            };

            /**
             * Move fail - sad face :(.
             */
            var moveFailed = function() {
                var actname = $(movingObject).find('.instancename').html();

                footerAlert.removeAjaxLoading();
                footerAlert.setTitle(M.util.get_string('movefailed', 'theme_snap', actname));
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
                var title;
                if (movingObjects.length === 1) {
                    var assetname = $(movingObjects[0]).find('.snap-asset-link .instancename').html();
                    assetname = assetname || M.util.get_string('pluginname', 'label', assetname);
                    title = M.util.get_string('moving', 'theme_snap', assetname);
                } else {
                    title = M.util.get_string('movingcount', 'theme_snap', movingObjects.length);
                }
                footerAlert.setTitle(title);
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
             * General move request
             *
             * @param {object}   params
             * @param {function} onSuccess
             * @param {bool}     finalItem
             */
            var ajaxReqMoveGeneral = function(params, onSuccess, finalItem) {
                if (ajaxing) {
                    // Request already made.
                    log.debug('Skipping ajax request, one already in progress');
                    return;
                }

                // Add spinner.
                footerAlert.addAjaxLoading();

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
                    ajaxNotify.ifErrorShowBestMsg(data).done(function(errorShown) {
                        if (errorShown) {
                            log.debug('Ajax request fail');
                            moveFailed();
                            return;
                        } else {
                            // No errors, call success callback and stop moving if necessary.
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
                });
                req.fail(function() {
                    moveFailed();
                });

                if (finalItem) {
                    req.always(function() {
                        ajaxing = false;
                        footerAlert.removeAjaxLoading();
                    });
                }
            };

            /**
             * Get section title.
             * @param {integer} section
             * @returns {*|jQuery}
             */
            var getSectionTitle = function(section) {
                // Get title from TOC.
                return $('#chapters li:nth-of-type(' + (section + 1) + ') .chapter-title').html();
            };

            /**
             * Update next / previous links.
             * @param {string} selector
             * @return {promise}
             */
            var updateSectionNavigation = function(selector) {
                var dfd = $.Deferred();
                var sections, totalSectionCount;
                if (!selector) {
                    selector = '#region-main .course-content > ul li.section';
                    sections = $(selector);
                    totalSectionCount = sections.length;
                } else {
                    sections = $(selector);
                    var allSections = $('#region-main .course-content > ul li.section');
                    totalSectionCount = allSections.length;
                }

                var completed = 0;

                $.each(sections, function(idx, el) {
                    var sectionNum = sectionNumber(el);
                    var previousSection = sectionNum - 1;
                    var nextSection = sectionNum + 1;
                    var previous = false;
                    var next = false;
                    var hidden, extraclasses;
                    if (previousSection > -1) {
                        hidden = $('#section-' + previousSection).hasClass('hidden');
                        extraclasses = hidden ? ' dimmed_text' : '';
                        previous = {
                            section: previousSection,
                            title: getSectionTitle(previousSection),
                            classes: extraclasses
                        };
                    }
                    if (nextSection < totalSectionCount) {
                        hidden = $('#section-' + nextSection).hasClass('hidden');
                        extraclasses = hidden ? ' dimmed_text' : '';
                        next = {
                            section: nextSection,
                            title: getSectionTitle(nextSection),
                            classes: extraclasses
                        };
                    }
                    var navigation = {
                        previous: previous,
                        next: next
                    };
                    templates.render('theme_snap/course_section_navigation', navigation)
                        .done(function(result) {
                            $('#section-' + sectionNum + ' .section_footer').replaceWith(result);
                            completed++;
                            if (completed === sections.length) {
                                dfd.resolve();
                            }
                        });

                });
                return dfd.promise();
            };

            /**
             * Update sections.
             */
            var updateSections = function() {

                // Renumber section ids, rename section titles.
                $.each($('#region-main .course-content > ul li.section'), function(idx, obj) {
                    $(obj).attr('id', 'section-' + idx);
                    // Get title from TOC (note that its idx + 1 because first entry is
                    // introduction.
                    var chapterTitle = getSectionTitle(idx);
                    // Update section title with corresponding TOC title - this is necessary
                    // for weekly topic courses where the section title needs to stay the
                    // same as the TOC.
                    $('#section-' + idx + ' .content .sectionname').html(chapterTitle);
                    // Update section data attribute to reflect new section idx.
                    $(this).find('a.section-modchooser-link').attr('data-section', idx);
                });

                updateSectionNavigation();
            };

            /**
             * Delete section dialog and confirm function.
             * @param {object} e
             * @param {object} el
             */
            var sectionDelete = function(e, el) {
                e.preventDefault();
                var sectionNum = parentSectionNumber(el);
                var section = $('#section-' + sectionNum);
                var sectionName = section.find('.sectionname').text();

                /**
                 * Delete section.
                 */
                var doDelete = function() {

                    if (!ajaxTracker.start('section_delete', el)) {
                        // Already in progress.
                        return;
                    }

                    var delProgress = M.util.get_string('deletingsection', 'theme_snap', sectionName);

                    footerAlert.setTitle(delProgress);
                    footerAlert.addAjaxLoading('');
                    footerAlert.show();

                    var params = {
                        courseshortname: courseLib.courseConfig.shortname,
                        action: 'delete',
                        sectionnumber: sectionNum,
                        value: 1
                    };

                    log.debug('Making course/rest.php section delete request', params);

                    // Make ajax call.
                    var ajaxPromises = ajax.call([
                        {
                            methodname: 'theme_snap_course_sections',
                            args: params
                        }
                    ], true, true);

                    // Handle ajax promises.
                    ajaxPromises[0]
                        .done(function(response) {
                            // Update TOC.
                            templates.render('theme_snap/course_toc', response.toc)
                                .done(function(result) {
                                    $('#course-toc').html($(result).html());
                                    $(document).trigger('snapTOCReplaced');
                                    // Remove section from DOM.
                                    section.remove();
                                    updateSections();

                                    // Current section no longer exists so change location to previous section.
                                    if (sectionNum >= $('.course-content > ul li.section').length) {
                                        location.hash = 'section-' + (sectionNum - 1);
                                    }
                                    courseLib.showSection();
                                    // We can't complete the action in the 'always' section because we want it to
                                    // definitely be called after the section is removed from the DOM.
                                    ajaxTracker.complete('section_delete');
                                })
                                .always(function() {
                                    // Allow another request now this has finished.
                                    footerAlert.hideAndReset();
                                })
                                .fail(function() {
                                    ajaxTracker.complete('section_delete');
                                });
                        })
                        .fail(function(response) {
                            ajaxNotify.ifErrorShowBestMsg(response);
                            footerAlert.hideAndReset();
                            // Allow another request now this has finished.
                            ajaxTracker.complete('section_delete');
                        });
                };

                var delTitle = M.util.get_string('confirm', 'moodle');
                var delConf = M.util.get_string('confirmdeletesection', 'moodle', sectionName);
                var ok = M.util.get_string('deletesectionconfirm', 'theme_snap');
                var cancel = M.util.get_string('cancel', 'moodle');
                notification.confirm(delTitle, delConf, ok, cancel, doDelete);
            };

            /**
             * Generic action handler for all asset actions.
             * @param {event} e
             * @param {domNode} triggerEl
             */
            var assetAction = function(e, triggerEl) {
                e.preventDefault();

                var assetEl = $($(triggerEl).parents('.snap-asset')[0]),
                    cmid = Number(assetEl[0].id.replace('module-', '')),
                    instanceName = assetEl.find('.instancename').text().trim(),
                    action = $(triggerEl).data('action'),
                    errActionKey = '',
                    errMessageKey = '',
                    errAction = '',
                    errMessage = '',
                    jsPendingKey = 'asset_' + action;

                if (ajaxTracker.ajaxing(jsPendingKey)) {
                    // Already in progress.
                    // We check this because we don't want to show the confirmation dialog when in progress.
                    return;
                }

                var actionAJAX = function() {
                    if (!ajaxTracker.start(jsPendingKey, assetEl, '.snap-edit-asset-more')) {
                        // Request already made.
                        return;
                    }

                    var params = {
                        'action': action,
                        'sectionreturn': 0,
                        'id': cmid
                    };

                    ajax.call([
                        {
                            methodname: 'core_course_edit_module',
                            args: params
                        }
                    ], true, true)[0]
                        .done(function(response) {
                            ajaxNotify.ifErrorShowBestMsg(response, errAction, errMessage).done(function(errorShown) {
                                ajaxTracker.complete(jsPendingKey);
                                if (errorShown) {
                                    log.debug('Ajax request fail');
                                    return;
                                } else {
                                    log.debug('Ajax request successful');
                                    if (action === 'delete') {
                                        // Remove asset from DOM.
                                        assetEl.remove();
                                        // Remove asset searchable.
                                        $('#toc-searchables li[data-id="' + cmid + '"]').remove();
                                    } else if (action === 'show') {
                                        assetEl.removeClass('draft');
                                    } else if (action === 'hide') {
                                        assetEl.addClass('draft');
                                    } else if (action === 'duplicate') {
                                        assetEl.replaceWith(response);
                                    }
                                }
                            });
                        })
                        .fail(function(response) {
                            ajaxNotify.ifErrorShowBestMsg(response, errAction, errMessage).done(function() {
                                ajaxTracker.complete(jsPendingKey);
                            });
                        })
                        .always(function() {
                            footerAlert.hideAndReset();
                        });
                };

                /**
                 * Get error strings incase of AJAX failure.
                 * @returns {*|Promise}
                 */
                var getErrorStrings = function() {
                    if (action === 'duplicate') {
                        errActionKey = 'action:duplicateasset';
                        errMessageKey = 'error:failedtoduplicateasset';
                    } else if (action === 'show' || action === 'hide') {
                        errActionKey = 'action:changeassetvisibility';
                        errMessageKey = 'error:failedtochangeassetvisibility';
                    } else if (action === 'delete') {
                        errActionKey = 'action:deleteasset';
                        errMessageKey = 'error:failedtodeleteasset';
                    }
                    return str.get_strings([
                        {key: errActionKey, component: 'theme_snap'},
                        {key: errMessageKey, component: 'theme_snap'}
                    ]);
                };

                getErrorStrings().then(function(strings) {
                    errAction = strings[0];
                    errMessage = strings[0];
                    if (action === 'delete') {
                        // Create confirmation strings.
                        var delConf = '',
                            plugindata = {
                                type: M.util.get_string('pluginname', assetEl.attr('class').match(/modtype_([^\s]*)/)[1])
                            };
                        if (instanceName !== '') {
                            plugindata.name = instanceName;
                            delConf = M.util.get_string('deletechecktypename', 'moodle', plugindata);
                        } else {
                            delConf = M.util.get_string('deletechecktype', 'moodle', plugindata);
                        }

                        var delTitle = M.util.get_string('confirm', 'moodle');
                        var ok = M.util.get_string('deleteassetconfirm', 'theme_snap', plugindata.type);
                        var cancel = M.util.get_string('cancel', 'moodle');
                        notification.confirm(delTitle, delConf, ok, cancel, actionAJAX);
                    } else {
                        actionAJAX();
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
                params.class = 'resource';

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
                        params.sectionId = parentSectionNumber(target);
                    } else {
                        params.sectionId = parentSectionNumber(movingObject);
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
                var domTargetSection = parentSectionNumber(dropzone);
                var currentSection = sectionNumber(movingObjects[0]);
                var targetSection = currentSection < domTargetSection ?
                        domTargetSection - 1 :
                        domTargetSection;

                var params = {
                    "class": 'section',
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
                                        $('#section-' + domTargetSection).before($('#section-' + currentSection));

                                        // Update section ids, next previous links, etc.
                                        updateSections();

                                        // Navigate to section in its new location.
                                        location.hash = 'section-' + targetSection;
                                        courseLib.showSection();

                                        // Finally, we have finished moving the section!
                                        stopMoving();
                                    });
                            },
                            fail: function(response) {
                                ajaxNotify.ifErrorShowBestMsg(response);
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
                var actionSelectors = '.snap-asset-actions .js_snap_hide, ';
                actionSelectors += '.snap-asset-actions .js_snap_show, ';
                actionSelectors += '.snap-asset-actions .js_snap_delete, ';
                actionSelectors += '.snap-asset-actions .js_snap_duplicate';

                $(document).on('click', actionSelectors, function(e) {
                    assetAction(e, this);
                });
            };

            /**
             * Generic section action handler.
             *
             * @param {string} action visibility, highlight
             * @param {null|function} onComplete for when completed.
             */
            var sectionActionListener = function(action, onComplete) {

                $('#region-main').on('click', '.snap-section-editing.actions .snap-' + action, function(e) {

                    e.stopPropagation();
                    e.preventDefault();

                    var trigger = this;

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

                    if (!ajaxTracker.start('section_' + action, trigger)) {
                        // Request already in progress.
                        return;
                    }

                    // For toggling visibility.
                    var toggle;
                    if (action === 'visibility') {
                        toggle = $(this).hasClass('snap-hide') ? 0 : 1;
                    } else {
                        // For toggling highlight/mark as current.
                        toggle = $(this).attr('aria-pressed') === 'true' ? 0 : 1;
                    }

                    var sectionNumber = parentSectionNumber(this);
                    var sectionActionsSelector = '#section-' + sectionNumber + ' .snap-section-editing';
                    var actionSelector = sectionActionsSelector + ' .snap-' + action;


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
                    ajaxPromises[0]
                    .fail(function(response) {
                        var errMessage, errAction;
                        if (action === 'visibility') {
                            errMessage = M.util.get_string('error:failedtochangesectionvisibility', 'theme_snap');
                            errAction = M.util.get_string('action:changesectionvisibility', 'theme_snap');
                        } else {
                            errMessage = M.util.get_string('error:failedtohighlightsection', 'theme_snap');
                            errAction = M.util.get_string('action:highlightsectionvisibility', 'theme_snap');
                        }
                        ajaxNotify.ifErrorShowBestMsg(response, errAction, errMessage).done(function() {
                            // Allow another request now this has finished.
                            ajaxTracker.complete('section_' + action);
                        });
                    }).always(function() {
                        $(trigger).removeClass('ajaxing');
                    }).done(function(response) {
                        // Update section action and then reload TOC.
                        return templates.render('theme_snap/course_action_section', response.actionmodel)
                        .then(function(result) {
                            $(actionSelector).replaceWith(result);
                            $(actionSelector).focus();
                            // Update TOC.
                            return templates.render('theme_snap/course_toc', response.toc);
                        }).then(function(result) {
                            $('#course-toc').html($(result).html());
                            $(document).trigger('snapTOCReplaced');
                            if (onComplete && typeof (onComplete) === 'function') {
                                var completion = onComplete(sectionNumber, toggle);
                                if (completion && typeof (completion.always) === 'function') {
                                    // Callback returns a promise, js no longer running.
                                    completion.always(
                                        function() {
                                            // Allow another request now this has finished.
                                            ajaxTracker.complete('section_' + action);
                                        }
                                    );
                                } else {
                                    // Callback does not return a promise, js no longer running.
                                    // Allow another request now this has finished.
                                    ajaxTracker.complete('section_' + action);
                                }
                            } else {
                                // Allow another request now this has finished.
                                ajaxTracker.complete('section_' + action);
                            }
                        });
                    });
                });
            };

            /**
             * Highlight section on click.
             */
            var highlightSectionListener = function() {
                sectionActionListener('highlight', function(sectionNumber) {
                    $('#section-' + sectionNumber).toggleClass('current');

                    // Reset sections which are not highlighted.
                    var $notCurrent = $('li.section.main')
                    .not('#section-' + sectionNumber)
                    .not('#section-0').removeClass("current");

                    $notCurrent.each(function() {
                        var highlighter = $(this).find('.snap-highlight');
                        var sectionNumber = parentSectionNumber(highlighter);
                        var newLink = $(highlighter).attr('href').replace(/(marker=)[0-9]+/ig, '$1' + sectionNumber);
                        $(highlighter).attr('href', newLink).attr('aria-pressed', 'false');
                    });
                });
            };

            /**
             * Delete section on click.
             */
            var deleteSectionListener = function() {
                $(document).on('click', '.snap-section-editing.actions .snap-delete', function(e) {
                    sectionDelete(e, this);
                });
            };

            /**
             * Toggle section visibility on click.
             */
            var toggleSectionListener = function() {
                /**
                 * Toggle hidden class and update section navigation.
                 * @param {number} sectionNumber
                 * @param {boolean} toggle
                 * @returns {Promise}
                 */
                var manageHiddenClass = function(sectionNumber, toggle) {
                    if (toggle === 0) {
                        $('#section-' + sectionNumber).addClass('hidden');
                    } else {
                        $('#section-' + sectionNumber).removeClass('hidden');
                    }

                    // Update the section navigation either side of the current section.
                    var selectors = [
                        '#section-' + (sectionNumber - 1),
                        '#section-' + (sectionNumber + 1)
                    ];
                    var selector = selectors.join(',');
                    return updateSectionNavigation(selector);
                };
                sectionActionListener('visibility', manageHiddenClass);
            };

            /**
             * Show footer alert for moving.
             */
            var footerAlertShowMove = function() {
                footerAlert.show(function(e) {
                    e.preventDefault();
                    stopMoving();
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
                    footerAlertShowMove();

                    // Moving a section.
                    var sectionNumber = parentSectionNumber(this);
                    log.debug('Section is', sectionNumber);
                    var section = $('#section-' + sectionNumber);
                    var sectionName = section.find('.sectionname').text();

                    log.debug('Moving this section', sectionName);
                    movingObjects = [section];

                    // This should never happen, but just in case...
                    $('.section-moving').removeClass('section-moving');
                    section.addClass('section-moving');
                    $('a[href="#section-' + sectionNumber + '"]').parent('li').addClass('section-moving');
                    $('body').addClass('snap-move-section');

                    var title = M.util.get_string('moving', 'theme_snap', sectionName);
                    footerAlert.setTitle(title);

                    $('.section-drop').each(function() {
                        var sectionDropMsg = M.util.get_string('movingdropsectionhelp', 'theme_snap',
                            {moving: sectionName, before: $(this).data('title')}
                        );
                        $(this).html(sectionDropMsg);
                    });

                    footerAlert.setSrNotice(M.util.get_string('movingstartedhelp', 'theme_snap', sectionName));
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
                        $('.snap-asset button').attr('disabled','disabled');
                        $(asset).find('button').removeAttr('disabled');
                        $('.snap-asset .snap-asset-content a').attr('tabindex','-1');
                        $(asset).find('a').removeAttr('tabindex');

                        $(asset).find('.js-snap-asset-move').prop('checked', 'checked');

                        $('body').addClass('snap-move-inprogress');
                        $('body').addClass('snap-move-asset');

                    }

                    if ($(this).prop('checked')) {
                        // Add asset to moving array.
                        movingObjects.push(asset);
                        $(asset).find('a').removeAttr('tabindex');
                        $(asset).find('button').removeAttr('disabled');
                        $(asset).addClass('asset-moving');
                    } else {
                        // Remove from moving array.
                        removeMovingObject(asset);
                        // Remove moving class
                        $(asset).find('.snap-asset-content a').attr('tabindex','-1');
                        $(asset).find('button').attr('disabled','disabled');
                        $(asset).removeClass('asset-moving');
                        if (movingObjects.length === 0) {
                            // Nothing is ticked for moving, cancel the move.
                            stopMoving();
                        }
                    }
                    footerAlertShowMove();
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
             * Add listeners.
             */
            var addListeners = function() {
                moveSectionListener();
                toggleSectionListener();
                highlightSectionListener();
                deleteSectionListener();
                assetMoveListener();
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
                    /* eslint-disable camelcase */
                    M.course.resource_toolbox.handle_resource_dim = function(button, activity, action) {
                        return (action === 'hide') ? 0 : 1;
                    };
                    /* eslint-enable camelcase */
                }
            };

            /**
             * Initialise script.
             */
            var initialise = function() {
                // Add listeners.
                addListeners();

                // Override core functions
                util.whenTrue(function() {
                    return M.course && M.course.init_section_toolbox;
                }, function() {
overrideCore();
}, true);

            };
            initialise();
        }
    };

});
