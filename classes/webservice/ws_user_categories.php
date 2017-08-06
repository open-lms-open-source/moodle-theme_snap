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

namespace theme_snap\webservice;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

/**
 * Change category web service
 * @author    Jerome Mouneyrac
 * @copyright Copyright (c) 2017 Bepaw Pty Ltd (http://bepaw.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_user_categories extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function user_categories_parameters() {
        $parameters = [
            'action' => new \external_value(PARAM_ALPHA, 'add, remove, list', VALUE_REQUIRED),
            'categoryid' => new \external_value(PARAM_INT, 'Category id', VALUE_DEFAULT, 0)
        ];
        return new \external_function_parameters($parameters);
    }

    /**
     * @return \external_single_structure
     */
    public static function user_categories_returns() {
        $keys = [
            'listing' => new \external_value(PARAM_TEXT, 'json string of current user listing', VALUE_REQUIRED),
            'error' => new \external_value(PARAM_TEXT, 'error message - if any', VALUE_DEFAULT, '')
        ];

        return new \external_single_structure($keys, 'result');
    }

    /**
     * @param $userid
     * @param $categoryid
     * @param $add
     * @return array
     */
    public static function user_categories($action, $categoryid = 0) {

        //TODO: support for querying someone else preferences (don't forget to check permissions, probably only admins.)

        // Parameter validation.
        $params = self::validate_parameters(
            self::user_categories_parameters(),
            array(
                'categoryid' => $categoryid,
                'action' => $action
            )
        );

        $usercategories = get_user_preferences('theme_snap_menu_categories');
        $usercategories = (array) json_decode($usercategories);

        $catid = $params['categoryid'];

        switch ($params['action']) {
            case 'add':
                $usercategories['catid_'.$catid] = $catid;
                set_user_preference('theme_snap_menu_categories', json_encode($usercategories));
                break;
            case 'remove':
                $usercategories = array_diff($usercategories, array($catid));
                set_user_preference('theme_snap_menu_categories', json_encode($usercategories));
                break;
            default:
                break;
        }

        $listing = "[";
        $notfirstitem = false;
        foreach($usercategories as $categoryid) {
            if ($notfirstitem) {
                $listing .= ',';
            } else {
                $notfirstitem = true;
            }
            $listing .= $categoryid;
        }
        $listing .= "]";

        return array('listing' => $listing);
    }

    /**
     * @return \external_function_parameters
     */
    public static function user_viewing_mode_parameters() {
        $parameters = [
            'value' => new \external_value(PARAM_ALPHA, 'categories || all || get (to just retrieve the value and not set it)',
                VALUE_DEFAULT, "get")
        ];
        return new \external_function_parameters($parameters);
    }

    /**
     * @return \external_single_structure
     */
    public static function user_viewing_mode_returns() {
        $keys = [
            'value' => new \external_value(PARAM_ALPHA, 'categories || all', VALUE_REQUIRED),
            'error' => new \external_value(PARAM_TEXT, 'error message - if any', VALUE_DEFAULT, '')
        ];

        return new \external_single_structure($keys, 'result');
    }

    /**
     * @param $value
     * @return array
     */
    public static function user_viewing_mode($value = "get") {

        // Parameter validation.
        $params = self::validate_parameters(
            self::user_viewing_mode_parameters(),
            array(
                'value' => $value
            )
        );

        $error = '';

        if ($params['value'] == "get" || empty($params['value'])) {
            $value = get_user_preferences('theme_snap_personal_menu_viewing_mode');

            // Reset the viewing mode if the site settings is disable.
            if ($value == 'categories' && !get_config('theme_snap', 'categoriestoggle')) {
                set_user_preference('theme_snap_personal_menu_viewing_mode', 'all');
                $value = 'all';
            }
        } else {
            if ($params['value'] != 'categories' && $params['value'] != 'all') {
                $error = 'Unknown value';
            } else {
                set_user_preference('theme_snap_personal_menu_viewing_mode', $params['value']);
            }
        }

        return array('value' => $value, 'error' => $error);
    }
}
