<?php
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

namespace theme_snap;

use core_courseformat\local\sectionactions as core_sectionactions;

/**
 * Extends sectionactions from core to expose protected utilities.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2025 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sectionsactions extends core_sectionactions {

    /**
     * Expose get_last_section_number to snap.
     *
     * @param bool $includedelegated
     * @return int
     */
    public function get_last_section_number_public(bool $includedelegated = true): int {
        return parent::get_last_section_number($includedelegated);
    }
}