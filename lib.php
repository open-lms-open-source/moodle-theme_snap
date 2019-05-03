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
 * @copyright Copyright (c) 2015 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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

    $css = theme_snap_set_category_colors($css, $theme);

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

    return $css;
}

/**
 * Adds the custom category colors to the CSS.
 *
 * @param string $css The CSS.
 * @return string The updated CSS
 */
function theme_snap_set_category_colors($css, $theme) {
    global $DB;

    $tag = '/**setting:categorycolors**/';
    $replacement = '';

    // Get category colors from database.
    $categorycolors = array();
    $dbcategorycolors = get_config("theme_snap", "category_color");
    if (!empty($dbcategorycolors) && $dbcategorycolors != '0') {
        $categorycolors = json_decode($dbcategorycolors, true);
    }

    if (!empty($categorycolors)) {
        $colors = $categorycolors;

        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($colors));
        $categories = $DB->get_records_select(
            'course_categories',
            'id ' . $insql,
            $inparams,
            // Ordered by path ascending so that the colors of child categories overrides,
            // parent categories by coming later in the CSS output.
            'path ASC'
        );

        $themedirectory = realpath(core_component::get_component_directory('theme_snap'));
        $brandscss = file_get_contents($themedirectory . '/scss/_brandcolor.scss');
        foreach ($categories as $category) {
            $compiler = new core_scss();
            // Rewrite wrapper class with current category id.
            $categoryselector = '.category-' . $category->id . ' {';
            $scss = str_replace('.theme-snap {', $categoryselector, $brandscss);
            $compiler->append_raw_scss($scss);
            $compiler->add_variables([
                'brand-primary' => $colors[$category->id],
                'nav-color' => $colors[$category->id],
                'nav-button-color' => $colors[$category->id],
                'nav-login-bg' => $colors[$category->id],
                'nav-login-color' => '#FFFFFF'
            ]);

            try {
                $compiled = $compiler->to_css();
            } catch (\Leafo\ScssPhp\Exception $e) {
                $compiled = '';
                debugging('Error while compiling SCSS: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
            $replacement = $replacement . $compiled;
        }
    }

    $css = str_replace($tag, $replacement, $css);
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
        $replacement = "#snap-home.logo, .snap-logo-sitename {background-image: url($logo);}";
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

    $coverimagecontexts = [CONTEXT_SYSTEM, CONTEXT_COURSE, CONTEXT_COURSECAT];

    // System level file areas.
    $sysfileareas = [
        'logo',
        'favicon',
        'fs_one_image',
        'fs_two_image',
        'fs_three_image',
        'slide_one_image',
        'slide_two_image',
        'slide_three_image'
    ];

    if ($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, $sysfileareas)) {
        $theme = theme_config::load('snap');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else if (in_array($context->contextlevel, $coverimagecontexts)
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

function theme_snap_get_main_scss_content($theme) {
    global $CFG;

    // Note, the following code is not fully used yet, only the hardcoded
    // pre and post scss files will be loaded, not any presets defined by
    // settings.

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($filename == 'default.scss') {
        // We still load the default preset files directly from the boost theme. No sense in duplicating them.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        // We still load the default preset files directly from the boost theme. No sense in duplicating them.
        $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/plain.scss');

    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_snap', 'preset', 0, '/', $filename))) {
        // This preset file was fetched from the file area for theme_snap and not theme_boost (see the line above).
        $scss .= $presetfile->get_content();
    } else {
        $scss = '@import "boost";';
    }

    // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
    $pre = file_get_contents($CFG->dirroot . '/theme/snap/scss/pre.scss');
    // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
    $post = file_get_contents($CFG->dirroot . '/theme/snap/scss/post.scss');

    // Combine them together.
    return $pre . "\n" . $scss . "\n" . $post;
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return array
 */
function theme_snap_get_pre_scss($theme) {
    global $CFG;

    $scss = '';

    $settings['brand-primary'] = !empty($theme->settings->themecolor) ? $theme->settings->themecolor : '#3bcedb';
    $userfontsans  = $theme->settings->headingfont;
    if (empty($userfontsans) || in_array($userfontsans, ['Roboto', '"Roboto"'])) {
        $userfontsans = '';
    } else {
        $userfontsans .= ",";
    }
    $fallbacksans = 'Roboto, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
    $settings['font-family-feature'] = $userfontsans . $fallbacksans;

    $userfontserif = $theme->settings->seriffont;
    if (empty($userfontserif) || in_array($userfontserif, ['Georgia', '"Georgia"'])) {
        $userfontserif = '';
    } else {
        $userfontserif .= ",";
    }
    $fallbackserif = 'Georgia,"Times New Roman", Times, serif';
    $settings['font-family-serif'] = $userfontserif . $fallbackserif;

    if (!empty($theme->settings->customisenavbar)) {
        $settings['nav-bg'] = !empty($theme->settings->navbarbg) ? $theme->settings->navbarbg : '#ffffff';
        $settings['nav-color'] = !empty($theme->settings->navbarlink) ? $theme->settings->navbarlink : $settings['brand-primary'];
    }
    if (!empty($theme->settings->customisenavbutton)) {
        $settings['nav-button-bg'] = !empty($theme->settings->navbarbuttoncolor) ? $theme->settings->navbarbuttoncolor : "#ffffff";

        if (!empty($theme->settings->navbarbuttonlink)) {
            $settings['nav-button-color'] = $theme->settings->navbarbuttonlink;
        } else {
            $settings['nav-button-color'] = $settings['brand-primary'];
        }
    }

    foreach ($settings as $key => $value) {
        $scss .= '$' . $key . ': ' . $value . ";\n";
    }

    return $scss;
}

/**
 * Calculates the luminosity of an given RGB color.
 * the color code must be in the format of RRGGBB.
 * the luminosity equations are from the WCAG 2 requirements.
 * http://www.w3.org/TR/WCAG20/#relativeluminancedef
 *
 * @param string $color1
 * @param string $color2
 * @return float
 */
function theme_snap_calculate_luminosity($color) {
    $r = hexdec(substr($color, 1, 2)) / 255; // Red value.
    $g = hexdec(substr($color, 3, 2)) / 255; // Green value.
    $b = hexdec(substr($color, 5, 2)) / 255; // Blue value.
    if ($r <= 0.03928) {
        $r = $r / 12.92;
    } else {
        $r = pow((($r + 0.055) / 1.055), 2.4);
    }
    if ($g <= 0.03928) {
        $g = $g / 12.92;
    } else {
        $g = pow((($g + 0.055) / 1.055), 2.4);
    }
    if ($b <= 0.03928) {
        $b = $b / 12.92;
    } else {
        $b = pow((($b + 0.055) / 1.055), 2.4);
    }
    $luminosity = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    return $luminosity;
}

/**
 * calculates the luminosity ratio of two colors.
 * the luminosity ratio equations are from the WCAG 2 requirements.
 * http://www.w3.org/TR/WCAG20/#contrast-ratiodef
 *
 * @param string $color1
 * @param string $color2
 * @return float
 */
function theme_snap_calculate_luminosity_ratio($color1, $color2) {
    $l1 = theme_snap_calculate_luminosity($color1);
    $l2 = theme_snap_calculate_luminosity($color2);
    if ($l1 > $l2) {
        $ratio = (($l1 + 0.05) / ($l2 + 0.05));
    } else {
        $ratio = (($l2 + 0.05) / ($l1 + 0.05));
    }
    return $ratio;
}

/**
 * Returns the calculated contrast ratio.
 * the ratio levels are from the WCAG 2 requirements.
 * http://www.w3.org/TR/WCAG20/#visual-audio-contrast (1.4.3)
 * http://www.w3.org/TR/WCAG20/#larger-scaledef
 *
 * @param string $color1
 * @param string $color2
 * @return float
 */
function theme_snap_evaluate_color_contrast($color1, $color2) {
    $ratio = theme_snap_calculate_luminosity_ratio($color1, $color2);
    return $ratio;
}

/**
 * Compares category colors, if they exist, to different setup colors.
 * @return string|void
 */
function theme_snap_compare_cat_colors() {
    $catcolors = get_config('theme_snap', 'category_color');
    $catcolorelements = json_decode($catcolors);
    if ($catcolorelements === false || $catcolorelements == null) {
        return;
    }
    $iscustomnavbaron = get_config('theme_snap', 'customisenavbar');
    $iscustomnavbuttonon = get_config('theme_snap', 'customisenavbutton');
    $navbarcolorbk = get_config('theme_snap', 'navbarbg');
    $navbarbutcolorbk = get_config('theme_snap', 'navbarbuttoncolor');
    $colorsdontcontrast = [];
    foreach ($catcolorelements as $key => $catcolorelement) {
        if ($iscustomnavbaron) {
            $contrast = theme_snap_evaluate_color_contrast($catcolorelement, $navbarcolorbk);
            if ($contrast < 4.5) {
                array_push($colorsdontcontrast, $key);
                continue;
            }
        }
        if ($iscustomnavbuttonon) {
            $contrast = theme_snap_evaluate_color_contrast($catcolorelement, $navbarbutcolorbk);
            if ($contrast < 4.5) {
                array_push($colorsdontcontrast, $key);
                continue;
            }
        }
        $contrast = theme_snap_evaluate_color_contrast($catcolorelement, "#FFFFFF");
        if ($contrast < 4.5) {
            array_push($colorsdontcontrast, $key);
        }
    }
    if (!empty($colorsdontcontrast)) {
        $message = get_string('catinvalidratio', 'theme_snap', implode(', ', $colorsdontcontrast));
    }
    return $message;
}

/**
 * Compares colors depending on the setup.
 * @param $identifier
 * @return array|float
 */
function theme_snap_compare_colors($identifier) {
    if ($identifier == 1) {
        $ratio = theme_snap_compare_basics_color();
    } else if ($identifier == 2) {
        $ratio = theme_snap_compare_navbar_color();
    } else if ($identifier == 3) {
        $ratio = theme_snap_compare_navbarbutton_color();
    }
    return $ratio;
}

/**
 * Compares the theme color with white color.
 * @return float
 */
function theme_snap_compare_basics_color() {
    $basiccolor = get_config('theme_snap', 'themecolor');
    $contrast = theme_snap_evaluate_color_contrast($basiccolor, "#FFFFFF");
    return $contrast;
}

/**
 * Compares the colors of the nav bar background and link.
 * @return float
 */
function theme_snap_compare_navbar_color() {
    $navbarcolor1 = get_config('theme_snap', 'navbarbg');
    $navbarcolor2 = get_config('theme_snap', 'navbarlink');
    $contrast = theme_snap_evaluate_color_contrast($navbarcolor1, $navbarcolor2);
    return $contrast;
}

/**
 * Compare nav bar button colors to nav bar button link
 * @return array
 */
function theme_snap_compare_navbarbutton_color() {
    $navbarbutcolor1 = get_config('theme_snap', 'navbarbuttoncolor');
    $navbarbutcolor2 = get_config('theme_snap', 'navbarbuttonlink');
    $contrast = theme_snap_evaluate_color_contrast($navbarbutcolor1, $navbarbutcolor2);
    return $contrast;
}

/**
 * Gets the pixel average colour in the third top left part of the image.
 * @param stored_file $originalfile Image file to be processed.
 * @param array $fileinfo Image info.
 * @return hex color.
 */
function theme_snap_calculate_image_main_color(\stored_file $originalfile, array $fileinfo) {
    // Copy file to temp directory to avoid messing up the original file.
    $tmpimage = tempnam(sys_get_temp_dir(), 'tmpimg');
    \file_put_contents($tmpimage, $originalfile->get_content());

    // Create resource depending on mime type.
    $mimetype = $fileinfo['mimetype'];
    if ($mimetype == 'image/jpeg') {
        $resource = imagecreatefromjpeg($tmpimage);
    } else if ($mimetype == 'image/png') {
        $resource = imagecreatefrompng($tmpimage);
    } else if ($mimetype == 'image/gif') {
        $resource = imagecreatefromgif($tmpimage);
    }

    // Calculate the average pixel colour for the third left part of the image.
    $widththird = $fileinfo['width'] / 3;
    $height = $fileinfo['height'] / 2;
    $totalvalue = 0;
    $pixelcount = 0;

    for ($i = 0; $i < $widththird; $i++) {
        for ($j = 0; $j < $height; $j++) {
            $pixelint = imagecolorat($resource, $i, $j);
            $totalvalue += $pixelint;
            $pixelcount++;
        }
    }

    $average = $totalvalue / $pixelcount;

    $cols = imagecolorsforindex($resource, $average);
    $redhex = substr("00" . dechex($cols['red']), -2);
    $greenhex = substr("00" . dechex($cols['green']), -2);
    $bluehex = substr("00" . dechex($cols['blue']), -2);
    $pixelhex = "#" . $redhex . $greenhex . $bluehex;

    return $pixelhex;
}