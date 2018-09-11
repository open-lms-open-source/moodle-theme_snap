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
 * Layout - course.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/header.php');

$coursemainpage = strpos($PAGE->pagetype, 'course-view-') === 0;
$tocformat = ($COURSE->format == 'topics' || $COURSE->format == 'weeks');
// Check if the toc is displayed list or top - used to add layout in this file.
$leftnav = true;
if (!empty($PAGE->theme->settings->leftnav)) {
    if ($PAGE->theme->settings->leftnav == 'top') {
        $leftnav = false;
    }
}
$mastimage = '';
// Check we are in a course (not the site level course), and the course is using a cover image.
if ($COURSE->id != SITEID && !empty($coverimagecss)) {
    $mastimage = 'mast-image';
}
?>
<!-- Moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">
<div id="page-header" class="clearfix <?php echo $mastimage; ?>">
    <div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->navbar($mastimage); ?></div>

    <div id="page-mast">
    <?php
    if ($coursemainpage) {
        $output = $PAGE->get_renderer('core', 'course');
        echo $output->course_format_warning();
    }
    echo $OUTPUT->page_heading();
    echo $OUTPUT->course_header();
    // Note, there is no blacklisting for the edit blocks button on course pages.
    echo $OUTPUT->page_heading_button();
    if ($tocformat && !$leftnav) {
        echo $OUTPUT->course_toc();
    }
    ?>
    </div>
</div>
<?php
if ($tocformat && $leftnav) {
    echo '<div id="snap-course-wrapper">';
    echo '<div class="row">';
    echo '<div class="col-lg-3">';
    echo $OUTPUT->course_toc();
    echo '</div>';
    echo '<div class="col-lg-9">';
}
?>
<section id="region-main">

<?php
echo $OUTPUT->course_content_header();
$output = $PAGE->get_renderer('core', 'course');
echo $output->snap_footer_alert();
echo $OUTPUT->course_modchooser();
echo $OUTPUT->main_content();
echo $OUTPUT->course_content_footer();
?>
</section>
<?php
require(__DIR__.'/moodle-blocks.php');

if ($tocformat && $leftnav) {
    echo '</div> <!-- close section -->';
    echo '</div> <!-- close row -->';
    echo '</div> <!-- close course wrapper -->';
}

if ($coursemainpage) {
    $coursefooter = $output->course_footer();
    if (!empty($coursefooter)) { ?>
        <footer role="contentinfo" id="snap-course-footer"><?php echo $coursefooter ?></footer>
    <?php
    }
} ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->

<?php require(__DIR__.'/footer.php');
