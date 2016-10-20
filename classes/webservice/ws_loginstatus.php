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
 * Login status - logged in or not.
 * This is to get round the useless error messages that moodle returns when you aren't logged in - e.g:
 * "Course or activity not accessible."
 * Basically a work around for this issue - MDL-54551.
 *
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2016 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap\webservice;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../lib/externallib.php');

/**
 * Login status - logged in or not.
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2016 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ws_loginstatus extends \external_api {
    /**
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        $keys = [
            'failedactionmsg' => new \external_value(PARAM_TEXT, 'Failed action', VALUE_OPTIONAL)
        ];

        return new  \external_function_parameters($keys, 'login status params');
    }

    /**
     * @return \external_single_structure
     */
    public static function service_returns() {
        $keys = [
            'loggedin' => new \external_value(PARAM_BOOL, 'Logged in - true or false.', VALUE_REQUIRED),
            'loggedoutmsg' => new \external_value(PARAM_TEXT, 'Logged out message', VALUE_OPTIONAL),
            'loggedouttitle' => new \external_value(PARAM_TEXT, 'Logged out title', VALUE_OPTIONAL),
            'loggedoutcontinue' => new \external_value(PARAM_TEXT, 'Logged out continue button title', VALUE_OPTIONAL)
        ];

        return new \external_single_structure($keys, 'login status and error messages');
    }

    /**
     * @return array
     * @param string $failedactionmsg
     */
    public static function service($failedactionmsg = null) {
        $loggedin = isloggedin();
        $return = [
            'loggedin' => $loggedin
        ];
        if (!$loggedin) {
            if (!empty($failedactionmsg)) {
                $return['loggedoutmsg'] = get_string('loggedoutfailmsg', 'theme_snap', $failedactionmsg);
            } else {
                $return['loggedoutmsg'] = get_string('loggedoutmsg', 'theme_snap');
            }
            $return['loggedouttitle'] = get_string('loggedoutmsg', 'theme_snap');
            $return['loggedoutcontinue'] = get_string('continue');
        }
        return $return;
    }
}
