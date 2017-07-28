<?php
defined('MOODLE_INTERNAL') || die;// Main settings.

$snapsettings = new admin_settingpage('themesnappersonalmenu', get_string('personalmenu', 'theme_snap'));

// Personal menu show course grade in cards.
$name = 'theme_snap/showcoursegradepersonalmenu';
$title = new lang_string('showcoursegradepersonalmenu', 'theme_snap');
$description = new lang_string('showcoursegradepersonalmenudesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Personal menu deadlines on/off.
$name = 'theme_snap/deadlinestoggle';
$title = new lang_string('deadlinestoggle', 'theme_snap');
$description = new lang_string('deadlinestoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Personal menu recent feedback & grading  on/off.
$name = 'theme_snap/feedbacktoggle';
$title = new lang_string('feedbacktoggle', 'theme_snap');
$description = new lang_string('feedbacktoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Personal menu messages on/off.
$name = 'theme_snap/messagestoggle';
$title = new lang_string('messagestoggle', 'theme_snap');
$description = new lang_string('messagestoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Personal menu forum posts on/off.
$name = 'theme_snap/forumpoststoggle';
$title = new lang_string('forumpoststoggle', 'theme_snap');
$description = new lang_string('forumpoststoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Personal menu display on login on/off.
$name = 'theme_snap/personalmenulogintoggle';
$title = new lang_string('personalmenulogintoggle', 'theme_snap');
$description = new lang_string('personalmenulogintoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

$settings->add($snapsettings);
