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
 * @package   theme_n2018
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2016 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/templates'],
    function($, templates) {
        var FooterAlert = function() {

            // Container.
            var containerEl;

            /**
             * Initialising function.
             */
            (function() {
                containerEl = $('#n2018-footer-alert');

                // If the move notice html was not output to the dom via php, then we need to add it here via js.
                // This is necessary for the front page which does not have a renderer that we can override.
                if (containerEl.length === 0) {
                    templates.render('theme_n2018/footer_alert', {})
                        .done(function(result) {
                            $('#region-main').append(result);
                            containerEl = $('#n2018-footer-alert');
                        });
                }
            })();

            /**
             * Set title element html.
             * @param {string} titleHTML
             */
            this.setTitle = function(titleHTML) {
                $('.n2018-footer-alert-title').html(titleHTML);
                this.setSrNotice('');
                    // Focus on container so that it get's red out for accessibility reasons.
                containerEl.focus();
            };

            /**
             * Set screen reader notice.
             * @param {string} srText
             */
            this.setSrNotice = function(srText) {
                containerEl.find('p.sr-only').html(srText);
            };

            /**
             * Add AJAX loading spinner.
             */
            this.addAjaxLoading = function(str) {
                str = !str ? M.util.get_string('loading', 'theme_n2018') : str;
                var titleEl = $('.n2018-footer-alert-title');
                if (titleEl.find('.loadingstat').length === 0) {
                    titleEl.append('<span class="loadingstat spinner-three-quarters' +
                        '">' + str + '</span>');
                }
            };

            /**
             * Remove AJAX loading spinner.
             */
            this.removeAjaxLoading = function() {
                containerEl.find('.loadingstat').remove();
            };

            /**
             * Show footer alert.
             */
            this.show = function(onCancel) {
                containerEl.addClass('n2018-footer-alert-visible');
                if (typeof(onCancel) === 'function') {
                    $('.n2018-footer-alert-cancel').click(onCancel);
                    $('.n2018-footer-alert-cancel').addClass('state-visible');
                } else {
                    $('.n2018-footer-alert-cancel').removeClass('state-visible');
                }
            };

            /**
             * Hide footer alert.
             */
            this.hide = function() {
                containerEl.removeClass('n2018-footer-alert-visible');
                $('.n2018-footer-alert-cancel').removeClass('state-visible');
            };

            /**
             * Hide footer alert and reset content.
             */
            this.hideAndReset = function() {
                this.removeAjaxLoading();
                this.setTitle('');
                this.setSrNotice('');
                this.hide();
            };
        };
        return new FooterAlert();
    }
);
