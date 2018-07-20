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

$n2018settings = new admin_settingpage('themen2018coursedisplay', get_string('coursedisplay', 'theme_n2018'));

// Course toc display options.
$name = 'theme_n2018/leftnav';
$title = new lang_string('leftnav', 'theme_n2018');
$list = get_string('list', 'theme_n2018');
$top = get_string('top', 'theme_n2018');
$radios = array('list' => $list, 'top' => $top);
$default = 'list';
$description = new lang_string('leftnavdesc', 'theme_n2018');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$n2018settings->add($setting);

// Resource display options.
$name = 'theme_n2018/resourcedisplay';
$title = new lang_string('resourcedisplay', 'theme_n2018');
$card = new lang_string('card', 'theme_n2018');
$list = new lang_string('list', 'theme_n2018');
$radios = array('list' => $list, 'card' => $card);
$default = 'card';
$description = get_string('resourcedisplayhelp', 'theme_n2018');
$setting = new admin_setting_configradiobuttons($name, $title, $description, $default, $radios);
$n2018settings->add($setting);

// Course footer on/off.
$name = 'theme_n2018/coursefootertoggle';
$title = new lang_string('coursefootertoggle', 'theme_n2018');
$description = new lang_string('coursefootertoggledesc', 'theme_n2018');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$n2018settings->add($setting);

// Hide navigation block.
$name = 'theme_n2018/hidenavblock';
$title = new lang_string('hidenavblock', 'theme_n2018');
$description = new lang_string('hidenavblockdesc', 'theme_n2018');
$default = $checked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$n2018settings->add($setting);

$settings->add($n2018settings);
