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


/**
 * CSS Processor
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_snap_process_css($css, $theme) {

    // Set the background image for the logo.
    $logo = $theme->setting_file_url('logo', 'logo');
    $css = theme_snap_set_logo($css, $logo);

    // Set the background image for the poster.
    $poster = $theme->setting_file_url('poster', 'poster');
    $css = theme_snap_set_poster($css, $poster);

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
    $tag = '[[setting:logo]]';
    $replacement = $logo;
    if (is_null($replacement)) {
        $replacement = '';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

/**
 * Adds the poster to CSS.
 *
 * @param string $css The CSS.
 * @param string $poster The URL of the poster.
 * @return string The parsed CSS
 */
function theme_snap_set_poster($css, $poster) {
    $tag = '[[setting:poster]]';
    $replacement = $poster;
    if (is_null($replacement)) {
        $replacement = '';
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
    $tag = '[[setting:customcss]]';
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
    $settings['font-family-sans-serif'] = !empty($theme->settings->headingfont) ? $theme->settings->headingfont : 'Roboto';
    $settings['font-family-sans-serif'] .= ',"Fira Sans","Segoe UI","HelveticaNeue-Light","Helvetica Neue Light",' .
        '"Helvetica Neue",Helvetica, Arial, sans-serif';
    $settings['font-family-serif'] = !empty($theme->settings->seriffont) ? $theme->settings->seriffont : 'Georgia';
    $settings['font-family-serif'] .= ',"Times New Roman", Times, serif';
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

    $tag = '[[setting:snap-user-bootswatch]]';
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
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'poster' || $filearea === 'logo' || $filearea === 'favicon')) {
        $theme = theme_config::load('snap');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}


