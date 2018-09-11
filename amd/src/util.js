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
 * @copyright Copyright (c) 2016 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var staticSupportsSessionStorage = null;

    /**
     * General utilities library.
     */
    return {
        /**
         * On function evaluating true.
         *
         * @param {function} func
         * @param {function} callBack
         * @param {boolean} forceCallBack
         * @param {number} maxIterations
         * @param {number} i
         */
        whenTrue: function(func, callBack, forceCallBack, maxIterations, i) {
            maxIterations = !maxIterations ? 10 : maxIterations;
            i = !i ? 0 : i + 1;
            if (i > maxIterations) {
                // Error, too long waiting for function to evaluate true.
                if (forceCallBack) {
                    callBack();
                }
                return;
            }
            if (func()) {
                callBack();
            } else {
                var self = this;
                window.setTimeout(function() {
                    self.whenTrue(func, callBack, forceCallBack, maxIterations, i);
                }, 200);
            }
        },

        /**
         * Scroll a specific dom element into the viewport.
         * @param {Object} el
         */
        scrollToElement: function(el) {
            var navheight = $('#mr-nav').outerHeight();

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
            }, 600);
        },

        /**
         * Does the browser support session storage?
         * @returns {null|bool}
         */
        supportsSessionStorage: function() {
            if (staticSupportsSessionStorage !== null) {
                return staticSupportsSessionStorage;
            }
            if (typeof window.sessionStorage === 'object') {
                try {
                    window.sessionStorage.setItem('sessionStorage', 1);
                    window.sessionStorage.removeItem('sessionStorage');
                    staticSupportsSessionStorage = true;
                } catch (e) {
                    staticSupportsSessionStorage = false;
                }
            }
            return staticSupportsSessionStorage;
        }
    };
});
