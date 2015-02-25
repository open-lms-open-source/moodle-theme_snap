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
 * Local Tests
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\tests;

use theme_snap\local;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   theme_snap
 * @copyright Copyright (c) 2013 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_local_test extends \advanced_testcase {

    public function test_get_course_color() {
        $actual = local::get_course_color(1);
        $this->assertSame('c4ca42', $actual);

        $actual = local::get_course_color(10);
        $this->assertSame('d3d944', $actual);

        $actual = local::get_course_color(100);
        $this->assertSame('f89913', $actual);

        $actual = local::get_course_color(1000);
        $this->assertSame('a9b7ba', $actual);
    }

    public function test_simpler_time() {
        $testcases = array (
            1 => 1,
            22 => 22,
            33 => 33,
            59 => 59,
            60 => 60,
            61 => 60,
            89 => 60,
            90 => 120,
            91 => 120,
            149 => 120,
            150 => 180,
            151 => 180,
            1234567 => 1234560,
        );

        foreach ($testcases as $input => $expected) {
            $actual = local::simpler_time($input);
            $this->assertSame($expected, $actual);
        }
    }

    public function test_relative_time() {

        $timetag  = array(
            'tag' => 'time',
            'attributes' => array(
                'is' => 'relative-time',
            ),
        );

        $actual = local::relative_time(time());
        $this->assertTag($timetag + ['content' => 'now'], $actual);

        $onesecbeforenow = time() - 1;

        $actual = local::relative_time($onesecbeforenow);
        $this->assertTag($timetag + ['content' => '1 sec ago'], $actual);

        $relativeto = date_timestamp_get(date_create("01/01/2001"));

        $onesecago = $relativeto - 1;

        $actual = local::relative_time($onesecago, $relativeto);
        $this->assertTag($timetag + ['content' => '1 sec ago'], $actual);

        $oneminago = $relativeto - 60;

        $actual = local::relative_time($oneminago, $relativeto);
        $this->assertTag($timetag + ['content' => '1 min ago'], $actual);
    }

    public function test_sort_graded() {
        $time = time();
        $oldertime = $time - 100;
        $newertime = $time + 100;

        $older = new \StdClass;
        $older->opentime = $oldertime;
        $older->closetime = $oldertime;
        $older->coursemoduleid = 123;

        $newer = new \StdClass;
        $newer->opentime = $newertime;
        $newer->closetime = $newertime;
        $newer->coursemoduleid = 789;

        $actual = local::sort_graded($older, $newer);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($newer, $older);
        $this->assertSame(1, $actual);

        $olderopenonly = new \StdClass;
        $olderopenonly->opentime = $oldertime;
        $olderopenonly->coursemoduleid = 101;

        $neweropenonly = new \StdClass;
        $neweropenonly->opentime = $newertime;
        $neweropenonly->coursemoduleid = 102;

        $actual = local::sort_graded($olderopenonly, $newer);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($olderopenonly, $neweropenonly);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($neweropenonly, $older);
        $this->assertSame(1, $actual);

        $actual = local::sort_graded($neweropenonly, $olderopenonly);
        $this->assertSame(1, $actual);

        $one = new \StdClass;
        $one->opentime = $time;
        $one->closetime = $time;
        $one->coursemoduleid = 1;

        $two = new \StdClass;
        $two->opentime = $time;
        $two->closetime = $time;
        $two->coursemoduleid = 2;

        $actual = local::sort_graded($one, $two);
        $this->assertSame(-1, $actual);

        $actual = local::sort_graded($two, $one);
        $this->assertSame(1, $actual);

        // Everything equals itself.
        $events = [$older, $newer, $olderopenonly, $neweropenonly, $one, $two];
        foreach ($events as $event) {
            $actual = local::sort_graded($event, $event);
            $this->assertSame(0, $actual);
        }
    }

    public function test_extract_first_image() {

        $actual = local::extract_first_image('no image here');
        $this->assertFalse($actual);

        $html = '<img src="http://www.example.com/image.jpg" alt="example image">';
        $actual = local::extract_first_image($html);
        $this->assertSame('http://www.example.com/image.jpg', $actual['src']);
        $this->assertSame('example image', $actual['alt']);
    }

    public function test_no_upcoming_deadlines() {
        global $USER;

        $actual = local::upcoming_deadlines($USER->id);
        $expected = array();
        $this->assertSame($actual, $expected);

        $actual = local::deadlines();
        $expected = '<p>You have no upcoming deadlines.</p>';
        $this->assertSame($actual, $expected);
    }

    public function test_no_messages() {
        global $USER;

        $actual = local::get_user_messages($USER->id);
        $expected = array();
        $this->assertSame($actual, $expected);

        $actual = local::messages();
        $expected = '<p>You have no messages.</p>';
        $this->assertSame($actual, $expected);
    }

    public function test_no_grading() {
        $actual = local::grading();
        $expected = '<p>You have no submissions to grade.</p>';
        $this->assertSame($actual, $expected);
    }

    /**
     * Get poster resize file
     *
     * @return bool|\stored_file
     * @throws \Exception
     * @throws \dml_exception
     */
    protected function get_poster_resize_file() {
        $fs = \get_file_storage();
        $syscontextid = \context_system::instance()->id;
        $fullpath = "/$syscontextid/theme_snap/resizedposter/0/site-image.jpg";
        $resizefile = $fs->get_file_by_hash(sha1($fullpath));
        return ($resizefile);
    }

    protected function assert_poster_image_resized($fixturename, $imgcount = 0) {
        global $CFG;

        // Clean up previous resizes.
        $oldresizefile = $this->get_poster_resize_file();
        if ($oldresizefile) {
            return $oldresizefile->delete();
        }

        $syscontextid = \context_system::instance()->id;

        $ext = strtolower(pathinfo ($fixturename, PATHINFO_EXTENSION));
        $testimagename = 'testimage'.$imgcount.'.'.$ext;

        $filerecord = array(
            'contextid' => $syscontextid,
            'component' => 'theme_snap',
            'filearea'  => 'poster',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $testimagename,
        );

        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$fixturename;

        // Fake an upload of a poster image via the theme settings.
        $fs = \get_file_storage();
        $testfile = $fs->create_file_from_pathname($filerecord, $filepath);
        \set_config('poster', '/'.$testimagename, 'theme_snap');
        local::process_poster_image();

        $tfinfo = $testfile->get_imageinfo();

        // Only jpgs will create a site-image.jpg file
        if ($ext == 'jpg' && $tfinfo['width'] > 1380) {
            // If the test file is greater than 1380 pixels width then a resize should occur.
            $resizefile = $this->get_poster_resize_file();
            $this->assertInstanceOf('stored_file', $resizefile);
            // We also need to make sure that our poster css contains the rsized image url.
            $css = '[[setting:poster]]';
            $css = theme_snap_poster_css($css, $filepath);
            $this->assertContains('resizedposter', $css);

        } else {
            // Either this is not a jpeg or its a jpeg that should not be resized.
            $resizefile = $this->get_poster_resize_file();
            $this->assertFalse($resizefile);
            // We also need to make sure that our poster css doesn't contain the rsized image url.
            $css = '[[setting:poster]]';
            $css = theme_snap_poster_css($css, $filepath);
            $this->assertNotContains('resizedposter', $css);
        }
    }

    public function test_poster_image_upload() {

        $this->resetAfterTest(true);

        $this->assert_poster_image_resized('bpd_bikes.jpg', 1);
        $this->assert_poster_image_resized('bpd_bikes_small.jpg', 2);
        $this->assert_poster_image_resized('testpng.png', 1);
        $this->assert_poster_image_resized('testpng_small.png', 2);
        $this->assert_poster_image_resized('testgif.gif', 1);
        $this->assert_poster_image_resized('testgif_small.gif', 2);
    }

}
