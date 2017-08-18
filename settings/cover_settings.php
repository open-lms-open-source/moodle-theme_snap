<?php
defined('MOODLE_INTERNAL') || die;// Main settings.
use theme_snap\admin_setting_configradiobuttons;

$snapsettings = new admin_settingpage('themesnapccoverdisplay', get_string('coverdisplay', 'theme_snap'));

$name = 'theme_snap/cover_image';
$heading = new lang_string('poster', 'theme_snap');
$description = '';
$setting = new admin_setting_heading($name, $heading, $description);
$snapsettings->add($setting);

// Cover image file setting.
$name = 'theme_snap/poster';
$title = new lang_string('poster', 'theme_snap');
$description = new lang_string('posterdesc', 'theme_snap');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'poster', 0, $opts);
$setting->set_updatedcallback('theme_snap_process_site_coverimage');
$snapsettings->add($setting);

$settings->add($snapsettings);
