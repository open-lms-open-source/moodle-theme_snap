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

/**
 * Course toc section
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\renderables;

use theme_snap\output\shared;

class course_toc_progress {

    /**
     * @var bool - does this section have progress?
     */
    public $hasprogress = false;

    /**
     * @var stdClass - {complete, total}
     */
    public $progress;

    /**
     * @var bool - completed?
     */
    public $completed;

    /**
     * @var string
     */
    public $pixcompleted;

    /**
     * Set properties from course and section.
     * @param \stdClass $course
     * @param \stdClass $section
     */
    public function __construct($course, $section) {
        global $OUTPUT;

        static $compinfos = [];
        if (isset($compinfos[$course->id])) {
            $completioninfo = $compinfos[$course->id];
        } else {
            $completioninfo = new \completion_info($course);
            $compinfos[$course->id] = $completioninfo;
        }
        
        if (!$completioninfo->is_enabled()) {
            return ''; // Completion tracking not enabled.
        }

        $sac = shared::section_activity_summary($section, $course, null);
        if (empty($sac->progress)) {
            return;
        }

        $this->hasprogress = true;
        $this->progress = $sac->progress;
        $this->pixcompleted = $OUTPUT->pix_url('i/completion-manual-y');
        $this->completed = $sac->progress->complete === $sac->progress->total;
    }
}

