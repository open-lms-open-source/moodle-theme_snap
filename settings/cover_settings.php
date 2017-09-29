<?php
defined('MOODLE_INTERNAL') || die;// Main settings.
use theme_snap\admin_setting_configradiobuttons;

$snapsettings = new admin_settingpage('themesnapcoverdisplay', get_string('coverdisplay', 'theme_snap'));

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

// Cover carousel.
$name = 'theme_snap/cover_carousel_heading';
$heading = new lang_string('covercarousel', 'theme_snap');
$description = new lang_string('covercarouseldescription', 'theme_snap');
$setting = new admin_setting_heading($name, $heading, $description);
$snapsettings->add($setting);

$name = 'theme_snap/cover_carousel';
$title = new lang_string('covercarouselon', 'theme_snap');
$description = '';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);


$name = 'theme_snap/slide_one_image';
$title = new lang_string('coverimage', 'theme_snap');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_one_image', 0, $opts);
$snapsettings->add($setting);

$name = 'theme_snap/slide_two_image';
$title = new lang_string('coverimage', 'theme_snap');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_two_image', 0, $opts);
$snapsettings->add($setting);

$name = 'theme_snap/slide_three_image';
$title = new lang_string('coverimage', 'theme_snap');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_three_image', 0, $opts);
$snapsettings->add($setting);

$name = 'theme_snap/slide_one_title';
$title = new lang_string('title', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$snapsettings->add($setting);

$name = 'theme_snap/slide_two_title';
$title = new lang_string('title', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$snapsettings->add($setting);

$name = 'theme_snap/slide_three_title';
$title = new lang_string('title', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$snapsettings->add($setting);

$name = 'theme_snap/slide_one_subtitle';
$title = new lang_string('subtitle', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$snapsettings->add($setting);

$name = 'theme_snap/slide_two_subtitle';
$title = new lang_string('subtitle', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$snapsettings->add($setting);

$name = 'theme_snap/slide_three_subtitle';
$title = new lang_string('subtitle', 'theme_snap');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$snapsettings->add($setting);

$settings->add($snapsettings);
