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

use theme_n2018\admin_setting_configcourseid;
$n2018settings = new admin_settingpage('themen2018featuredcourses', get_string('featuredcourses', 'theme_n2018'));

// Featured courses instructions.
$name = 'theme_n2018/fc_instructions';
$heading = '';
$description = get_string('featuredcourseshelp', 'theme_n2018');
$setting = new admin_setting_heading($name, $heading, $description);
$n2018settings->add($setting);

// Featured courses heading.
$name = 'theme_n2018/fc_heading';
$title = new lang_string('featuredcoursesheading', 'theme_n2018');
$description = '';
$default = new lang_string('featuredcourses', 'theme_n2018');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_RAW_TRIMMED, 50);
$n2018settings->add($setting);

// Featured courses.
$name = 'theme_n2018/fc_one';
$title = new lang_string('featuredcourseone', 'theme_n2018');
$description = '';
$default = '0';
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_two';
$title = new lang_string('featuredcoursetwo', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_three';
$title = new lang_string('featuredcoursethree', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_four';
$title = new lang_string('featuredcoursefour', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_five';
$title = new lang_string('featuredcoursefive', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_six';
$title = new lang_string('featuredcoursesix', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_seven';
$title = new lang_string('featuredcourseseven', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

$name = 'theme_n2018/fc_eight';
$title = new lang_string('featuredcourseeight', 'theme_n2018');
$setting = new admin_setting_configcourseid($name, $title, $description, $default, PARAM_RAW_TRIMMED);
$n2018settings->add($setting);

// Browse all courses link.
$name = 'theme_n2018/fc_browse_all';
$title = new lang_string('featuredcoursesbrowseall', 'theme_n2018');
$description = new lang_string('featuredcoursesbrowsealldesc', 'theme_n2018');
$checked = '1';
$unchecked = '0';
$default = $unchecked;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default, $checked, $unchecked);
$n2018settings->add($setting);

$settings->add($n2018settings);
