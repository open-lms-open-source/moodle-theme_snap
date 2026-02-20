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
 * Additional settings in the course index.
 *
 * @module theme_snap/courseindex_adjustments
 * @copyright  Copyright (c) 2025 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get hidden TOC activities list from config.
 * @returns {number[]} Array of hidden course module IDs
 */
const getHiddenTocActivities = () => {
    const config = require('core/config');
    return config.hiddenTocActivities || [];
};

/**
 * Remove hidden activities from the course index DOM.
 */
export const filterHiddenActivitiesFromDOM = () => {
    const hiddencmids = getHiddenTocActivities();
    if (!hiddencmids || hiddencmids.length === 0) {
        return;
    }

    const courseindex = document.querySelector('#courseindex');
    if (!courseindex) {
        return;
    }

    // Remove activities that are marked as hidden.
    // Course index activities use data-id and data-for="cm" attributes.
    hiddencmids.forEach(cmid => {
        const activityElement = courseindex.querySelector(`[data-id="${cmid}"][data-for="cm"]`);
        if (activityElement) {
            activityElement.remove();
        }
    });
};
