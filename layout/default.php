<?php include(__DIR__.'/header.php'); ?>

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
?>
</div>
<?php
echo $OUTPUT->print_settings_link();
if ($this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
    echo '<a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingsnap#admin-poster">'.get_string('changecoverimage', 'theme_snap').'</a>';
}
?>
</header>

<section id="region-main">
<?php
echo $OUTPUT->course_content_header();
echo $OUTPUT->page_heading_button();
echo $OUTPUT->main_content();
echo $OUTPUT->course_content_footer();
?>
</section>

<?php include(__DIR__.'/moodle-blocks.php'); ?>
</main>

</div>
</div>
<!-- close moodle js hooks -->

<?php include(__DIR__.'/footer.php'); ?>
