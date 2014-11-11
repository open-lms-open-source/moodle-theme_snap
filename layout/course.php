<?php
include(__DIR__.'/header.php');

$coursemainpage = strpos($PAGE->pagetype, 'course-view-') === 0;
?>
<!-- moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<main id="moodle-page" class="clearfix">
<div id="page-header" class="clearfix">
<div class="breadcrumb-nav" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>

<div id="page-mast"
<?php if (!empty($snapcourseimage)) : ?>
class="mast-image"
<?php endif;?>
>
<?php
echo $OUTPUT->page_heading();
echo $OUTPUT->course_header();
if ($coursemainpage) {
    echo $OUTPUT->print_course_toc();
}
?>
</div>
<?php echo $OUTPUT->print_settings_link(); ?>
</div>

<section id="region-main">
<?php
echo $OUTPUT->course_content_header();
echo $OUTPUT->page_heading_button();
echo $OUTPUT->main_content();
echo $OUTPUT->course_content_footer();
?>
</section>

<?php

include(__DIR__.'/moodle-blocks.php');

if ($coursemainpage) {
    $coursefooter = $OUTPUT->print_course_footer();
    if (!empty($coursefooter)) : ?>
    <footer role=footer id=snap-course-footer class=row><?php echo $coursefooter ?></footer>
    <?php endif;
} ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->

<?php include(__DIR__.'/footer.php'); ?>
