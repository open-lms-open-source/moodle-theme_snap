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

$snapsettings = new admin_settingpage('themesnapbranding', get_string('basics', 'theme_snap'));

if (!during_initial_install() && !empty(get_site()->fullname)) {
    // Site name setting.
    $name = 'fullname';
    $title = new \core\lang_string('fullname', 'theme_snap');
    $description = new \core\lang_string('fullnamedesc', 'theme_snap');
    $description = '';
    $setting = new admin_setting_sitesettext($name, $title, $description, null);
    $snapsettings->add($setting);
}

// Main theme colour setting.
$name = 'theme_snap/themecolor';
$title = new \core\lang_string('themecolor', 'theme_snap');
$description = '';
$default = '#82009E'; // Open LMS EDU Purple - To pass WCAG color contrast ratio check.
$previewconfig = null;
$setting = new \theme_snap\admin_setting_configcolorwithcontrast(
    \theme_snap\admin_setting_configcolorwithcontrast::BASICS, $name, $title, $description, $default, $previewconfig);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Site description setting.
$name = 'theme_snap/subtitle';
$title = new \core\lang_string('sitedescription', 'theme_snap');
$description = new \core\lang_string('subtitle_desc', 'theme_snap');
$setting = new admin_setting_configtext_with_maxlength($name, $title, $description, '', PARAM_RAW_TRIMMED, 50, 130);
$snapsettings->add($setting);

$name = 'theme_snap/imagesheading';
$title = new \core\lang_string('images', 'theme_snap');
$description = '';
$setting = new admin_setting_heading($name, $title, $description);
$snapsettings->add($setting);

 // Logo file setting.
$name = 'theme_snap/logo';
$title = new \core\lang_string('logo', 'theme_snap');
$description = new \core\lang_string('logodesc', 'theme_snap');
$opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);


// Favicon file setting.
$name = 'theme_snap/favicon';
$title = new \core\lang_string('favicon', 'theme_snap');
$description = new \core\lang_string('favicondesc', 'theme_snap');
$opts = array('accepted_types' => array('.ico', '.png', '.gif'));
$setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Icon activities color heading.
$name = 'theme_snap/iconactivitiescolorheading';
$title = new \core\lang_string('iconactivitiescolor', 'theme_snap');
$description = new \core\lang_string('iconactivitiescolordesc', 'theme_snap');
$setting = new admin_setting_heading($name, $title, $description);
$snapsettings->add($setting);

// Administration activities color.
$name = 'theme_snap/adminactivitiescolor';
$title = new \core\lang_string('adminactivitiescolor', 'theme_snap');
$description = 'Default: #da58ef';
$default = '#da58ef';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Assessment activities color.
$name = 'theme_snap/assessactivitiescolor';
$title = new \core\lang_string('assessactivitiescolor', 'theme_snap');
$description = 'Default: #008CBA';
$default = '#008CBA';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Collaboration activities color.
$name = 'theme_snap/collabactivitiescolor';
$title = new \core\lang_string('collabactivitiescolor', 'theme_snap');
$description = 'Default: #009B87';
$default = '#009B87';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Communication activities color.
$name = 'theme_snap/commactivitiescolor';
$title = new \core\lang_string('commactivitiescolor', 'theme_snap');
$description = 'Default: #689F38';
$default = '#689F38';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Interactive content activities color.
$name = 'theme_snap/interactivitiescolor';
$title = new \core\lang_string('interactivitiescolor', 'theme_snap');
$description = 'Default: #8d3d1b';
$default = '#8d3d1b';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Resource activities color.
$name = 'theme_snap/resouactivitiescolor';
$title = new \core\lang_string('resouactivitiescolor', 'theme_snap');
$description = 'Default: #3279B2';
$default = '#3279B2';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Advanced branding heading.
$name = 'theme_snap/advancedbrandingheading';
$title = new \core\lang_string('advancedbrandingheading', 'theme_snap');
$description = new \core\lang_string('advancedbrandingheadingdesc', 'theme_snap');
$setting = new admin_setting_heading($name, $title, $description);
$snapsettings->add($setting);

// Heading font setting.
$name = 'theme_snap/headingfont';
$title = new \core\lang_string('headingfont', 'theme_snap');
$description = new \core\lang_string('headingfont_desc', 'theme_snap');
$default = '"Roboto"';
$setting = new admin_setting_configtext($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

// Custom CSS file.
$name = 'theme_snap/customcss';
$title = new \core\lang_string('customcss', 'theme_snap');
$description = new \core\lang_string('customcssdesc', 'theme_snap');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

$name = 'theme_snap/customscss';
$title = new \core\lang_string('customscss', 'theme_snap');
$description = new \core\lang_string('customscssdesc', 'theme_snap');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$snapsettings->add($setting);

$settings->add($snapsettings);
