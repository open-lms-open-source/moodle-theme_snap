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
        <main id="moodle-page" class="clearfix">
        <div id="page-header" class="clearfix snap-category-header <?php echo $mastimage; ?>">
        <div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->snapnavbar($mastimage); ?></div>
            <div id="page-mast">
            <?php
                $categories = $PAGE->categories;
                if (empty($categories)) {
                    $catname = get_string('courses', 'theme_snap');
                    $catname = format_text($catname);
                    echo '<h1>' . html_to_text(s($catname)) . '</h1>';
                } else {
                    // Get the current category name and description.
                    $cat = reset($categories);
                    $catid = $cat->id;
                    $catname = format_text($cat->name);
                    $catdescription = $cat->description;

                    // Category edit link.
                    $editcatagory = '';
                    if (can_edit_in_category($catid)) {
                        $editurl = new moodle_url('/course/editcategory.php', ['id' => $catid]);
                        $editcatagory = '<div><a href=" '.$editurl.' " class="btn btn-secondary btn-sm">'
                                .get_string('categoryedit', 'theme_snap').'</a></div>';
                    }

                    // Category summary.
                    $catsummary = '';
                    if ($catdescription) {
                        $content = context_coursecat::instance($cat->id);
                        $catdescription = file_rewrite_pluginfile_urls($catdescription,
                            'pluginfile.php', $content->id, 'coursecat', 'description', null);
                        $options = array('noclean' => true, 'overflowdiv' => false);
                        $catsummary = '<div class="snap-category-description">'
                            .format_text($catdescription, $cat->descriptionformat, $options).
                            $editcatagory.'</div>';
                    } else {
                        // No summary, output edit link.
                        $catsummary = $editcatagory;
                    }
                    echo '<h1>' . html_to_text(s($catname)) . '</h1>';
                    echo $catsummary;
                }

                $iscoursecat = $PAGE->context->contextlevel === CONTEXT_COURSECAT;
                $manageurl = false;
                if (has_capability('moodle/category:manage', $PAGE->context)) {
                    $manageurl = new moodle_url('/course/management.php');
                    if ($iscoursecat) {
                        echo '<div class="text-right">' . $OUTPUT->cover_image_selector() . '</div>';
                    }
                }
                ?>
            </div>
        </div>
        <section id="region-main">
            <?php
            if ($manageurl) {
                echo '<p><a class="btn btn-secondary btn-sm" href="' . $manageurl . '">';
                echo get_string('managecourses', 'moodle') . '</a></p>';
            }
            echo $OUTPUT->main_content();
            ?>
        </section>
        </main>
        <?php
        echo $OUTPUT->custom_block_region('side-pre');
        ?>
    </div>
</div>
    <!-- close moodle js hooks -->
<?php
// @codingStandardsIgnoreEnd
require(__DIR__.'/footer.php');
