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
 * @package
 * @author    David Castro <david.castro@openlms.net>
 * @copyright Copyright (c) 2018 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module theme_snap/rearrange_tab_handler-lazy
 */
define(function() {
        /**
         * Applies tab and shift+tab event handling so focus is fixed.
         *
         * @param {(jQuery)} currNode
         * @param {(jQuery)} nextNode
         * @param {(jQuery)} prevNode
         */
        var applyTabOrder = function(currNode, nextNode, prevNode) {
            currNode.on('keydown', function(event) {
                var code = event.keyCode || event.which;
                if (code === 9) {
                    if (!event.shiftKey && nextNode !== undefined) {
                        event.preventDefault();
                        nextNode.focus();
                    } else if (event.shiftKey && prevNode !== undefined) {
                        event.preventDefault();
                        prevNode.focus();
                    }
                }
            });
        };

        return {
            /**
             * Initialising function.
             * @param {array} nodes
             */
            init: function(nodes) {
                for (var i = 0; i < nodes.length; i++) {
                    applyTabOrder(nodes[i], nodes[i + 1], nodes[i - 1]);
                }
            }
        };
    }
);
