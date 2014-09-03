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
<header id="page-header" class="clearfix">
<nav class="breadcrumb-nav" role="navigation" aria-label="breadcrumb"><?php echo $OUTPUT->navbar(); ?></nav>

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
</header>

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
    <div id=snap-course-footer class=row><?php echo $coursefooter ?></div>
    <?php endif;
} ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->

<?php include(__DIR__.'/footer.php'); ?>
