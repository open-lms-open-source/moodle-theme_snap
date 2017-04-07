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
 * Main responsive video function.
 */
define(['jquery', 'media_videojs/loader'], function($, videoJsLoader) {

    /**
     * Apply responsive video to non HTML5 video elements.
     *
     * @author Guy Thomas
     */
    var ResponsiveVideo = function() {

        /**
         * Get selectors for retrieving elements suitable for responsive code.
         * @returns {string}
         */
        var getNodeSelectors = function() {
            var supportedSites = [
                'youtube.com',
                'youtu.be',
                'vimeo.com',
                'archive.org/embed',
                'youtube-nocookie.com',
                'embed.ted.com',
                'embed-ssl.ted.com',
                'kickstarter.com',
                'video.html',
                'simmons.tegrity.com',
                'dailymotion.com'
            ];
            var selectors = [
                '.mediaplugin object:not(.media-responsive object, .felement.feditor object, .vjs-tech)',
                '.mediaplugin embed:not(.media-responsive embed, .felement.feditor embed, .vjs-tech)'
            ];
            for (var s in supportedSites) {
                var site = supportedSites[s];
                selectors.push('iframe:not(.media-responsive iframe, .felement.feditor iframe, .vjs-tech)[src*="' + site + '"]');
            }
            var joined = selectors.join(',');
            return joined;
        };

        /**
         * Make non vjs-tech iframes, etc responsive.
         * @param nodes
         */
        var makeResponsive = function(nodes, onComplete) {
            if (!nodes){
                nodes = $(getNodeSelectors());
            }

            $(nodes).each(function() {
                var width,
                    height,
                    aspectRatio;

                var parent = $(this).parent();

                var tagName = this.tagName.toLowerCase();
                if (tagName === 'iframe') {

                    if ($(parent).hasClass('media-responsive')) {
                        return true;
                    }

                    if (!$(parent).hasClass('mediaplugin')) {
                        // This iframe will need a new parent as we need a container we can rely on to just contain
                        // This one iframe.
                        var newParent = $('<div></div>');
                        $(this).after(newParent);
                        $(newParent).append($(this));
                        parent = newParent;
                    }
                }

                // Calculate aspect ratio.
                width = $(this).attr('width') || $(this).width();
                height = $(this).attr('height') || $(this).height();

                // If only the width or height contains percentages then we can't use it and will have to fall back
                // on offsets.
                if (!isNaN(width) || !isNaN(height)) {
                    width += ' ';
                    height += ' ';
                }
                if (width.indexOf('%') > -1 && height.indexOf('%') == -1
                    || width.indexOf('%') == -1 && height.indexOf('%') > -1
                ) {
                    width = $(this).width();
                    height = $(this).height();
                }

                width = parseInt(width);
                height = parseInt(height);
                aspectRatio = height / width;

                if (tagName === 'iframe') {
                    // Remove attributes.
                    $(this).removeAttr('width');
                    $(this).removeAttr('height');
                }

                // Make sure parent has a padding element.
                if (!parent.find('.media-responsive-pad').length) {
                    var aspectPerc = aspectRatio * 100;
                    var responsivePad = '<div class="media-responsive-pad" style="padding-top:' + aspectPerc + '%"></div>';
                    parent.append(responsivePad);
                }

                // Add responsive class to parent element.
                parent.addClass('media-responsive');
            });
            if (typeof(onComplete) === 'function') {
                onComplete();
            }
        };

        this.init = function() {
            /**
             * Apply a mutation observer to track oembed-content being dynamically added to the page.
             */
            var responsiveContentOnInsert = function() {
                /**
                 * Return nodes to process.
                 * @param {opbject} node (dom element)
                 * @returns {boolean}
                 */
                var nodesToProcess = function(node) {
                    if (!node.tagName) {
                        return false;
                    }
                    var selectors = getNodeSelectors();
                    if ($(node).is(selectors)) {
                        return $(node);
                    } else {
                        return $(node).find(selectors);
                    }
                };

                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        for (var n in mutation.addedNodes) {
                            var node = mutation.addedNodes[n];
                            var nodes = nodesToProcess(node);
                            if (nodes) {
                                videoJsLoader.setUp();
                                // Only apply responsive content to the newly added node for efficiency.
                                makeResponsive(nodes);
                            }
                        }
                    });
                });

                var observerConfig = {
                    attributes: false,
                    childList: true,
                    characterData: false,
                    subtree: true
                };

                // Note: Currently observing mutations throughout the document body - We might want to limit scope for
                // observation at some point in the future.
                var targetNode = document.body;
                observer.observe(targetNode, observerConfig);
            };

            $(document).ready(function() {
                // Call responsive content on dom ready, to catch things that existed prior to mutation observation.
                makeResponsive(null, responsiveContentOnInsert);
            });
        };
    };

    return new ResponsiveVideo();
});
