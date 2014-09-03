<footer id="moodle-footer" role="footer" class="clearfix">
<?php
/* snap custom footer */

/* custom footer edit button - always shown */
$footnote = empty($PAGE->theme->settings->footnote) ? '' : $PAGE->theme->settings->footnote;
if ($this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
    $footnote .= '<p class="text-right"><a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingsnap#admin-footnote">'.get_string('editcustomfooter', 'theme_snap').'</a></p>';
}

/* custom menu edit button - only shown if menu exists */
$custommenu = $OUTPUT->custom_menu();
if (!empty($custommenu) && $this->page->user_is_editing() && $PAGE->pagetype == 'site-index') {
    $custommenu .= '<p class="text-right"><a class="btn btn-default btn-sm" href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettings#id_s__custommenuitems">'.get_string('editcustommenu', 'theme_snap').'</a></p>';
}



if (!empty($custommenu) && !empty($footnote)) {
    echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo $footnote;
        echo '</div>';
        echo '<div class="col-md-6">';
        echo $custommenu;
        echo '</div>';
    echo '</div>';
} else if (!empty($footnote)) {
    echo '<div class="row">
        <div class="col-md-12">';
    echo $footnote;
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
<small><?php print_string('poweredbyrunby', 'theme_snap', (string) $OUTPUT->pix_url('poweredby'.$pwdby, 'theme')) ?><img class="moodlefooterlogo" src="<?php echo (string) $OUTPUT->pix_url('footermoodlelogo-w', 'theme'); ?>" alt="moodle" /><span class="footerlinkdivider">·</span><a href="http://kb.moodlerooms.com/joule-2-manuals" target='_blank'><?php print_string('manuals', 'theme_snap') ?></a><span class="footerlinkdivider">·</span><a href="http://kb.moodlerooms.com/" target='_blank'><?php print_string('knowledgebase', 'theme_snap') ?></a>
<?php
if ($OUTPUT->page_doc_link()) {
    echo " · ".$OUTPUT->page_doc_link();
}
?>
<br>© Copyright 2014 Moodlerooms Inc, All Rights Reserved.</small></div>

<div id="page-footer">
<?php echo $OUTPUT->lang_menu(); ?>
<?php echo $OUTPUT->standard_footer_html(); ?>
</div>
</footer>
<?php echo $OUTPUT->standard_end_of_body_html() ?>


<!-- bye! -->
</body>
</html>
