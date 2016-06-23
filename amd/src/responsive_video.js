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
define(['jquery'], function($) {

    /**
     * Apply responsive video to non HTML5 video elements.
     *
     * @author Guy Thomas
     * @date 2014-06-09
     */
    var ResponsiveVideo = function() {
        this.apply = function() {
            // Should we be targeting all elements of this type, or should we be more specific?
            // E.g. for externally embedded video like youtube we have to go with iframes but what happens if there is
            // an iframe and it isn't a video iframe - it still gets processed by this script.
            $('.mediaplugin object, .mediaplugin embed, iframe').not("[data-iframe-srcvideo='value']").each(function() {
                var width,
                    height,
                    aspectratio;

                var tagname = this.tagName.toLowerCase();
                if (tagname === 'iframe') {
                    var supportedsites = [
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
                    var supported = false;
                    for (var s in supportedsites) {
                        if (this.src.indexOf(supportedsites[s]) > -1) {
                            supported = true;
                            break;
                        }
                    }
                    this.setAttribute('data-iframe-srcvideo', (supported ? '1' : '0'));
                    if (!supported) {
                        return true; // Skip as not supported.
                    }
                    // Set class.
                    $(this).parent().addClass('videoiframe');
                }

                aspectratio = this.getAttribute('data-aspect-ratio');
                if (aspectratio === null) { // Note, an empty attribute should evaluate to === null.
                    // Calculate aspect ratio.
                    width = this.width || this.offsetWidth;
                    width = parseInt(width);
                    height = this.height || this.offsetHeight;
                    height = parseInt(height);
                    aspectratio = height / width;
                    this.setAttribute('data-aspect-ratio', aspectratio);
                }

                if (tagname === 'iframe') {
                    // Remove attributes.
                    $(this).removeAttr('width');
                    $(this).removeAttr('height');
                }

                // Get width again.
                width = parseInt(this.offsetWidth);
                // Set height based on width and aspectratio
                var style = {height: (width * aspectratio) + 'px'};
                $(this).css(style);
            });
        };
    };
    
    return new ResponsiveVideo();
});
