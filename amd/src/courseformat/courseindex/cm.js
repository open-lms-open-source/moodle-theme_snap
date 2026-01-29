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
 * Course index cm component.
 * Override Core behavior from course/format/amd/src/local/courseindex/cm.js
 *
 * @module     theme_snap/courseformat/courseindex/cm
 * @class      theme_snap/courseformat/courseindex/cm
 * @copyright  Copyright (c) 2026 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseCmComponent from 'core_courseformat/local/courseindex/cm';

export default class Component extends BaseCmComponent {

    /**
     * Update a course index cm using the state information.
     *
     * @param {object} param
     * @param {Object} param.element details the update details.
     */
    _refreshCm({element}) {
        super._refreshCm({element});
        // Update title attribute on Snap.
        this.getElement(this.selectors.CM_NAME).title = element.name;
    }
}