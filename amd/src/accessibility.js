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
 * @author    Oscar Nadjar oscar.nadjar@blackboard.com
 * @copyright Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * JS code to assign attributes and expected behavior for elements in the Dom regarding accessibility.
 */
define(['jquery', 'core/str'],
    function($, str) {
        return {
            init: function() {

                str.get_strings([
                    {key : 'accesforumstringdis', component : 'theme_snap'},
                    {key : 'accesforumstringmov', component : 'theme_snap'},
                    {key : 'calendar', component : 'calendar'}
                ]).done(function(stringsjs) {
                    // Add aria label to some DOM elements
                    $("i.fa-calendar").parent().attr("aria-label", stringsjs[2]);
                    $("input[name='TimeEventSelector[calendar]']").attr('aria-label', stringsjs[2]);
                    if ($("#page-mod-forum-discuss")) {
                        $(".displaymode form select.custom-select").attr("aria-label", stringsjs[0]);
                        $(".movediscussion select.urlselect").attr("aria-label", stringsjs[1]);
                    }
                });

                // Add accessibility for the tabs.
                $(document).ready(function() {
                    new Tabpanel("snap-pm-accessible-tab");
                    new Tabpanel("modchooser-accessible-tab");
                });

                function Tabpanel(id) {
                    this._id = id;
                    this.$tpanel = $('#' + id);
                    this.$tabs = this.$tpanel.find('.tab');
                    this.$panels = this.$tpanel.find('.tab-pane');

                    this.bindHandlers();
                    this.init();
                }

                Tabpanel.prototype.keys = {
                    left: 37,
                    up: 38,
                    right: 39,
                    down: 40
                };

                Tabpanel.prototype.init = function() {
                    var $tab;

                    this.$panels.attr('aria-hidden', 'true');
                    this.$panels.removeClass('active in');

                    $tab = this.$tabs.filter('.active');

                    if ($tab === undefined) {
                        $tab = this.$tabs.first();
                        $tab.addClass('active');
                    }

                    this.$tpanel
                        .find('#' + $tab.find('a').attr('aria-controls'))
                        .addClass('active in').attr('aria-hidden', 'false');
                };

                Tabpanel.prototype.switchTabs = function($curTab, $newTab) {
                    var $curTabLink = $curTab.find('a'),
                        $newTabLink = $newTab.find('a');

                    $curTab.removeClass('active');
                    $curTabLink.attr('tabindex', '-1').attr('aria-selected', 'false');

                    $newTab.addClass('active');
                    $newTabLink.attr('aria-selected', 'true');

                    this.$tpanel
                        .find('#' + $curTabLink.attr('aria-controls'))
                        .removeClass('active in').attr('aria-hidden', 'true');

                    this.$tpanel
                        .find('#' + $newTabLink.attr('aria-controls'))
                        .addClass('active in').attr('aria-hidden', 'false');

                    $newTabLink.attr('tabindex', '0');
                    $newTabLink.focus();
                };

                Tabpanel.prototype.bindHandlers = function() {
                    var self = this;

                    this.$tabs.keydown(function(e) {
                        return self.handleTabKeyDown($(this), e);
                    });

                    this.$tabs.click(function(e) {
                        return self.handleTabClick($(this), e);
                    });
                };

                Tabpanel.prototype.handleTabKeyDown = function($tab, e) {
                    var $newTab, tabIndex;

                    switch (e.keyCode) {
                        case this.keys.left:
                        case this.keys.up: {

                            tabIndex = this.$tabs.index($tab);

                            if (tabIndex === 0) {
                                $newTab = this.$tabs.last();
                            }
                            else {
                                $newTab = this.$tabs.eq(tabIndex - 1);
                            }

                            this.switchTabs($tab, $newTab);

                            e.preventDefault();
                            return false;
                        }
                        case this.keys.right:
                        case this.keys.down: {

                            tabIndex = this.$tabs.index($tab);

                            if (tabIndex === this.$tabs.length-1) {
                                $newTab = this.$tabs.first();
                            }
                            else {
                                $newTab = this.$tabs.eq(tabIndex + 1);
                            }

                            this.switchTabs($tab, $newTab);

                            e.preventDefault();
                            return false;
                        }
                    }
                };

                Tabpanel.prototype.handleTabClick = function($tab) {
                    var $oldTab = this.$tpanel.find('.tab.active');
                    this.switchTabs($oldTab, $tab);
                };
            }
        };
    }
);
