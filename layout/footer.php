<?php
$inccoursefooterclass = ($PAGE->theme->settings->coursefootertoggle && strpos($PAGE->pagetype, 'course-view-') === 0)
    ? ' hascoursefooter'
    : ' nocoursefooter';
?>
<footer id="moodle-footer" role="footer" class="clearfix<?php echo ($inccoursefooterclass)?>">
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

<div id='mrooms-footer' class="helplink text-right">
    <small>
    <?php
    if ($OUTPUT->page_doc_link()) {
        echo $OUTPUT->page_doc_link();
    }
    ?>
    <br/>Built with <a href="http://kb.moodlerooms.com/" target='_blank' title='Joule help guides'>Joule</a> from <a href="http://moodlerooms.com/" target='_blank'>Moodlerooms</a>, powered by <a href="http://www.moodle.com/" target='_blank'>Moodle</a>.
    </small>
<br>© Copyright 2014 Moodlerooms Inc, All Rights Reserved.</small>
</div>
<!-- close mrooms footer -->
<div id="page-footer">
<?php echo $OUTPUT->lang_menu(); ?>
<?php echo $OUTPUT->standard_footer_html(); ?>
</div>
</footer>
<?php echo $OUTPUT->standard_end_of_body_html() ?>
<!-- bye! -->
</body>
</html>
