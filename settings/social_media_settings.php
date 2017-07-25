<?php
defined('MOODLE_INTERNAL') || die;// Main settings.

use theme_snap\admin_setting_configurl;

$snapsettings = new admin_settingpage('themesnapsocialmedia', get_string('socialmedia', 'theme_snap'));

    // Social media.
    $name = 'theme_snap/facebook';
    $title = new lang_string('facebook', 'theme_snap');
    $description = new lang_string('facebookdesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $snapsettings->add($setting);

    $name = 'theme_snap/twitter';
    $title = new lang_string('twitter', 'theme_snap');
    $description = new lang_string('twitterdesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $snapsettings->add($setting);

    $name = 'theme_snap/linkedin';
    $title = new lang_string('linkedin', 'theme_snap');
    $description = new lang_string('linkedindesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $snapsettings->add($setting);

    $name = 'theme_snap/youtube';
    $title = new lang_string('youtube', 'theme_snap');
    $description = new lang_string('youtubedesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $snapsettings->add($setting);

    $name = 'theme_snap/instagram';
    $title = new lang_string('instagram', 'theme_snap');
    $description = new lang_string('instagramdesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $snapsettings->add($setting);

    $settings->add($snapsettings);
