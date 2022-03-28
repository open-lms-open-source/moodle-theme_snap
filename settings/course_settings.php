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
use theme_snap\admin_setting_configradiobuttons;

$snapsettings = new admin_settingpage('themesnapcoursedisplay', get_string('coursedisplay', 'theme_snap'));

// Course toc display options.
$name = 'theme_snap/leftnav';
$title = new lang_string('leftnav', 'theme_snap');
$list = get_string('list', 'theme_snap');
$top = get_string('top', 'theme_snap');
$radios = array('list' => $list, 'top' => $top);
$default = 'list';
$description = new lang_string('leftnavdesc', 'theme_snap');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$snapsettings->add($setting);

// Resource display options.
$name = 'theme_snap/resourcedisplay';
$title = new lang_string('resourcedisplay', 'theme_snap');
$card = new lang_string('card', 'theme_snap');
$list = new lang_string('list', 'theme_snap');
$radios = array('list' => $list, 'card' => $card);
$default = 'card';
$description = get_string('resourcedisplayhelp', 'theme_snap');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$snapsettings->add($setting);

// Resource and URL description display options.
$name = 'theme_snap/displaydescription';
$title = new lang_string('displaydescription', 'theme_snap');
$default = $unchecked;
$description = new lang_string('displaydescriptionhelp', 'theme_snap');
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Course footer on/off.
$name = 'theme_snap/coursefootertoggle';
$title = new lang_string('coursefootertoggle', 'theme_snap');
$description = new lang_string('coursefootertoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Course web service for sections.
$name = 'theme_snap/coursepartialrender';
$title = new lang_string('coursepartialrender', 'theme_snap');
$description = new lang_string('coursepartialrenderdesc', 'theme_snap');
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Lazy loading for pages.
$name = 'theme_snap/lazyload_mod_page';
$title = get_string('lazyload_mod_page', 'theme_snap');
$description = get_string('lazyload_mod_page_description', 'theme_snap');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$snapsettings->add($setting);

// Choose design for pages.
$name = 'theme_snap/design_mod_page';
$title = get_string('design_mod_page', 'theme_snap');
$description = get_string('design_mod_page_description', 'theme_snap');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$snapsettings->add($setting);

$settings->add($snapsettings);
