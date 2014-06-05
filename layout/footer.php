<footer id="moodle-footer" role="footer" class="clearfix">

<div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
<?php

$custommenu = $OUTPUT->custom_menu();
if (!empty($custommenu) && !empty($PAGE->theme->settings->footnote)) {
    echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo $PAGE->theme->settings->footnote;
        echo '</div>';
        echo '<div class="col-md-6">';
        echo $custommenu;
        echo '</div>';
    echo '</div>';
} else if (!empty($PAGE->theme->settings->footnote)) {
    echo '<div class="row">
        <div class="col-md-12">';
    echo $PAGE->theme->settings->footnote;
    echo '</div></div>';
} else if (!empty($custommenu)) {
    echo '<div class="row">
        <div class="col-md-12">';
    echo $custommenu;
    echo '</div></div>';
}
?>
<hr>
<div class="helplink text-right">
<?php
    // set moodle rooms package logo
    $pwdby = isset($CFG->MR_PACKAGE) ? $CFG->MR_PACKAGE : 'power';
?>
<small><?php print_string('poweredbyrunby', 'theme_snap', (string) $OUTPUT->pix_url('poweredby'.$pwdby,'theme')) ?> · <a href="http://kb.moodlerooms.com/joule-2-manuals" target=_'blank'><?php print_string('manuals', 'theme_snap') ?></a> · <a href="http://kb.moodlerooms.com/" target='_blank'><?php print_string('knowledgebase', 'theme_snap') ?></a>
<?php
if ($OUTPUT->page_doc_link()) {
    echo " · ".$OUTPUT->page_doc_link();
}
?>
<br>© Copyright 2014 Moodlerooms Inc, All Rights Reserved.</small></div>

<div id="page-footer">
<?php echo $OUTPUT->lang_menu(); ?>
<?php echo $OUTPUT->standard_footer_html(); ?>
<div>
</footer>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
<!-- bye! -->
</body>
</html>
