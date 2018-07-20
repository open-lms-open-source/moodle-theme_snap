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
use theme_n2018\admin_setting_configradiobuttons;

$n2018settings = new admin_settingpage('themen2018coverdisplay', get_string('coverdisplay', 'theme_n2018'));

$name = 'theme_n2018/cover_image';
$heading = new lang_string('poster', 'theme_n2018');
$description = '';
$setting = new admin_setting_heading($name, $heading, $description);
$n2018settings->add($setting);

// Cover image file setting.
$name = 'theme_n2018/poster';
$title = new lang_string('poster', 'theme_n2018');
$description = new lang_string('posterdesc', 'theme_n2018');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'poster', 0, $opts);
$setting->set_updatedcallback('theme_n2018_process_site_coverimage');
$n2018settings->add($setting);

// Cover carousel.
$name = 'theme_n2018/cover_carousel_heading';
$heading = new lang_string('covercarousel', 'theme_n2018');
$description = new lang_string('covercarouseldescription', 'theme_n2018');
$setting = new admin_setting_heading($name, $heading, $description);
$n2018settings->add($setting);

$name = 'theme_n2018/cover_carousel';
$title = new lang_string('covercarouselon', 'theme_n2018');
$description = '';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$n2018settings->add($setting);


$name = 'theme_n2018/slide_one_image';
$title = new lang_string('coverimage', 'theme_n2018');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_one_image', 0, $opts);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_two_image';
$title = new lang_string('coverimage', 'theme_n2018');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_two_image', 0, $opts);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_three_image';
$title = new lang_string('coverimage', 'theme_n2018');
$description = '';
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'slide_three_image', 0, $opts);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_one_title';
$title = new lang_string('title', 'theme_n2018');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_two_title';
$title = new lang_string('title', 'theme_n2018');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_three_title';
$title = new lang_string('title', 'theme_n2018');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_one_subtitle';
$title = new lang_string('subtitle', 'theme_n2018');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_two_subtitle';
$title = new lang_string('subtitle', 'theme_n2018');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$n2018settings->add($setting);

$name = 'theme_n2018/slide_three_subtitle';
$title = new lang_string('subtitle', 'theme_n2018');
$description = '';
$default = '';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$n2018settings->add($setting);

$settings->add($n2018settings);
