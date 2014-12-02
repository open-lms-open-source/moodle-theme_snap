<header id='mr-nav' class='clearfix moodle-has-zindex'>
<div class="pull-right">
<?php $OUTPUT->print_fixed_menu(); ?>
</div>
<?php
    $sitefullname = format_string($SITE->fullname);
    if (!empty($PAGE->theme->settings->logo)) {
        $sitefullname = '<span class="sr-only">'.format_string($SITE->fullname).'</span>';
    }
    echo '<a aria-label="'.get_string('home', 'theme_snap').'" href="'. s($CFG->wwwroot).'" id="logo" title="'.s(format_string($SITE->fullname)).'">'.$sitefullname.'</a>';    
?>
</header>
