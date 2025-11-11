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

// Resource display options.
$name = 'theme_snap/resourcedisplay';
$title = new \core\lang_string('resourcedisplay', 'theme_snap');
$card = new \core\lang_string('card', 'theme_snap');
$list = new \core\lang_string('list', 'theme_snap');
$radios = array('list' => $list, 'card' => $card);
$default = 'card';
$description = get_string('resourcedisplayhelp', 'theme_snap');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$snapsettings->add($setting);

// Course footer on/off.
$name = 'theme_snap/coursefootertoggle';
$title = new \core\lang_string('coursefootertoggle', 'theme_snap');
$description = new \core\lang_string('coursefootertoggledesc', 'theme_snap');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$snapsettings->add($setting);

// Lazy loading for pages.
$name = 'theme_snap/lazyload_mod_page';
$title = get_string('lazyload_mod_page', 'theme_snap');
$description = get_string('lazyload_mod_page_description', 'theme_snap');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$snapsettings->add($setting);

$settings->add($snapsettings);
