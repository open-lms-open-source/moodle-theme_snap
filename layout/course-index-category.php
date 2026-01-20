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
 * Layout - course-index-category.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2017 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/header.php');

$iscoursecat = $PAGE->context->contextlevel == CONTEXT_COURSECAT;

// @codingStandardsIgnoreStart
// Note, coding standards ignore is required so that we can have more readable indentation under php tags.

$mastimage = '';
// Check we are in a course (not the site level course), and the course is using a cover image.
if (!empty($coverimagecss)) {
    $mastimage = 'mast-image';
}
?>

<!-- moodle js hooks -->
<div id="page">
    <div id="page-content">
    <!--
    ////////////////////////// MAIN  ///////////////////////////////
    -->
        <div id="moodle-page" class="clearfix">
        <div id="page-header" class="clearfix snap-category-header <?php echo $mastimage; ?>">
        <nav class="breadcrumb-nav" aria-label="breadcrumbs"><?php echo $OUTPUT->navbar(); ?></nav>
            <?php
            $categories = $PAGE->categories;
            $cat = reset($categories);
            if (!empty($categories)) {
                $cat = reset($categories);
            }
            $manageurl = false;
            if (has_capability('moodle/category:manage', $PAGE->context)) {
                $manageurl = new \core\url('/course/management.php');
            }

            echo $OUTPUT->snap_page_header();
            ?>
        </div>
        <section id="region-main">
            <div class="d-inline-flex">
                <?php
                global $OUTPUT;
                $context = get_category_or_system_context(empty($cat) ? 0 : $cat->id);
                if($iscoursecat) {
                    if (has_capability('moodle/course:create', $context)) {
                        // Print link to create a new course, for the 1st available category.
                        if ($cat->id) {
                            $url = new \core\url('/course/edit.php', ['category' => $cat->id, 'returnto' => 'category']);
                        } else {
                            $url = new \core\url('/course/edit.php', ['category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat']);
                        }
                        echo '<div><a class="btn btn-secondary" href="' . $url . '">' .
                            get_string('addnewcourse', 'moodle') . '</a></div>';
                    }
                    if (has_capability('moodle/category:manage', $context)) {
                        $addsubcaturl = new \core\url('/course/editcategory.php', array('parent' => $cat->id));
                        echo '<div><a class="btn btn-secondary ms-3" href="' . $addsubcaturl . '">' .
                            get_string('addsubcategory', 'moodle') . '</a></div>';
                    }
                    if ($manageurl) {
                        echo '<p><a class="btn btn-secondary ms-3" href="' . $manageurl . '">';
                        echo get_string('managecourses', 'moodle') . '</a></p>';
                    }
                    if (!empty($editcatagory)) {
                        echo $editcatagory;
                    }
                    echo $OUTPUT->container_start('buttons ms-3');
                    if (\core_course_category::is_simple_site() == 1) {
                        snap_print_course_request_buttons(\context_system::instance());
                    } else {
                        snap_print_course_request_buttons($context);
                    }
                    echo $OUTPUT->container_end();
                    echo "</div>";
                }else {
                    if ($manageurl) {
                        echo '<p><a class="btn btn-secondary" href="' . $manageurl . '">';
                        echo get_string('managecourses', 'moodle') . '</a></p>';
                    }
                    if (has_capability('moodle/course:create', $context)) {
                        // Print link to create a new course, for the 1st available category.
                        $url = new \core\url('/course/edit.php', ['category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat']);
                        echo '<div><a class="btn btn-secondary ms-3" href="' . $url . '">' .
                            get_string('addnewcourse', 'moodle') . '</a></div>';
                    }
                    echo "</div>";
                }
                
                echo $OUTPUT->main_content();
                ?>
        </section>
        </div>
        <div id="moodle-blocks" class="clearfix">
            <?php echo $OUTPUT->custom_block_region('side-pre'); ?>
        </div>
        <?php
        require __DIR__.'/blocks_drawer.php';
        echo $OUTPUT->snap_feeds_side_menu();
        ?>
    </div>
</div>
<?php echo $OUTPUT->standard_after_main_region_html() ?>
    <!-- close moodle js hooks -->
<?php
// @codingStandardsIgnoreEnd
require(__DIR__.'/footer.php');
