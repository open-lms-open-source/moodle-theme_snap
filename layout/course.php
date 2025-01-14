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
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
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
// Check if in current path we must to hide TOC.
$pathurl = $PAGE->url->get_path();
$pathurl = $OUTPUT->get_path_hiddentoc($pathurl);
?>
<!-- Moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<div id="moodle-page" class="clearfix">
<?php
echo $OUTPUT->custom_menu_spacer();
?>
<div id="page-header" class="clearfix <?php echo $mastimage; ?>">
    <nav class="breadcrumb-nav" aria-label="breadcrumbs"><?php echo $OUTPUT->snapnavbar($mastimage); ?></nav>

    <div id="page-mast">
    <?php
    if ($coursemainpage) {
        $output = $PAGE->get_renderer('core', 'course');
        echo $output->course_format_warning();
    }
    // Allow individual course formats to set their preferred values.
    switch ($COURSE->format) {
        case 'weeks':
            $PAGE->set_heading($COURSE->fullname);
            break;
        default:
            break;
    }
    echo $OUTPUT->page_heading();
    echo $OUTPUT->course_header();
    // Note, there is no blacklisting for the edit blocks button on course pages.
    echo $OUTPUT->page_heading_button();
    if ($tocformat && !$leftnav && !$pathurl) {
        echo $OUTPUT->course_toc();
    }
    ?>
    </div>
</div>
<?php
if ($tocformat && $leftnav) {
    echo '<div id="snap-course-wrapper">';
    echo '<div class="row">';
    // If current path is a level up view, we hide TOC.
    if ($pathurl === true) {
        echo '<div class="col-lg-12">';
    } else {
        echo '<div class="col-lg-3">';
        echo $OUTPUT->course_toc();
        echo '</div>';
        echo '<div class="col-lg-9">';
    }
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
echo $OUTPUT->snap_feeds_side_menu();

if ($tocformat && $leftnav) {
    echo '</div> <!-- close section -->';
    echo '</div> <!-- close row -->';
    echo '</div> <!-- close course wrapper -->';
}

if ($coursemainpage) {
    $coursefooter = $output->course_footer();
    if (!empty($coursefooter)) { ?>
        <div id="snap-course-footer"><?php echo $coursefooter ?></div>
        <?php
    }
} ?>
</div>

</div>
</div>
<!-- close moodle js hooks -->

<?php require(__DIR__.'/footer.php');
