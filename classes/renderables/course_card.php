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
 * Course card renderable
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\renderables;

use theme_snap\services\course;
use theme_snap\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/coursecatlib.php');

class course_card implements \renderable {

    /**
     * @var \stdClass $course
     */
    private $course;

    /**
     * @var course service
     */
    private $service;

    /**
     * @var course_card $model
     * (should be set to json encoded version of $this);
     */
    public $model;

    /**
     * @var int $courseid
     */
    public $courseid;

    /**
     * @var string $fullname
     */
    public $fullname;

    /**
     * @var string $shortname
     */
    public $shortname;

    /**
     * @var bool
     */
    public $favorited;

    /**
     * @var array
     */
    public $visibleavatars;

    /**
     * @var array
     */
    public $hiddenavatars;

    /**
     * @var int
     */
    public $hiddenavatarcount;

    /**
     * @var bool
     */
    public $showextralink = false;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $imagecss;

    /**
     * @var bool
     */
    public $published = true;

    /**
     * @var string
     */
    public $toggletitle = '';

    /**
     * @param int $courseid
     * @param course | null $service
     */
    public function __construct($courseid, course $service = null) {
        $this->courseid = $courseid;
        $this->service = $service ? : course::service();
        $this->apply_properties();
        $this->model = $this;
    }

    /**
     * Set props.
     */
    private function apply_properties() {
        global $DB;
        $this->course = $DB->get_record('course', ['id' => $this->courseid]);
        $this->url = new \moodle_url('/course/view.php', ['id' => $this->course->id]) . '';
        $this->shortname = $this->course->shortname;
        $this->fullname = $this->course->fullname;
        $this->published = (bool)$this->course->visible;
        $this->favorited = $this->service->favorited($this->courseid);
        $togglestrkey = !$this->favorited ? 'favorite' : 'favorited';
        $this->toggletitle = get_string($togglestrkey, 'theme_snap', $this->fullname);
        $this->apply_contact_avatars();
        $this->apply_image_css();
    }

    /**
     * Set image css for course card (cover image, etc).
     */
    private function apply_image_css() {
        $bgcolor = local::get_course_color($this->courseid);
        $this->imagecss = "background-color: #$bgcolor;";
        $bgimage = local::course_card_image_url($this->courseid);
        if (!empty($bgimage)) {
            $this->imagecss .= "background-image: url($bgimage);";
        }
    }

    /**
     * Set course contact avatars;
     */
    private function apply_contact_avatars() {
        global $DB, $OUTPUT;
        $clist = new \course_in_list($this->course);
        $teachers = $clist->get_course_contacts();
        $avatars = [];
        $blankavatars = [];

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacherids[] = $teacher['user']->id;
            }
            $teacherusers = $DB->get_records_list('user', 'id', $teacherids);

            foreach ($teachers as $teacher) {
                if (!isset($teacherusers[$teacher['user']->id])) {
                    continue;
                }
                $teacheruser = $teacherusers [$teacher['user']->id];
                $userpicture = new \user_picture($teacheruser);
                $userpicture->link = false;
                $userpicture->size = 100;
                $teacherpicture = $OUTPUT->render($userpicture);

                if (stripos($teacherpicture, 'defaultuserpic') === false) {
                    $avatars[] = $teacherpicture;
                } else {
                    $blankavatars[] = $teacherpicture;
                }
            }
        }

        // Let's put the interesting avatars first!
        $avatars = array_merge($avatars, $blankavatars);
        if (count($avatars) > 5) {
            // Show 4 avatars and link to show more.
            $this->visibleavatars = array_slice($avatars, 0, 4);
            $this->hiddenavatars = array_slice($avatars, 4);
            $this->showextralink = true;
        } else {
            $this->visibleavatars = $avatars;
            $this->hiddenavatars = [];
        }
        $this->hiddenavatarcount = count($this->hiddenavatars);
    }

    /**
     * This magic method is here purely so that doing strval($coursecard->model) yields a json encoded version of the
     * object that can be used in a template.
     * @return string
     */
    public function __toString() {
        unset($this->model);
        $retval = json_encode($this);
        $this->model = $this;
        return $retval;
    }
}
