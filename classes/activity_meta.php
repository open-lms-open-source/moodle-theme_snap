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

defined('MOODLE_INTERNAL') || die();

/**
 * Activity meta data.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property string $submittedstr - string to use when submitted
 * @property string $notsubmittedstr - string to use when not submitted
 * @property string $submitstrkey - language string key
 * @property string $draftstr - string for draft status
 * @property string $reopenedstr - string for reopened status
 * @property string $duestr - string for due date
 * @property string $overduestr - string for overdue status
 * @property int $timeopen - unix time stamp for time open
 * @property int $timeclose - unix time stamp for time closes
 * @property bool $isteacher - boolean for if meta data is intended for teacher
 * @property bool $submissionnotrequired - boolean, true if a submission is not required
 * @property bool $submitted - boolean, true if submission has been made
 * @property bool $draft - boolean, true if activity submission is in draft status
 * @property int $timesubmitted - unix time stamp for time submitted
 * @property bool $grade - has the submission been graded
 * @property bool $overdue - is the submission overdue
 * @property int $numsubmissions - number of submissions
 * @property int $numrequiregrading - number of submissions requiring grading
 * @property-read bool $_empty - is the activity_meta data empty
 */
class activity_meta {

    // Strings.
    protected $submittedstr;
    protected $notsubmittedstr;
    protected $submitstrkey;
    protected $draftstr;
    protected $reopenedstr;
    protected $duestr;
    protected $overduestr;

    // General meta data.
    protected $timeopen;
    protected $timeclose;
    protected $isteacher = false;
    protected $submissionnotrequired = false;

    // Student meta data.
    protected $submitted = false; // Consider collapsing this variable + draft variable into one 'status' variable?
    protected $draft = false;
    protected $reopened = false;
    protected $timesubmitted;
    protected $grade = false;
    protected $overdue = false;

    // Teacher meta data.
    protected $numsubmissions = 0;
    protected $numrequiregrading = 0;

    // Empty - nothing has been set.
    protected $_empty = true;

    public function __construct() {
        // Set default strings.
        $this->overduestr = get_string('overdue', 'theme_snap');
        $this->duestr = get_string('due', 'theme_snap');
    }

    /**
     * Magic method for setting.
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        if (stripos($name, '_') === 0) {
            throw new \coding_exception('Attempt to set read only protected property $'.$name);
        }
        $this->_empty = false;
        $this->$name = $value;
    }

    /**
     * Magic method for getting.
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (property_exists ($this, $name)) {
            return $this->$name;
        }
        throw new \coding_exception('Attempt to get non existent property '.$name);
    }
}
