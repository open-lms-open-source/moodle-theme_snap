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
 * Repository for theme_snap web services.
 * Centralizes all AJAX requests for the Snap theme.
 *
 * @module     theme_snap/repository
 * @copyright  Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Get the completion progress for a course section or all sections.
 *
 * @param {number} sectionid Section ID (0 or null to get all sections)
 * @param {number} userid User ID
 * @param {number} courseid Course ID
 * @return {Promise} Resolved with section progress data
 */
export const getSectionProgress = (sectionid, userid, courseid) => {
    const request = {
        methodname: 'theme_snap_get_course_section_progress',
        args: {
            sectionid: sectionid || 0,
            userid: userid,
            courseid: courseid,
        },
    };

    return Ajax.call([request])[0];
};

/**
 * Update the course TOC progress bar when completion status changes.
 *
 * @param {number} userid User ID
 * @param {number} courseid Course ID
 * @return {Promise} Resolved with course progress data
 */
export const updateCourseTocProgressBar = (userid, courseid) => {
    const request = {
        methodname: 'theme_snap_update_course_toc_progressbar',
        args: {
            userid: userid,
            courseid: courseid,
        },
    };

    return Ajax.call([request])[0];
};