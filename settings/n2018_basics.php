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

$n2018settings = new admin_settingpage('themen2018branding', get_string('basics', 'theme_n2018'));

if (!during_initial_install() && !empty(get_site()->fullname)) {
    // Site name setting.
    $name = 'fullname';
    $title = new lang_string('fullname', 'theme_n2018');
    $description = new lang_string('fullnamedesc', 'theme_n2018');
    $description = '';
    $setting = new admin_setting_sitesettext($name, $title, $description, null);
    $n2018settings->add($setting);
}

// Main theme colour setting.
$name = 'theme_n2018/themecolor';
$title = new lang_string('themecolor', 'theme_n2018');
$description = '';
$default = '#ff7f41'; // Moodlerooms orange.
$previewconfig = null;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$n2018settings->add($setting);

// Site description setting.
$name = 'theme_n2018/subtitle';
$title = new lang_string('sitedescription', 'theme_n2018');
$description = new lang_string('subtitle_desc', 'theme_n2018');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_RAW_TRIMMED, 50);
$n2018settings->add($setting);

$name = 'theme_n2018/imagesheading';
$title = new lang_string('images', 'theme_n2018');
$description = '';
$setting = new admin_setting_heading($name, $title, $description);
$n2018settings->add($setting);

 // Logo file setting.
$name = 'theme_n2018/logo';
$title = new lang_string('logo', 'theme_n2018');
$description = new lang_string('logodesc', 'theme_n2018');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$n2018settings->add($setting);


// Favicon file setting.
$name = 'theme_n2018/favicon';
$title = new lang_string('favicon', 'theme_n2018');
$description = new lang_string('favicondesc', 'theme_n2018');
$opts = array('accepted_types' => array('.ico', '.png', '.gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$n2018settings->add($setting);

$name = 'theme_n2018/footerheading';
$title = new lang_string('footnote', 'theme_n2018');
$description = '';
$setting = new admin_setting_heading($name, $title, $description);
$n2018settings->add($setting);

// Custom footer setting.
$name = 'theme_n2018/footnote';
$title = new lang_string('footnote', 'theme_n2018');
$description = new lang_string('footnotedesc', 'theme_n2018');
$default = '';
$setting = new admin_setting_confightmleditor($name, $title, $description, $default);
$n2018settings->add($setting);

// Advanced branding heading.
$name = 'theme_n2018/advancedbrandingheading';
$title = new lang_string('advancedbrandingheading', 'theme_n2018');
$description = new lang_string('advancedbrandingheadingdesc', 'theme_n2018');
$setting = new admin_setting_heading($name, $title, $description);
$n2018settings->add($setting);

// Heading font setting.
$name = 'theme_n2018/headingfont';
$title = new lang_string('headingfont', 'theme_n2018');
$description = new lang_string('headingfont_desc', 'theme_n2018');
$default = '"Roboto"';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$n2018settings->add($setting);

// Serif font setting.
$name = 'theme_n2018/seriffont';
$title = new lang_string('seriffont', 'theme_n2018');
$description = new lang_string('seriffont_desc', 'theme_n2018');
$default = '"Georgia"';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$n2018settings->add($setting);

// Custom CSS file.
$name = 'theme_n2018/customcss';
$title = new lang_string('customcss', 'theme_n2018');
$description = new lang_string('customcssdesc', 'theme_n2018');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$n2018settings->add($setting);

$settings->add($n2018settings);
