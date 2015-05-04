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
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\tests;

use theme_snap\local;

defined('MOODLE_INTERNAL') || die();

/**
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_snap_local_test extends \advanced_testcase {

    public function test_grade_warning_debug_off() {
        global $CFG;

        $this->resetAfterTest();
        $CFG->debugdisplay = 0;

        $actual = local::skipgradewarning("warning text");
        $this->assertNull($actual);
    }

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
     * Imitates an admin setting the site cover image via the
     * Snap theme settings page. Creates a file, sets a theme
     * setting with the filname, then calls the callback triggered
     * by submitting the form.
     *
     * @param $fixturename
     * @return array
     * @throws \Exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function fake_site_image_setting_upload($filename) {
        global $CFG;

        $syscontext = \context_system::instance();

        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'theme_snap',
            'filearea'  => 'poster',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $filename,
        );

        $filepath = $CFG->dirroot.'/theme/snap/tests/fixtures/'.$filename;

        $fs = \get_file_storage();

        $fs->delete_area_files($syscontext->id, 'theme_snap', 'poster');

        $fs->create_file_from_pathname($filerecord, $filepath);
        \set_config('poster', '/'.$filename, 'theme_snap');

        local::process_coverimage($syscontext);
    }

    /**
     * Imitates an admin deleting the site cover image via the
     * Snap theme settings page. Deletes a file, sets a theme
     * setting to blank, then calls the callback triggered
     * by submitting the form.
     *
     * @param $fixturename
     * @return array
     * @throws \Exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function fake_site_image_setting_cleared() {
        $syscontext = \context_system::instance();
        $fs = \get_file_storage();

        $fs->delete_area_files($syscontext->id, 'theme_snap', 'coverimage');

        \set_config('poster', '', 'theme_snap');
        local::process_coverimage($syscontext);
    }

    public function test_poster_image_upload() {
        $this->resetAfterTest();

        $beforeupload = local::site_coverimage_original();
        $this->assertFalse($beforeupload);

        $fixtures = [
            'bpd_bikes_3888px.jpg' => true , // True means SHOULD get resized.
            'bpd_bikes_1381px.jpg' => true,
            'bpd_bikes_1380px.jpg' => false,
            'bpd_bikes_1379px.jpg' => false,
            'bpd_bikes_1280px.jpg' => false,
            'testpng.png' => false,
            'testpng_small.png' => false,
            'testgif.gif' => false,
            'testgif_small.gif' => false,
        ];

        foreach ($fixtures as $filename => $shouldberesized) {

            $this->fake_site_image_setting_upload($filename);

            $css = '[[setting:poster]]';
            $css = local::site_coverimage_css($css);

            $this->assertContains('/theme_snap/coverimage/', $css);

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $this->assertContains("/site-image.$ext", $css);

            if ($shouldberesized) {
                $image = local::site_coverimage();
                $finfo = $image->get_imageinfo();
                $this->assertSame(1280, $finfo['width']);
            }
        }

        $this->fake_site_image_setting_cleared();

        $css = '[[setting:poster]]';
        $css = local::site_coverimage_css($css);

        $this->assertSame('', $css);
        $this->assertFalse(local::site_coverimage());
    }
}
