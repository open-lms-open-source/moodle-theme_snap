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
        // Change TOC active section styles.
        setTOCVisibleSection();
        // Filter hidden activities.
        filterHiddenActivitiesFromDOM();
    }
    /**
     * Refresh a section cm list.
     *
     * @param {object} param
     * @param {Object} param.element
     */
    _refreshSectionCmlist({element}) {
        super._refreshSectionCmlist({element});
        // Filter hidden activities when section is refresh.
        filterHiddenActivitiesFromDOM();
    }
}