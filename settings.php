<?php
// This file is part of the custom Moodle Snap theme
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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $name = 'theme_snap/brandingheading';
    $title = new lang_string('brandingheading', 'theme_snap');
    $description = new lang_string('brandingheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $settings->add($setting);

     // Logo file setting.
    $name = 'theme_snap/logo';
    $title = new lang_string('logo', 'theme_snap');
    $description = new lang_string('logodesc', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Favicon file setting.
    $name = 'theme_snap/favicon';
    $title = new lang_string('favicon', 'theme_snap');
    $description = new lang_string('favicondesc', 'theme_snap');
    $opts = array('accepted_types' => array('.ico'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Main theme colour setting.
    $name = 'theme_snap/themecolor';
    $title = new lang_string('themecolor', 'theme_snap');
    $description = new lang_string('themecolordesc', 'theme_snap');
    $default = '#3bcedb';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Cover image file setting.
    $name = 'theme_snap/poster';
    $title = new lang_string('poster', 'theme_snap');
    $description = new lang_string('posterdesc', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'poster', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Site description setting.
    $name = 'theme_snap/subtitle';
    $title = new lang_string('subtitle', 'theme_snap');
    $description = new lang_string('subtitle_desc', 'theme_snap');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $settings->add($setting);

    $name = 'theme_snap/menusandnavheading';
    $title = new lang_string('menusandnavheading', 'theme_snap');
    $description = new lang_string('menusandnavheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $settings->add($setting);

    // Custome footer setting.
    $name = 'theme_snap/footnote';
    $title = new lang_string('footnote', 'theme_snap');
    $description = new lang_string('footnotedesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Advanced branding heading.
    $name = 'theme_snap/advancedbrandingheading';
    $title = new lang_string('advancedbrandingheading', 'theme_snap');
    $description = new lang_string('advancedbrandingheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $settings->add($setting);

    // Heading font setting.
    $name = 'theme_snap/headingfont';
    $title = new lang_string('headingfont', 'theme_snap');
    $description = new lang_string('headingfont_desc', 'theme_snap');
    $default = '"Roboto"';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Serif font setting.
    $name = 'theme_snap/seriffont';
    $title = new lang_string('seriffont', 'theme_snap');
    $description = new lang_string('seriffont_desc', 'theme_snap');
    $default = '"Georgia"';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);


    // Custom CSS file.
    $name = 'theme_snap/customcss';
    $title = new lang_string('customcss', 'theme_snap');
    $description = new lang_string('customcssdesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
