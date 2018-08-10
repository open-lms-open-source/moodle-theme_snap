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
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module theme_snap/conversation_badge_count-lazy
 */
define(['jquery', 'core/ajax'],
    function($, Ajax) {
        var self = this;

        /**
         * Initialising function.
         * @param {int} userid The user id
         */
        self.init = function(userid) {
            self.userid = userid;
            // Container.
            self.containerEl = $('.conversation_badge_count');
            self.queryCount();
        };

        /**
         * Count the number of unread conversations (one or more messages from a user)
         * for a given user.
         *
         * @param {object} args The request arguments:
         * @return {object} jQuery promise
         */
        self.countUnreadConversations = function(args) {
            var request = {
                methodname: 'core_message_get_unread_conversations_count',
                args: args
            };

            var promise = Ajax.call([request])[0];

            promise.fail(self.resetCount);

            return promise;
        };

        /**
         * Query message repository for conversation count.
         */
        self.queryCount = function() {
            self.countUnreadConversations({useridto: self.userid}).then(self.updateCount);
        };

        /**
         * Updates the badge conversation count.
         * @param {int} count
         */
        self.updateCount = function(count) {
            if (count > 0) {
                self.containerEl.text(count);
                self.containerEl.removeClass("hidden");
            } else {
                self.containerEl.text('');
                self.containerEl.addClass("hidden");
            }
        };

        /**
         * Resets the count to 0
         */
        self.resetCount = function() {
            self.updateCount(0);
        };

        return {
            init: self.init
        };
    }
);
