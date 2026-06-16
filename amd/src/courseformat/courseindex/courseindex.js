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
import {filterHiddenActivitiesFromDOM} from "theme_snap/courseindex_adjustments";

export default class Component extends BaseSectionComponent {

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
     */
    stateReady(state) {
        super.stateReady(state);
        setTOCVisibleSection();
        filterHiddenActivitiesFromDOM();
        // In tiles format, handle anchor-link scrolling from the course index.
        this._initTilesAnchorScroll();
    }

    /**
     * Refresh a section cm list.
     *
     * @param {object} param
     * @param {Object} param.element
     */
    _refreshSectionCmlist({element}) {
        super._refreshSectionCmlist({element});
        filterHiddenActivitiesFromDOM();
    }

    /**
     * For tiles-format courses, intercept course index label / anchor-link clicks
     * that target an already-visible section and scroll smoothly to the element.
     *
     * This listener runs in the capture phase so it fires before
     * format_tiles/course_mod_modal's bubbling listener.  When the section is
     * already visible we handle the scroll here and stop propagation so tiles
     * does not attempt any further navigation.  When the section still needs to
     * be expanded we do nothing and let tiles handle it via AJAX.
     */
    _initTilesAnchorScroll() {
        if (!document.body.classList.contains('format-tiles')) {
            return;
        }
        const courseIndex = document.getElementById('courseindex');
        courseIndex.addEventListener('click', (e) => {
            const link = e.target.closest('a.courseindex-link[data-for="cm_name"]');
            if (!link) {
                return;
            }

            const linkUrl = link.getAttribute('href');
            if (!linkUrl || !linkUrl.includes('#')) {
                return;
            }

            const anchorId = linkUrl.startsWith('#') ? linkUrl.substring(1) : linkUrl.split('#')[1];
            if (!anchorId) {
                return;
            }

            const anchorEl = document.getElementById(anchorId);
            if (!anchorEl) {
                return;
            }

            // Section is already visible: scroll to the anchor and prevent tiles
            // from doing any further navigation.
            e.preventDefault();
            e.stopPropagation();

            // Our stopPropagation() prevents the click from reaching tiles'
            // course.js document-level listener that normally hides the overlay.
            // Dismiss it here so the user is not left with a stale overlay.
            const overlay = document.getElementById('format_tiles_overlay');

            if (overlay) {
                overlay.style.display = 'none';
            }

            anchorEl.scrollIntoView({behavior: 'smooth', block: 'center',});
        }, true);
    }
}