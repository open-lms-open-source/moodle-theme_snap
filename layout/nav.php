<div id='mr-nav' class='clearfix moodle-has-zindex'>
<div class="pull-right">
<?php $OUTPUT->print_login(); ?>
</div>

<?php if (!empty($PAGE->theme->settings->logo)) { ?>
    <a href="<?php echo s($CFG->wwwroot);?>" id="logo" title="<?php echo s(format_string($SITE->fullname)); ?>"><span class="sr-only"><?php echo format_string($SITE->fullname); ?> <?php print_string('home', 'theme_snap'); ?></span></a>
<?php } else { ?>
    <a href="<?php echo s($CFG->wwwroot);?>" id="logo" title="<?php echo s(format_string($SITE->fullname)); ?>"><?php echo format_string($SITE->fullname); ?> <span class="sr-only"><?php print_string('home', 'theme_snap'); ?></span></a>
<?php } ?>

</div>
