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

/**
 * Activity meta data.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_meta {

    // Strings.
    public $submittedstr;
    public $notsubmittedstr;
    public $submitstrkey;
    public $draftstr;
    public $reopenedstr;
    public $duestr;
    public $overduestr;

    // General meta data.
    public $timeopen;
    public $timeclose;
    public $isteacher = false;
    public $submissionnotrequired = false;

    // Student meta data.
    public $submitted = false; // Consider collapsing this variable + draft variable into one 'status' variable?
    public $draft = false;
    public $reopened = false;
    public $timesubmitted;
    public $grade;
    public $overdue = false;

    // Teacher meta data.
    public $numsubmissions = false;
    public $numrequiregrading = false;

    function __construct() {
        // Set default strings.
        $this->overduestr = get_string('overdue', 'theme_snap');
        $this->duestr = get_string('due', 'theme_snap');
    }
}
