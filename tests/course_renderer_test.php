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
 * Tests for the Snap course renderer's front page course list.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2026 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap;

use context_system;

/**
 * Tests for \theme_snap\output\core\course_renderer::coursecat_courses().
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2026 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \theme_snap\output\core\course_renderer::coursecat_courses
 */
class course_renderer_test extends \advanced_testcase {

    /**
     * On the front page each course box must be wrapped in an <li>, and the whole set in a
     * <ul role="list">.
     */
    public function test_frontpage_course_list_wrap(): void {
        $this->resetAfterTest();

        $renderer = $this->make_stub_renderer('site-index');

        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(\core_course_renderer::COURSECAT_SHOW_COURSES_EXPANDED);
        $chelper->set_attributes(['class' => 'frontpage-course-list-all']);
        $courses = [(object) ['id' => 101], (object) ['id' => 102]];

        $output = $renderer->render_courses($chelper, $courses, count($courses));
        $xpath = $this->load_html_xpath($output);

        // Exactly one list, exposed to assistive technology as role="list".
        $lists = $xpath->query('//div[contains(@class, "frontpage-course-list-all")]/ul[@role="list"]');
        $this->assertSame(1, $lists->length, 'Expected a single ul[role="list"] wrapping the course boxes.');

        // One list item per course, each directly wrapping a single course box.
        $items = $xpath->query('//ul[@role="list"]/li[@role="listitem"]');
        $boxes = $xpath->query(
            '//ul[@role="list"]/li[@role="listitem"]'
            . '/div[contains(concat(" ", normalize-space(@class), " "), " coursebox ")]'
        );
        $this->assertSame(2, $items->length, 'Expected one list item per course.');
        $this->assertSame($items->length, $boxes->length, 'Each list item must wrap exactly one course box.');
    }

    /**
     * Build a Snap course renderer whose coursecat_coursebox() is stubbed with a lightweight placeholder.
     *
     * @param string $pagetype the page type to simulate (the wrapping is scoped to 'site-index')
     * @return \theme_snap\output\core\course_renderer
     */
    private function make_stub_renderer(string $pagetype): \theme_snap\output\core\course_renderer {
        $page = new \moodle_page();
        $page->set_context(context_system::instance());
        $page->set_url('/');
        $page->set_pagetype($pagetype);

        return new class($page, null) extends \theme_snap\output\core\course_renderer {
            /**
             * Public passthrough to the protected method under test.
             */
            public function render_courses(\coursecat_helper $chelper, array $courses, int $totalcount): string {
                return $this->coursecat_courses($chelper, $courses, $totalcount);
            }

            /**
             * Lightweight stub: render a course box without any filters/format_text.
             */
            protected function coursecat_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
                return \core\output\html_writer::div('Course ' . $course->id, trim('coursebox clearfix ' . $additionalclasses));
            }
        };
    }

    /**
     * Parse an HTML fragment into a DOMXPath for structural assertions.
     *
     * @param string $html
     * @return \DOMXPath
     */
    private function load_html_xpath(string $html): \DOMXPath {
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return new \DOMXPath($doc);
    }
}
