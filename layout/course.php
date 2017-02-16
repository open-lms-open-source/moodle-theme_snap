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
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/header.php');

$coursemainpage = strpos($PAGE->pagetype, 'course-view-') === 0;
$tocformat = ($COURSE->format == 'topics' || $COURSE->format == 'weeks');
$leftnav = !empty($PAGE->theme->settings->leftnav);
?>
<!-- moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">
<div id="page-header" class="clearfix
<?php
// Check if the course is using a cover image.
if (!empty($coverimagecss)) : ?>
 mast-image
<?php endif;?>">
<div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>

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
    echo '<div class="col-md-3">';
    echo $OUTPUT->course_toc();
    echo '</div>';
    echo '<div class="col-md-9">';
}
?>
<section id="region-main">
<?php
echo $OUTPUT->course_content_header();
$output = $PAGE->get_renderer('core', 'course');
echo $output->snap_footer_alert();
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
    $coursefooter = $OUTPUT->course_footer();
    if (!empty($coursefooter)) { ?>
    <footer role=contentinfo id=snap-course-footer class=row><?php echo $coursefooter ?></footer>
    <?php
    }
} ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->

<?php require(__DIR__.'/footer.php');
