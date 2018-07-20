<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;// Main settings.

use theme_n2018\admin_setting_configurl;

$n2018settings = new admin_settingpage('themen2018socialmedia', get_string('socialmedia', 'theme_n2018'));

    // Social media.
    $name = 'theme_n2018/facebook';
    $title = new lang_string('facebook', 'theme_n2018');
    $description = new lang_string('facebookdesc', 'theme_n2018');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $n2018settings->add($setting);

    $name = 'theme_n2018/twitter';
    $title = new lang_string('twitter', 'theme_n2018');
    $description = new lang_string('twitterdesc', 'theme_n2018');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $n2018settings->add($setting);

    $name = 'theme_n2018/linkedin';
    $title = new lang_string('linkedin', 'theme_n2018');
    $description = new lang_string('linkedindesc', 'theme_n2018');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $n2018settings->add($setting);

    $name = 'theme_n2018/youtube';
    $title = new lang_string('youtube', 'theme_n2018');
    $description = new lang_string('youtubedesc', 'theme_n2018');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $n2018settings->add($setting);

    $name = 'theme_n2018/instagram';
    $title = new lang_string('instagram', 'theme_n2018');
    $description = new lang_string('instagramdesc', 'theme_n2018');
    $default = '';
    $setting = new admin_setting_configurl($name, $title, $description, $default);
    $n2018settings->add($setting);

    $settings->add($n2018settings);
