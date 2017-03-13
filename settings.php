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

/**
 * Snap settings.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use theme_snap\admin_setting_configurl;
use theme_snap\admin_setting_configcourseid;
use theme_snap\admin_setting_configradiobuttons;


$ADMIN->add('themes', new admin_category('theme_snap', 'Snap'));
$settings = null; // Unsets the default $settings object initialised by Moodle.

// Basic settings.
$snapsettings = new admin_settingpage('themesettingsnap', 'Snap');

// Feature spots settings.
$fssettings = new admin_settingpage('themesnapfeaturespots', get_string('featurespots', 'theme_snap'));

// Featured courses settings.
$fcsettings = new admin_settingpage('themesnapfeaturedcourses', get_string('featuredcourses', 'theme_snap'));

// Feature spots settings.
$resourcesettings = new admin_settingpage('themesnapresourcedisplay', get_string('resourcedisplay', 'theme_snap'));

if ($ADMIN->fulltree) {

    $checked = '1';
    $unchecked = '0';

    // Output flex page front page warning if necessary.
    $fpwarning = \theme_snap\output\shared::flexpage_frontpage_warning();
    if (!empty($fpwarning)) {
        $setting = new admin_setting_heading('flexpage_warning', '', $fpwarning);
        $snapsettings->add($setting);
    }

    $name = 'theme_snap/brandingheading';
    $title = new lang_string('brandingheading', 'theme_snap');
    $description = new lang_string('brandingheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $snapsettings->add($setting);

    if (!during_initial_install() && !empty(get_site()->fullname)) {
        // Site name setting.
        $name = 'fullname';
        $title = new lang_string('fullname', 'theme_snap');
        $description = new lang_string('fullnamedesc', 'theme_snap');
        $setting = new admin_setting_sitesettext($name, $title, $description, null);
        $snapsettings->add($setting);
    }

    // Site description setting.
    $name = 'theme_snap/subtitle';
    $title = new lang_string('subtitle', 'theme_snap');
    $description = new lang_string('subtitle_desc', 'theme_snap');
    $setting = new admin_setting_configtextarea($name, $title, $description, '');
    $snapsettings->add($setting);

    // Main theme colour setting.
    $name = 'theme_snap/themecolor';
    $title = new lang_string('themecolor', 'theme_snap');
    $description = new lang_string('themecolordesc', 'theme_snap');
    $default = '#3bcedb';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

     // Logo file setting.
    $name = 'theme_snap/logo';
    $title = new lang_string('logo', 'theme_snap');
    $description = new lang_string('logodesc', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Favicon file setting.
    $name = 'theme_snap/favicon';
    $title = new lang_string('favicon', 'theme_snap');
    $description = new lang_string('favicondesc', 'theme_snap');
    $opts = array('accepted_types' => array('.ico'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Cover image file setting.
    $name = 'theme_snap/poster';
    $title = new lang_string('poster', 'theme_snap');
    $description = new lang_string('posterdesc', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'poster', 0, $opts);
    $setting->set_updatedcallback('theme_snap_process_site_coverimage');
    $snapsettings->add($setting);

    // Personal menu settings.
    $name = 'theme_snap/personalmenu';
    $title = new lang_string('personalmenu', 'theme_snap');
    $description = new lang_string('footerheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $snapsettings->add($setting);

    // Personal menu display on login on/off.
    $name = 'theme_snap/personalmenulogintoggle';
    $title = new lang_string('personalmenulogintoggle', 'theme_snap');
    $description = new lang_string('personalmenulogintoggledesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $snapsettings->add($setting);

    // Personal menu deadlines on/off.
    $name = 'theme_snap/deadlinestoggle';
    $title = new lang_string('deadlinestoggle', 'theme_snap');
    $description = new lang_string('deadlinestoggledesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Personal menu recent feedback & grading  on/off.
    $name = 'theme_snap/feedbacktoggle';
    $title = new lang_string('feedbacktoggle', 'theme_snap');
    $description = new lang_string('feedbacktoggledesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Personal menu messages on/off.
    $name = 'theme_snap/messagestoggle';
    $title = new lang_string('messagestoggle', 'theme_snap');
    $description = new lang_string('messagestoggledesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Personal menu forum posts on/off.
    $name = 'theme_snap/forumpoststoggle';
    $title = new lang_string('forumpoststoggle', 'theme_snap');
    $description = new lang_string('forumpoststoggledesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Personal menu show course grade in cards.
    $name = 'theme_snap/showcoursegradepersonalmenu';
    $title = new lang_string('showcoursegradepersonalmenu', 'theme_snap');
    $description = new lang_string('showcoursegradepersonalmenudesc', 'theme_snap');
    $default = $checked; // For new installations (legacy is unchecked via upgrade.php).
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $snapsettings->add($setting);

    $name = 'theme_snap/footerheading';
    $title = new lang_string('footerheading', 'theme_snap');
    $description = new lang_string('footerheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $snapsettings->add($setting);

    // Hide navigation block.
    $name = 'theme_snap/hidenavblock';
    $title = new lang_string('hidenavblock', 'theme_snap');
    $description = new lang_string('hidenavblockdesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $snapsettings->add($setting);

    // Course footer on/off.
    $name = 'theme_snap/coursefootertoggle';
    $title = new lang_string('coursefootertoggle', 'theme_snap');
    $description = new lang_string('coursefootertoggledesc', 'theme_snap');
    $default = $checked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Custom footer setting.
    $name = 'theme_snap/footnote';
    $title = new lang_string('footnote', 'theme_snap');
    $description = new lang_string('footnotedesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

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

    // Advanced branding heading.
    $name = 'theme_snap/advancedbrandingheading';
    $title = new lang_string('advancedbrandingheading', 'theme_snap');
    $description = new lang_string('advancedbrandingheadingdesc', 'theme_snap');
    $setting = new admin_setting_heading($name, $title, $description);
    $snapsettings->add($setting);

    // Heading font setting.
    $name = 'theme_snap/headingfont';
    $title = new lang_string('headingfont', 'theme_snap');
    $description = new lang_string('headingfont_desc', 'theme_snap');
    $default = '"Roboto"';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Serif font setting.
    $name = 'theme_snap/seriffont';
    $title = new lang_string('seriffont', 'theme_snap');
    $description = new lang_string('seriffont_desc', 'theme_snap');
    $default = '"Georgia"';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Left toc option.
    $name = 'theme_snap/leftnav';
    $title = new lang_string('leftnav', 'theme_snap');
    $description = new lang_string('leftnavdesc', 'theme_snap');
    $default = $unchecked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $snapsettings->add($setting);

    // Custom CSS file.
    $name = 'theme_snap/customcss';
    $title = new lang_string('customcss', 'theme_snap');
    $description = new lang_string('customcssdesc', 'theme_snap');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $snapsettings->add($setting);

    // Feature spots settings.
    // Feature spot instructions.
    $name = 'theme_snap/fs_instructions';
    $heading = '';
    $description = get_string('featurespotshelp', 'theme_snap');
    $setting = new admin_setting_heading($name, $heading, $description);
    $fssettings->add($setting);

    // Feature spots heading.
    $name = 'theme_snap/fs_heading';
    $title = new lang_string('featurespotsheading', 'theme_snap');
    $description = '';
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW, 50);
    $fssettings->add($setting);

    // Feature spot images.
    $name = 'theme_snap/fs_one_image';
    $title = new lang_string('featureoneimage', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'fs_one_image', 0, $opts);
    $fssettings->add($setting);

    $name = 'theme_snap/fs_two_image';
    $title = new lang_string('featuretwoimage', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'fs_two_image', 0, $opts);
    $fssettings->add($setting);

    $name = 'theme_snap/fs_three_image';
    $title = new lang_string('featurethreeimage', 'theme_snap');
    $opts = array('accepted_types' => array('.png', '.jpg', '.gif', '.webp', '.svg'));
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'fs_three_image', 0, $opts);
    $fssettings->add($setting);

    // Feature spot titles.
    $name = 'theme_snap/fs_one_title';
    $title = new lang_string('featureonetitle', 'theme_snap');
    $description = '';
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $fssettings->add($setting);

    $name = 'theme_snap/fs_two_title';
    $title = new lang_string('featuretwotitle', 'theme_snap');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $fssettings->add($setting);

    $name = 'theme_snap/fs_three_title';
    $title = new lang_string('featurethreetitle', 'theme_snap');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $fssettings->add($setting);

    // Feature spot text.
    $name = 'theme_snap/fs_one_text';
    $title = new lang_string('featureonetext', 'theme_snap');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $fssettings->add($setting);

    $name = 'theme_snap/fs_two_text';
    $title = new lang_string('featuretwotext', 'theme_snap');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $fssettings->add($setting);

    $name = 'theme_snap/fs_three_text';
    $title = new lang_string('featurethreetext', 'theme_snap');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $fssettings->add($setting);

    // Featured courses instructions.
    $name = 'theme_snap/fc_instructions';
    $heading = '';
    $description = get_string('featuredcourseshelp', 'theme_snap');
    $setting = new admin_setting_heading($name, $heading, $description);
    $fcsettings->add($setting);

    // Featured courses heading.
    $name = 'theme_snap/fc_heading';
    $title = new lang_string('featuredcoursesheading', 'theme_snap');
    $description = '';
    $default = new lang_string('featuredcourses', 'theme_snap');
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW_TRIMMED, 50);
    $fcsettings->add($setting);

    // Featured courses.
    $name = 'theme_snap/fc_one';
    $title = new lang_string('featuredcourseone', 'theme_snap');
    $description = '';
    $default = '0';
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_two';
    $title = new lang_string('featuredcoursetwo', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_three';
    $title = new lang_string('featuredcoursethree', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_four';
    $title = new lang_string('featuredcoursefour', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_five';
    $title = new lang_string('featuredcoursefive', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_six';
    $title = new lang_string('featuredcoursesix', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_seven';
    $title = new lang_string('featuredcourseseven', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    $name = 'theme_snap/fc_eight';
    $title = new lang_string('featuredcourseeight', 'theme_snap');
    $setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
    $fcsettings->add($setting);

    // Browse all courses link.
    $name = 'theme_snap/fc_browse_all';
    $title = new lang_string('featuredcoursesbrowseall', 'theme_snap');
    $description = new lang_string('featuredcoursesbrowsealldesc', 'theme_snap');
    $checked = '1';
    $unchecked = '0';
    $default = $unchecked;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
    $fcsettings->add($setting);

    // Resource display help text.
    $name = 'theme_snap/resourcedisplayhelp';
    $heading = '';
    $description = get_string('resourcedisplayhelp', 'theme_snap');
    $setting = new admin_setting_heading($name, $heading, $description);
    $resourcesettings->add($setting);

    // Resource display options.
    $name = 'theme_snap/resourcedisplay';
    $title = new lang_string('resourcedisplay', 'theme_snap');
    $card = new lang_string('card', 'theme_snap');
    $list = new lang_string('list', 'theme_snap');
    $radios = array('list' => $list, 'card' => $card);
    $default = 'card';
    $description = '';
    $setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
    $resourcesettings->add($setting);
}

// Add theme pages.
$ADMIN->add('theme_snap', $snapsettings);
$ADMIN->add('theme_snap', $fssettings);
$ADMIN->add('theme_snap', $fcsettings);
$ADMIN->add('theme_snap', $resourcesettings);
