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

$snapsettings = new admin_settingpage('themesnaplogin', get_string('loginsetting', 'theme_snap'));

// Alternative login Settings.
$name = 'theme_snap/alternativeloginoptionsheading';
$title = new lang_string('alternativeloginoptions', 'theme_snap');
$description = '';
$setting = new admin_setting_heading($name, $title, $description);
$snapsettings->add($setting);

// Enable login options display.
$name = 'theme_snap/enabledlogin';
$title = new lang_string('enabledlogin', 'theme_snap');
$description = new lang_string('enabledlogindesc', 'theme_snap');
$default = '0';
$enabledloginchoices = [
    \theme_snap\output\core_renderer::ENABLED_LOGIN_BOTH        => new lang_string('bothlogin', 'theme_snap'),
    \theme_snap\output\core_renderer::ENABLED_LOGIN_MOODLE      => new lang_string('moodlelogin', 'theme_snap'),
    \theme_snap\output\core_renderer::ENABLED_LOGIN_ALTERNATIVE => new lang_string('alternativelogin', 'theme_snap')
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $enabledloginchoices);
$snapsettings->add($setting);

// Enabled login options order.
$name = 'theme_snap/enabledloginorder';
$title = new lang_string('enabledloginorder', 'theme_snap');
$description = new lang_string('enabledloginorderdesc', 'theme_snap');
$default = '0';
$enabledloginchoices = [
    \theme_snap\output\core_renderer::ORDER_LOGIN_MOODLE_FIRST      => new lang_string('moodleloginfirst', 'theme_snap'),
    \theme_snap\output\core_renderer::ORDER_LOGIN_ALTERNATIVE_FIRST => new lang_string('alternativeloginfirst', 'theme_snap')
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $enabledloginchoices);
$snapsettings->add($setting);

$settings->add($snapsettings);