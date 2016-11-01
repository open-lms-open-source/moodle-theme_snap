// jshint ignore: start
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
 * @namespace M.snap_message_badge
 */
M.snap_message_badge = M.snap_message_badge || {};

/**
 * Determine if we have done init yet
 */
M.snap_message_badge.initDone = false;

/**
 * Holds the ID of the message container that is currently being read
 */
M.snap_message_badge.activeMessageId = undefined;

/**
 * Holds the URL to mark a message read before going to the real URL
 */
M.snap_message_badge.forwardURL = undefined;

M.snap_message_badge.perrequest = 5;

M.snap_message_badge.offset = 0;

M.snap_message_badge.totalmessages = 0;

M.snap_message_badge.courseid = null;

/**
 * Show the best error message for a response error.
 *
 * @param {obejct} response
 */
M.snap_message_badge.responseBestErrorMessage = function(response) {
    require(
        [
            'theme_snap/ajax_notification'
        ], function(ajaxNotify) {
            ajaxNotify.ifErrorShowBestMsg(response.error);
        }
    );
}

/**
 * Init Badge
 *
 * @param {YUI} Y
 */
M.snap_message_badge.init_badge = function(Y, forwardURL, courseid) {

    // Set course id.
    M.snap_message_badge.courseid = courseid;

    // Save for later.
    M.snap_message_badge.forwardURL = forwardURL;

    // Add listener when jquery events ready.
    // If you just use $(document).on, the listener will sometimes fail because jquery isn't fully initialised.
    // This function waits until jquery is in a state where you can listen for custom events.
    var onJQueryEventsReady = function(eventname, callback, count) {
        if (!count) {
            count = 1;
        }
        if (count > 20) {
            // Error, jquery events never ready!
            return;
        }
        count++;
        if (typeof($._data( $(document)[0], 'events' )) != 'undefined') {
            $(document).on(eventname, callback);
        } else {
            window.setTimeout(function() {
                onJQueryEventsReady(eventname, callback, count);
            }, 100)
        }
    };

    onJQueryEventsReady('snapUpdatePersonalMenu', function() {
        M.snap_message_badge.init_overlay(Y);
    });
};

/**
 * Init the overlay
 *
 * @param Y
 */
M.snap_message_badge.init_overlay = function(Y, callback) {
    if (M.snap_message_badge.initDone) {
        if (typeof callback == 'function') {
            callback();
        }
        return;
    }

    M.snap_message_badge.initDone = true;
    M.snap_message_badge.get_messages_html(Y, M.snap_message_badge.onresponse_messages_html);
};

/**
 * Attach behavior to a message
 *
 * @param Y
 * @param messageNode
 */
M.snap_message_badge.init_message = function(Y, messageNode) {
    messageNode.all('.message_badge_contexturl').on('click', function(e) {
        M.snap_message_badge.forward(Y, messageNode, e);
    });
    messageNode.all('.message_badge_message_text a').on('click', function(e) {
        M.snap_message_badge.forward(Y, messageNode, e);
    });
    messageNode.one('.message_badge_ignoreurl').on('click', function(e) {
        e.preventDefault();
        M.snap_message_badge.ignore_message(Y, messageNode.one('.message_badge_ignoreurl').get('href'));
        //messageNode.addClass('message_badge_hidden');
        messageNode.remove(true);
        M.snap_message_badge.offset--;
        M.snap_message_badge.show_all_read(Y);
    });
    messageNode.one('.message_badge_readurl').on('click', function(e) {
        e.preventDefault();

        if (messageNode.get('id') != M.snap_message_badge.activeMessageId) {
            M.snap_message_badge.populate_messagebody(Y, messageNode, messageNode.one('.message_badge_readurl').get('href'), function(unreadCount) {
                messageNode.addClass('dimmed_text message_badge_message_opened');
                M.snap_message_badge.update_unread_count(Y, unreadCount);
                M.snap_message_badge.activeMessageId = messageNode.get('id');
                // decrement offset
                M.snap_message_badge.offset--;
            });
        }
    });
};

/**
 * Show error dialog
 *
 * @param {string} msg
 */
M.snap_message_badge.alert_error = function(msg) {
    Y.use('moodle-core-notification-alert', function () {
        var alert = new M.core.alert({
            title : M.util.get_string('erroroccur', 'debug'),
            message : msg,
            yesLabel: M.util.get_string('ok', 'moodle')
        });
        alert.show();
    });
};

/**
 * Send a request to ignore a message (AKA mark as read) and
 * update the badge count
 *
 * @param Y
 * @param url
 */
M.snap_message_badge.ignore_message = function(Y, url) {
    Y.io(url, {
        on: {
            success: function(id, o) {
                var response = Y.JSON.parse(o.responseText);

                if (response.error != undefined) {
                    M.snap_message_badge.responseBestErrorMessage(response.error);
                } else {
                    M.snap_message_badge.update_unread_count(Y, response.args);
                }
                M.snap_message_badge.show_all_read (Y);
            },
            failure: function(id, o) {
                M.snap_message_badge.alert_error(M.str.message_badge.genericasyncfail);
            }
        }
    });
};

/**
 * Used to update the badge count
 *
 * @param Y
 * @param unreadCount
 */
M.snap_message_badge.update_unread_count = function(Y, unreadCount) {
    if (unreadCount >= 1) {
        Y.one('.message_badge_count').set('innerHTML', unreadCount);
    } else {
        Y.one('.message_badge_count').remove();
        // Removed this - we only want to show this if there are no messages on screen (behaviour requested by Snap team 2014-05-14)
        //Y.one('.message_badge_empty').removeClass('message_badge_hidden');

        M.snap_message_badge.show_all_read (Y);
    }
};

/**
 * Reveal 'all read' notice if appropriate
 *
 * @param Y
 */
M.snap_message_badge.show_all_read = function(Y){
    // Show all messages read notice
    if (Y.all('.message_badge_messages > .message_badge_message').size() == 0){
        if (!Y.one('#badge_moremessages') || Y.one('#badge_moremessages').getStyle('display') == 'none') {
            Y.one('.message_badge_empty').removeClass('message_badge_hidden');
        }
    }
};

/**
 * Some links take us off page by clicking on them, first mark the
 * message that they belong to as read, then redirect to their URL
 *
 * @param Y
 * @param messageNode
 * @param e
 */
M.snap_message_badge.forward = function(Y, messageNode, e) {
    var aNode = null;
    if (e.target.test('a')) {
        aNode = e.target;
    } else {
        aNode = e.target.ancestor('a');
    }
    if (aNode !== null) {
        // Go to our page first to mark the message read, it'll then forward to the actual URL
        aNode.set('href',
            M.snap_message_badge.forwardURL +
                '&messageid=' + encodeURIComponent(messageNode.getAttribute('messageid')) +
                '&url=' + encodeURIComponent(aNode.get('href'))
        );

        // We do this just in case the URL opens a new window (prevent, bad behavior)
        aNode.target.removeAttribute('target');
    }
};

/**
 * Populates a overlay with information found at endpoint
 *
 * @param Y
 * @param url
 */
M.snap_message_badge.populate_messagebody = function(Y, messagenode, url, onsuccess) {
    Y.io(url + '&courseid=' + M.snap_message_badge.courseid, {
        on: {
            start: function() {
                var loadingstat = Y.Node.create('<div class="loadingstat three-quarters">' + Y.Escape.html(M.util.get_string('loading', 'theme_snap')) + '</div>');
                messagenode.one('.message_badge_message_text').append(loadingstat);
            },
            success: function(id, o) {
                var response = Y.JSON.parse(o.responseText);

                // Remove loader.
                messagenode.one('.loadingstat').remove();

                if (response.error != undefined) {
                    M.snap_message_badge.responseBestErrorMessage(response.error);
                } else {
                    var contentnode = messagenode.one('.message_badge_message_text');

                    // Get rid of read action (its read now!).
                    messagenode.one('.message_badge_readurl').remove();
                    messagenode.one('.message_badge_url_separator').remove();

                    // Should we show a full message?
                    var messagebody = '';
                    var showfull = messagenode.getAttribute('data-has-full-message') == 'true';
                    if (showfull){
                        messagebody = response.body;
                    } else {
                        messagebody = Y.Escape.html(M.util.get_string('messageread', 'theme_snap'));
                    }

                    var articlehtml = '';

                    // Should I be using Y.Escape.html on the html that is getting returned?
                    if (response.header != undefined) {
                        articlehtml += "<header>" + response.header + "</header>";
                    }
                    if (response.body != undefined) {
                        var classstr = !showfull ? ' class="messagematch"' : '';
                        articlehtml += '<div' + classstr + '>' + messagebody + '</div>'
                    }
                    if (response.footer != undefined) {
                        articlehtml += '<footer>' + response.footer + '</footer>';
                    }
                    var article = Y.Node.create('<article>' + articlehtml + '</article>');
                    contentnode.appendChild(article);
                    article.addClass('slideInDown');

                    if (typeof onsuccess == 'function') {
                        if (response.args != undefined) {
                            onsuccess(response.args);
                        } else {
                            onsuccess();
                        }
                    }
                }
            },
            failure: function(id, o) {
                M.snap_message_badge.alert_error(M.str.message_badge.genericasyncfail);
            }
        }
    });
};

/**
 * Process message html
 *
 * @param response
 */
M.snap_message_badge.onresponse_messages_html = function(response) {

    M.snap_message_badge.totalmessages = response.totalmessages;

    var existingMessageContainer = Y.one('.alert_stream .message_badge_container .message_badge_container .message_badge_overlay .message_badge_messages');
    if (!existingMessageContainer) {
        Y.one('.message_badge_container').append(response.messages);
    } else {
        var tmpNode = Y.Node.create('<div></div>').append(response.messages);
        var newMessages = tmpNode.all('.message_badge_message');
        existingMessageContainer.append(newMessages);
    }

    var container = Y.one('.message_badge_container');

    // Remove loading status.
    var loadingstat = container.one('.loadingstat').remove(true);

    var overlayNode = Y.one('.message_badge_overlay');

    // We must make visible before rendering - messes up positioning
    overlayNode.removeClass('message_badge_hidden');

    if (newMessages) {
        // Process the new messages.
        var procmessages = newMessages;
    } else {
        // Process all of the messages.
        var procmessages = overlayNode.all('.message_badge_message');
    }
    // Process messages.
    procmessages.each(function(node) {
        M.snap_message_badge.init_message(Y, node);
    });

    // Activate help icons
    M.snap_message_badge.init_help_icons(Y);

    if (typeof callback == 'function') {
        callback();
    }

    M.snap_message_badge.apply_moremessagesbutton();
}

M.snap_message_badge.apply_moremessagesbutton = function (){
    var moremessages = Y.one('#badge_moremessages');
    if (moremessages == null) {
        var moremessages = Y.Node.create('<div id="badge_moremessages"><a href="#" class="btn btn-primary">' +
            M.util.get_string('more', 'theme_snap') + '</a></div>');

        Y.one('.message_badge_container').insert(moremessages, 'after');

        Y.one('#badge_moremessages .btn').on('click', function(e){

            // Increment offset by unmber of messages to get perrequest.
            M.snap_message_badge.offset += M.snap_message_badge.perrequest;
            if (M.snap_message_badge.offset > M.snap_message_badge.totalmessages) {
                M.snap_message_badge.offset = M.snap_message_badge.totalmessages;
            }

            M.snap_message_badge.get_messages_html(Y, M.snap_message_badge.onresponse_messages_html);

            e.preventDefault();
            return false;
        });

    }
    if ((M.snap_message_badge.offset + M.snap_message_badge.perrequest) >= M.snap_message_badge.totalmessages){
        // hide moremessages button
        Y.one(moremessages).hide();
    }
}

/**
 * Fetch messages HTML and add it to the DOM
 *
 * @param Y
 */
M.snap_message_badge.get_messages_html = function(Y, onsuccess) {

    // Lets have a loading status.
    var container = Y.one('.message_badge_container');
    var loadingstat = Y.Node.create('<div class="loadingstat three-quarters">' + Y.Escape.html(M.util.get_string('loading', 'theme_snap')) + '</div>');
    container.append(loadingstat);

    Y.io(M.cfg.wwwroot + '/message/output/badge/view.php?controller=ajax&action=getmessages&maxmessages=' + M.snap_message_badge.perrequest + '&offset=' + M.snap_message_badge.offset, {
        on: {
            success: function(id, o) {
                var response = Y.JSON.parse(o.responseText);
                if (response.error != undefined) {
                    M.snap_message_badge.responseBestErrorMessage(response.error);
                } else {
                    if (typeof onsuccess == 'function') {
                        onsuccess(response);
                    }
                }
            },
            failure: function(o) {
                // Do not spit out an error message as it is highly likely that the user has just navigated away from
                // the page as per the following article:
                // http://stackoverflow.com/questions/6910291/change-page-in-the-middle-of-ajax-request
                // exit function if no response headers present
                if (!o.getAllResponseHeaders()){
                    return;
                }
                M.snap_message_badge.alert_error(M.str.message_badge.genericasyncfail);
            }
        }
    });
};

/**
 * Make Moodle help icons active that come back through JSON response
 *
 * @param Y
 */
M.snap_message_badge.init_help_icons = function(Y) {
    M.core.init_popuphelp([]);
};
