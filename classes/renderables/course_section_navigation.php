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
 * Renderable for course section navigation.
 * @package   theme_snap
 * @author    Guy Thomas
 * @copyright Copyright (c) 2016 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\renderables;
use context_course;
use theme_snap\var_nodescription;

/**
 * Renderable class for course section navigation.
 * @package   theme_snap
 * @author    Guy Thomas
 * @copyright Copyright (c) 2016 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_section_navigation implements \core\output\renderable {

    /**
     * @var false|course_section_navigation_link previous section link
     */
    public $previous;

    /**
     * @var false|course_section_navigation_link next section link
     */
    public $next;

    /**
     * @var int sectionid
     */
    public $sectionid;

    /**
     * @var boolean issubsection
     */
    public $issubsection;

    /**
     * course_section_navigation constructor.
     * @param stdClass $course
     * @param section_info[] $sections
     * @param int $sectionno
     */
    public function __construct($course, $sections, $sectionno) {
        $course = course_get_format($course)->get_course();

        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
            || !$course->hiddensections;

        $navigablesections = $this->get_navigable_sections($sections, $sectionno);
        $sectionid = $sections[$sectionno]->id;

        $this->sectionid = $sectionid;
        $this->issubsection = $sections[$sectionno]->is_delegated();
        $this->previous = $this->find_navigation_link($course, $navigablesections, $sectionid, -1, $canviewhidden);
        $this->next     = $this->find_navigation_link($course, $navigablesections, $sectionid, 1, $canviewhidden);
    }

    /**
     * Returns an ordered array of navigable sections for the current section.
     *
     * @param section_info[] $sections Array of section_info objects for the course.
     * @param int $sectionno The current section number.
     * @return section_info[] Associative array of section_id => section_info, in navigable order.
     */
    private function get_navigable_sections(array $orderedsections, int $sectionno): array {
        $issubsection = $orderedsections[$sectionno]->is_delegated();

        $navigablesections = [];
        if (!$issubsection) {
            foreach ($orderedsections as $section) {
                /** @var \section_info $section */
                if (!$section->is_delegated()) {
                    $navigablesections[$section->id] = $section;
                }
            }
        } else {
            /** @var \section_info $parentsection */
            $parentsection = $orderedsections[$sectionno]->get_component_instance()->get_parent_section();
            if (!$parentsection) {
                return [];
            }

            // The sectionno may not match the actual order if subsections were moved, so cm order is used.
            foreach ($parentsection->get_sequence_cm_infos() as $cm) {
                /** @var \cm_info $cm */
                $customdata = $cm->get_custom_data();

                // The method get_custom_data() may return stdClass or null for some modules.
                if (!is_array($customdata)) {
                    continue;
                }
                if (isset($customdata['sectionid'])) {
                    $navigablesections[$customdata['sectionid']] = null;
                }
            }
            foreach ($orderedsections as $section) {
                if (array_key_exists($section->id, $navigablesections)) {
                    $navigablesections[$section->id] = $section;
                }
            }
        }

        return $navigablesections;
    }

    /**
     * Finds the previous or next navigation link relative to a given section.
     *
     * Iterates through $orderedsections in the given $direction until a visible or viewable section is found.
     *
     * @param stdClass $course The course object.
     * @param section_info[] $orderedsections Ordered array of sections.
     * @param int $sectionid The current section id.
     * @param int $direction Direction to search: -1 for previous, 1 for next.
     * @param bool $canviewhidden Whether the user can view hidden sections.
     * @return course_section_navigation_link|false The navigation link object, or false if none.
     */
    private function find_navigation_link($course, array $orderedsections, int $sectionid, int $direction, bool $canviewhidden) {
        $keys = array_keys($orderedsections);
        $currentindex = array_search($sectionid, $keys, true);
        if ($currentindex === false) {
            return false;
        }

        $index = $currentindex + $direction;
        while (isset($keys[$index])) {
            $target = $keys[$index];
            $section = $orderedsections[$target];

            if ($canviewhidden || $section->uservisible || $section->availableinfo) {
                $extraclasses = $section->visible ? '' : ' dimmed_text';
                $sectiontitle = get_section_name($course, $section);
                if ($sectiontitle === get_string('general')) {
                    $sectiontitle = get_string('introduction', 'theme_snap');
                }

                $url = course_get_url($course, $section->sectionnum, ['navigation' => true]);
                return new course_section_navigation_link($section->sectionnum, $extraclasses, $sectiontitle, $url);
            }

            $index += $direction;
        }

        return false;
    }
}
