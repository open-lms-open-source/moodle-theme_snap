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
 * Steps definitions for behat theme.
 *
 * @package   theme_snap
 * @category  test
 * @copyright Copyright (c) 2018 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Check for color setup in categories
 *
 * @package   theme_snap
 * @category  test
 * @copyright Copyright (c) 2018 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_snap_category_colors extends behat_base {

    /**
     * Checks if classes are loaded to body on pages.
     *
     * @Given /^I check body for classes "(?P<classes_string>(?:[^"]|\\")*)"$/
     * @param string $classes classes separated by comma
     * @throws Exception
     */
    public function i_check_body_for_classes($classes) {
        $classesarray = explode(",", $classes);
        $xpath = '/body[';
        foreach ($classesarray as $key => $class) {
            $xpath .= 'contains(@class,"' . $class . '")';
            if ($key < count($classesarray) - 1) {
                $xpath .= " and ";
            }
        }
        $xpath .= "]";
        $this->find_all('xpath', $xpath);
    }

    /**
     * Checks if classes are loaded to body on pages.
     *
     * @Given /^I check element "(?P<element_string>(?:[^"]|\\")*)" with color "(?P<color_string>(?:[^"]|\\")*)"$/
     * @param string $element element to be checked
     * @param string $color hex color
     * @throws Exception
     */
    public function i_check_element_with_color($element, $color) {
        $session = $this->getSession();
        $elementcolor = $session->getDriver()->evaluateScript(
                'window.getComputedStyle(document.querySelectorAll("'
                . $element . '")[0], null).getPropertyValue("color");');
        $fromcolor = self::hex2rgb($color);
        $tocolor = self::rgb2array($elementcolor);

        if ($fromcolor !== $tocolor) {
            throw new Exception("Color " . $color . " was not found in element "
                    . $element . ", instead " . $elementcolor . " was found.");
        }
    }

    /**
     * Checks background color of an element, forcing focus to catch hover/focus styles.
     *
     * @Given /^I focus and check element "(?P<element_string>(?:[^"]|\\")*)" with background color "(?P<color_string>(?:[^"]|\\")*)"$/
     * @param string $element CSS selector to be checked
     * @param string $color hex color
     * @throws Exception
     */
    public function i_focus_and_check_element_with_background_color($element, $color) {
        $session = $this->getSession();

        // force Focus
        $javascript = <<<JS
        (function() {
            var el = document.querySelector("{$element}");
            if (!el) return "Element not found";
            
            // Set focus on element
            el.focus(); 
            
            // Return the background color.
            return window.getComputedStyle(el, null).getPropertyValue("background-color");
        })()
    JS;

        $elementcolor = $session->getDriver()->evaluateScript($javascript);

        if ($elementcolor === "Element not found") {
            throw new Exception("Element " . $element . " was not found in the DOM.");
        }

        // Compare Hex colors with RGB colors
        $fromcolor = self::hex2rgb($color);
        $tocolor = self::rgb2array($elementcolor);

        if ($fromcolor !== $tocolor) {
            throw new Exception("Background color " . $color . " was not found in element "
                . $element . ", instead " . $elementcolor . " was found.");
        }
    }

    /**
     * Checks if css element have a property with input value.
     *
     * @codingStandardsIgnoreStart
     * @Given /^I check element "(?P<element_string>(?:[^"]|\\")*)" with property "(?P<property_string>(?:[^"]|\\")*)" = "(?P<value_string>(?:[^"]|\\")*)"$/
     * @codingStandardsIgnoreEnd
     * @param string $element element to be checked
     * @param string $property property to be checked
     * @param string $value value of the property
     * @throws Exception
     */
    public function i_check_element_with_property($element, $property, $value) {
        $session = $this->getSession();
        $elementvalue = $session->getDriver()->evaluateScript(
            'window.getComputedStyle(document.querySelectorAll("'
            . $element . '")[0], null).getPropertyValue("' . $property . '");');

        if (strpos($elementvalue, 'rgb') !== false) {
            $elementvalue = self::rgb2array($elementvalue);
            $value = self::hex2rgb($value);
        } else {
            $value = self::unit_converter($element, $value);
        }

        if ($elementvalue !== $value) {
            throw new Exception( $property . " with value " . (is_array($value) ? implode(",", $value) : $value)
                    . " was not found in element " . $element . ", instead "
                    . (is_array($elementvalue) ? implode(",", $elementvalue) : $elementvalue)
                    . " was found.");
        }
    }

    /**
     * Checks if a CSS element's filter property contains a feColorMatrix recolor matching the given hex colour.
     *
     * The recolor-icon Sass mixin compiles to a filter: url(data:image/svg+xml;utf8,...) whose SVG embeds a
     * feColorMatrix with normalised (0-1) R/G/B values derived from the hex colour. This step extracts those
     * values via JavaScript, then compares them numerically so the assertion is immune to float-precision
     * differences between Sass and PHP string representations.
     *
     * @codingStandardsIgnoreStart
     * @Given /^I check element "(?P<element_string>(?:[^"]|\\")*)" has filter for color "(?P<color_string>[^"]*)"$/
     * @codingStandardsIgnoreEnd
     * @param string $element CSS selector of the element whose filter is checked.
     * @param string $hexcolor Expected colour in hex format (#RRGGBB).
     * @throws Exception
     */
    public function i_check_element_has_filter_for_color($element, $hexcolor) {
        $session = $this->getSession();

        // Extract the 20 feColorMatrix values from the element's computed filter.
        // Values are space-separated; positions 4, 9, 14 (0-based) are the R, G, B constants.
        // The regex skips over any quote/escape chars that browsers insert when normalising the SVG data URI.
        $js = <<<JS
(function() {
    var elem = document.querySelector("$element");
    if (!elem) { return "ERR:no-element"; }
    var filterVal = window.getComputedStyle(elem).getPropertyValue("filter");
    if (!filterVal || filterVal === "none") { return "ERR:no-filter:" + filterVal; }
    // Match "values=" followed by any non-digit chars (quotes, backslash-escapes, etc.),
    // then capture the space-separated float values.
    var match = filterVal.match(/values[^0-9]*([0-9][0-9 .]*[0-9 ])/);
    if (!match) { return "ERR:no-match:" + filterVal.substring(0, 200); }
    var parts = match[1].trim().split(/\s+/);
    if (parts.length < 15) { return "ERR:short-values:" + match[1]; }
    return parts[4] + "," + parts[9] + "," + parts[14];
})()
JS;
        $result = $session->getDriver()->evaluateScript($js);

        if ($result === null || substr($result, 0, 4) === 'ERR:') {
            throw new \Exception("No feColorMatrix filter found on element \"$element\": $result");
        }

        list($actualR, $actualG, $actualB) = array_map('floatval', explode(',', $result));

        // Convert hex to normalised (0-1) R/G/B, matching the Sass mixin formula.
        $hex = ltrim($hexcolor, '#');
        $expectedR = hexdec(substr($hex, 0, 2)) / 255;
        $expectedG = hexdec(substr($hex, 2, 2)) / 255;
        $expectedB = hexdec(substr($hex, 4, 2)) / 255;

        $tolerance = 0.005;
        if (
            abs($actualR - $expectedR) > $tolerance ||
            abs($actualG - $expectedG) > $tolerance ||
            abs($actualB - $expectedB) > $tolerance
        ) {
            throw new \Exception(
                "Filter colour mismatch on element \"$element\". " .
                "Expected RGB ({$expectedR}, {$expectedG}, {$expectedB}) for $hexcolor, " .
                "but got ({$actualR}, {$actualG}, {$actualB}) from filter."
            );
        }
    }

    /**
     * Function to get a RGB array from a hex color (1, 3, or 6 digits).
     * @param string $color color in hex format
     * @return array|bool
     */
    private static function hex2rgb($color) {
        preg_match("/^#{0,1}([0-9a-f]{1,6})$/i", $color, $match);
        if (!isset($match[1])) {
            return false;
        }
        $hex = $match[1];
        if (strlen($match[1]) == 6) {
            list($r, $g, $b) = array($hex[0].$hex[1], $hex[2].$hex[3], $hex[4].$hex[5]);
        } else if (strlen($match[1]) == 3) {
            list($r, $g, $b) = array($hex[0].$hex[0], $hex[1].$hex[1], $hex[2].$hex[2]);
        } else if (strlen($match[1]) == 2) {
            list($r, $g, $b) = array($hex[0].$hex[1], $hex[0].$hex[1], $hex[0].$hex[1]);
        } else if (strlen($match[1]) == 1) {
            list($r, $g, $b) = array($hex.$hex, $hex.$hex, $hex.$hex);
        } else {
            return false;
        }

        $color = array();
        $color['r'] = hexdec($r);
        $color['g'] = hexdec($g);
        $color['b'] = hexdec($b);

        return $color;
    }

    /**
     * Function to create a RGB array from string.
     * @param string $rgb rgb value in format rgb(R, G, B)
     * @return array|bool
     */
    private static function rgb2array($rgb) {
        if (strpos($rgb, 'rgba') !== false) {
            $pattern = '~^rgba?\((25[0-5]|2[0-4]\d|1\d{2}|\d\d?)\s*,\s*(25[0-5]|2[0-4]\d|1\d{2}|\d\d?)\s*,' .
            '\s*(25[0-5]|2[0-4]\d|1\d{2}|\d\d?)\s*(?:,\s*([01]\.?\d*?))?\)$~';
            preg_match($pattern, $rgb, $vals);
        } else {
            preg_match("/rgb\\((\\d{1,3}), (\\d{1,3}), (\\d{1,3})\\)/", $rgb, $vals);
        }
        if (!isset($vals[1])) {
            return false;
        }
        $color = array();
        $color['r'] = intval($vals[1]);
        $color['g'] = intval($vals[2]);
        $color['b'] = intval($vals[3]);

        return $color;
    }

    /**
     * Function to convert relative units to absolute units.
     *
     * @param string $element
     * @param string $value
     * @return string
     */
    private function unit_converter($element, $value) {
        $amount = floatval($value);
        $unit = explode($amount, $value)[1];
        if ($unit == 'em') { // Converts em to px.
            $session = $this->getSession();
            $fontsize = $session->getDriver()->evaluateScript(
                'window.getComputedStyle(document.querySelectorAll("'
                . $element . '")[0], null).getPropertyValue("font-size");'); // Return font size in px.
            $fontsize = floatval($fontsize);
            return ($amount * $fontsize).'px';
        }
        return $value;
    }
}
