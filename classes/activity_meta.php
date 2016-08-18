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

use \theme_snap\traits\null_object;

defined('MOODLE_INTERNAL') || die();

/**
 * Activity meta data.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_meta {

    use null_object;

    // Strings.
    /**
     * @var string $submittedstr - string to use when submitted
     */
    public $submittedstr;
    /**
     * @var string $notsubmittedstr - string to use when not submitted
     */
    public $notsubmittedstr;
    /**
     * @var string $submitstrkey - language string key
     */
    public $submitstrkey;
    /**
     * @var string $draftstr - string for draft status
     */
    public $draftstr;
    /**
     * @var string $reopenedstr - string for reopened status
     */
    public $reopenedstr;
    /**
     * @var string $duestr - string for due date
     */
    public $duestr;
    /**
     * @var string $overduestr - string for overdue status
     */
    public $overduestr;

    // General meta data.
    /**
     * @var int $timeopen - unix time stamp for time open
     */
    public $timeopen;
    /**
     * @var int $timeclose - unix time stamp for time closes
     */
    public $timeclose;
    /**
     * @var bool $isteacher - true if meta data is intended for teacher
     */
    public $isteacher = false;
    /**
     * @var bool $submissionnotrequired - true if a submission is not required
     */
    public $submissionnotrequired = false;

    // Student meta data.
    /**
     * @var bool $submitted - true if submission has been made
     */
    public $submitted = false; // Consider collapsing this variable + draft variable into one 'status' variable?
    /**
     * @var bool $draft - true if activity submission is in draft status
     */
    public $draft = false;
    /**
     * @var bool $reopened - true if reopened
     */
    public $reopened = false;
    /**
     * @var int $timesubmitted - unix time stamp for time submitted
     */
    public $timesubmitted;
    /**
     * @var bool $grade - has the submission been graded
     */
    public $grade = false;
    /**
     * @var bool $overdue - is the submission overdue
     */
    public $overdue = false;

    // Teacher meta data.
    /**
     * @var int $numsubmissions - number of submissions
     */
    public $numsubmissions = 0;
    /**
     * @var int $numrequiregrading - number of submissions requiring grading
     */
    public $numrequiregrading = 0;

    public function __construct() {
        // Set default strings.
        $this->set_default('overduestr', get_string('overdue', 'theme_snap'));
        $this->set_default('duestr', get_string('due', 'theme_snap'));
    }
}
