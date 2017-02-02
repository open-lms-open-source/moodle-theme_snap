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
 * Standard library functions for snap theme.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Process site cover image.
 *
 * @throws Exception
 * @throws coding_exception
 * @throws dml_exception
 */
function theme_snap_process_site_coverimage() {
    $context = \context_system::instance();
    \theme_snap\local::process_coverimage($context);
    theme_reset_all_caches();
}

/**
 * CSS Processor
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_snap_process_css($css, theme_config $theme) {

    // Set the background image for the logo.
    $logo = $theme->setting_file_url('logo', 'logo');
    $css = theme_snap_set_logo($css, $logo);

    // Set the custom css.
    if (!empty($theme->settings->customcss)) {
        $customcss = $theme->settings->customcss;
    } else {
        $customcss = null;
    }
    $css = theme_snap_set_customcss($css, $customcss);

    // Set bootswatch.
    $css = theme_snap_set_bootswatch($css, theme_snap_get_bootswatch_variables($theme));

    return $css;
}

/**
 * Adds the logo to CSS.
 *
 * @param string $css The CSS.
 * @param string $logo The URL of the logo.
 * @return string The parsed CSS
 */
function theme_snap_set_logo($css, $logo) {
    $tag = '/**setting:logo**/';
    if (is_null($logo)) {
        $replacement = '';
    } else {
        $replacement = "#snap-home.logo {background-image: url($logo);} #page-login-index .loginpanel h2{background-image: url($logo);}";
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

/**
 * Adds any custom CSS to the CSS before it is cached.
 *
 * @param string $css The original CSS.
 * @param string $customcss The custom CSS to add.
 * @return string The CSS which now contains our custom CSS.
 */
function theme_snap_set_customcss($css, $customcss) {
    $tag = '/**setting:customcss**/';
    $replacement = $customcss;
    if (is_null($replacement)) {
        $replacement = '';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

/**
 * Extract bootswatch variables from theme configuration.
 *
 * @param theme_config $theme
 * @return array
 */
function theme_snap_get_bootswatch_variables(theme_config $theme) {
    $settings['brand-primary'] = !empty($theme->settings->themecolor) ? $theme->settings->themecolor : '#3bcedb';
    $userfontsans  = $theme->settings->headingfont;
    if (empty($userfontsans) || in_array($userfontsans, ['Roboto', '"Roboto"'])) {
        $userfontsans = '';
    } else {
        $userfontsans .= ",";
    }
    $fallbacksans = 'Roboto,"Fira Sans","Segoe UI","HelveticaNeue-Light",'
        . '"Helvetica Neue Light","Helvetica Neue",Helvetica, Arial, sans-serif';
    $settings['font-family-sans-serif'] = $userfontsans . $fallbacksans;

    $userfontserif = $theme->settings->seriffont;
    if (empty($userfontserif) || in_array($userfontserif, ['Georgia', '"Georgia"'])) {
        $userfontserif = '';
    } else {
        $userfontserif .= ",";
    }
    $fallbackserif = 'Georgia,"Times New Roman", Times, serif';
    $settings['font-family-serif'] = $userfontserif . $fallbackserif;

    return $settings;
}

/**
 * Add bootswatch CSS
 *
 * @param string $css The original CSS.
 * @param array $variables The bootswatch variables
 * @return string
 * @see theme_snap_get_bootswatch_variables
 */
function theme_snap_set_bootswatch($css, array $variables) {
    global $CFG;

    $tag = '/**setting:snap-user-bootswatch**/';
    if (strpos($css, $tag) === false) {
        return $css; // Avoid doing work when tag is not present.
    }
    require_once(__DIR__.'/lessphp/Less.php');

    try {
        $parser = new Less_Parser();
        $parser->parseFile(__DIR__.'/less/bootswatch/snap-variables.less', $CFG->wwwroot.'/');
        $parser->parseFile(__DIR__.'/less/bootswatch/snap-user-bootswatch.less', $CFG->wwwroot.'/');
        if (!empty($variables)) {
            $parser->ModifyVars($variables);
        }
        $replacement = $parser->getCss();
    } catch (Exception $e) {
        add_to_log(get_site()->id, 'library', 'bootswatch', '', 'Failed to complile bootswatch: '.$e->getMessage());
        $replacement = '';  // Nothing we can do but remove the tag.
    }
    return str_replace($tag, $replacement, $css);
}

/**
 * Based on theme function setting_file_serve.
 * Always sends item 0
 *
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param $options
 * @return bool
 */
function theme_snap_send_file($context, $filearea, $args, $forcedownload, $options) {
    $revision = array_shift($args);
    if ($revision < 0) {
        $lifetime = 0;
    } else {
        $lifetime = DAYSECS * 60;
    }

    $filename = end($args);
    $contextid = $context->id;
    $fullpath = "/$contextid/theme_snap/$filearea/0/$filename";
    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));

    if ($file) {
        send_stored_file($file, $lifetime, 0, $forcedownload, $options);
        return true;
    } else {
        send_file_not_found();
    }
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_snap_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {

    if ($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, ['logo', 'favicon', 'fs_one_image', 'fs_two_image', 'fs_three_image'])) {
        $theme = theme_config::load('snap');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else if (($context->contextlevel == CONTEXT_SYSTEM || $context->contextlevel == CONTEXT_COURSE)
        && $filearea == 'coverimage' || $filearea == 'coursecard') {
        theme_snap_send_file($context, $filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

function theme_snap_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $PAGE;

    if ($PAGE->theme->name === 'snap') {
        if ($iscurrentuser) {
            $str = get_strings(['preferences']);
            if (isset($tree->nodes['editprofile'])) {
                $after = 'editprofile';
            } else {
                $after = null;
            }
            $url = new moodle_url('/user/preferences.php');
            $prefnode = new core_user\output\myprofile\node('contact', 'userpreferences', $str->preferences, $after, $url);

            $tree->add_node($prefnode);
        }
    }
}
