// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * Course index main component.
 * Override Core behavior from course/format/amd/src/local/courseindex/courseindex.js
 *
 * @module     theme_snap/courseformat/courseindex/courseindex
 * @copyright Copyright (c) 2026 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseSectionComponent from 'core_courseformat/local/courseindex/courseindex';
import {setTOCVisibleSection} from 'theme_snap/section_asset_management';
import {getHiddenTocActivities} from "theme_snap/repository";

export default class Component extends BaseSectionComponent {

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
     */
    stateReady(state) {
        super.stateReady(state);
        setTOCVisibleSection();
        this._filterHiddenActivitiesFromDOM();
        // In tiles format, handle anchor-link scrolling from the course index.
        this._initTilesAnchorScroll();
    }

    /**
     * Refresh a section cm list.
     *
     * Re-applies the TOC hidden filter immediately with the cached list, then
     * fetches the current list from the server to catch any newly duplicated
     * activities whose cmid was not in Config.hiddenTocActivities at page load.
     *
     * @param {object} param
     * @param {Object} param.element
     */
    _refreshSectionCmlist({element}) {
        super._refreshSectionCmlist({element});
        this._filterHiddenActivitiesFromDOM();

        const config = require('core/config');
        getHiddenTocActivities(config.courseId)
            .then(data => {
                config.hiddenTocActivities = data.cmids;
                this._filterHiddenActivitiesFromDOM();
            })
            .catch(() => {
                // Non-critical: stale list stays in place; page reload will fix it.
            });
    }

    /**
     * Remove activities listed in Config.hiddenTocActivities from the course index DOM.
     */
    _filterHiddenActivitiesFromDOM() {
        const config = require('core/config');
        const hiddencmids = config.hiddenTocActivities || [];
        if (!hiddencmids.length) {
            return;
        }

        const courseindex = document.querySelector('#courseindex');
        if (!courseindex) {
            return;
        }

        hiddencmids.forEach(cmid => {
            const el = courseindex.querySelector(`[data-id="${cmid}"][data-for="cm"]`);
            if (el) {
                el.remove();
            }
        });
    }

    /**
     * Intercept course index label clicks in tiles courses and scroll smoothly
     * to the target element when it is already visible, avoiding a page reload.
     * When the target is hidden (tile closed) we return early and let format_tiles
     * open the tile via AJAX.
     */
    _initTilesAnchorScroll() {
        if (!document.body.classList.contains('format-tiles')) {
            return;
        }
        const courseIndex = document.getElementById('courseindex');
        courseIndex.addEventListener('click', (e) => {
            const anchorEl = this._resolveTilesAnchorEl(e);
            if (!anchorEl) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();

            const tileSection = anchorEl.closest('li.section.state-visible');
            if (tileSection) {
                this._scrollAfterTileAnimation(anchorEl, tileSection);
            } else {
                this._closeOpenTile();
                anchorEl.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        }, true);
    }

    /**
     * Returns the anchor element for a cm_name click, or null if Snap should
     * not handle it (no anchor, element absent or hidden inside a closed tile).
     * @param {MouseEvent} e
     * @returns {HTMLElement|null}
     */
    _resolveTilesAnchorEl(e) {
        const link = e.target.closest('a.courseindex-link[data-for="cm_name"]');
        if (!link) {
            return null;
        }
        const href = link.getAttribute('href');
        if (!href?.includes('#')) {
            return null;
        }
        const anchorId = href.startsWith('#') ? href.substring(1) : href.split('#')[1];
        const anchorEl = anchorId ? document.getElementById(anchorId) : null;
        // The getClientRects() is empty when the element is hidden (closed tile).
        return anchorEl?.getClientRects().length ? anchorEl : null;
    }

    /**
     * Scroll to anchorEl inside an open tile, waiting for a running slideDown
     * animation to finish first.
     * Detection: format_tiles sets an inline height during slideDown and clears
     * it on completion; a MutationObserver fires the scroll at that moment.
     * @param {HTMLElement} anchorEl
     * @param {HTMLElement} tileSection
     */
    _scrollAfterTileAnimation(anchorEl, tileSection) {
        const doScroll = () => anchorEl.scrollIntoView({behavior: 'smooth', block: 'center'});
        if (tileSection.style.height !== '') {
            const observer = new MutationObserver(() => {
                if (tileSection.style.height === '') {
                    observer.disconnect();
                    doScroll();
                }
            });
            observer.observe(tileSection, {attributes: true, attributeFilter: ['style']});
        } else {
            doScroll();
        }
    }

    /**
     * Close the open tile via its close button so format_tiles cleans up its
     * internal state. Falls back to hiding the overlay if no button is found.
     */
    _closeOpenTile() {
        const closeBtn = document.querySelector('li.section.state-visible .closesectionbtn');
        if (closeBtn) {
            closeBtn.click();
            return;
        }
        const overlay = document.getElementById('format_tiles_overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
}
