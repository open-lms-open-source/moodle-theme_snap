/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   theme_snap
 * @author    Guy Thomas <gthomas@moodlerooms.com>
 * @copyright Copyright (c) 2016 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/notification', 'core/ajax'],

    function(notification, ajax) {

        var staticLoginErrorShown = false;

        /**
         * This feature is simply to work around this issue - MDL-54551.
         * If the core moodle issue is ever fixed we might not require this module.
         */
        return {

            /**
             * If there is an error in this response then show the best error message for the user.
             *
             * @param response
             * @param failaction
             * @return bool
             */
            ifErrorShowBestMsg : function(response, failAction, failMsg) {

                if (staticLoginErrorShown) {
                    // We already have a login error message.
                    return true;
                }

                if (typeof response !== 'object') {
                    try {
                        var jsonObj = JSON.parse(response);
                        response = jsonObj;
                    } catch (e) {}
                }

                if (typeof response  === 'undefined') {
                    // Assume error.
                    response = {error: M.util.get_string('unknownerror', 'core')};
                }

                if (response.error || response.errorcode) {

                    if (M.snapTheme.forcePassChange) {
                        // When a force password change is in effect it breaks the theme_snap_loginstatus method.
                        // Send user to page for changing password:
                        window.location = M.cfg.wwwroot+'/login/change_password.php';
                        staticLoginErrorShown = true; // Not really, but we only want this redirect to happen once.
                        return;
                    }

                    /**
                     * Error notification function for non logged out issues.
                     * @param response
                     */
                    var errorNotification = function(response) {
                        if (failMsg) {
                            notification.alert(null, failMsg, M.util.get_string('ok', 'moodle'));
                        } else {
                            notification.exception(response);
                        }
                    };

                    // Ajax call login status function to see if we are logged in or not.
                    failAction = failAction ? failAction : '';
                    var args = {
                        failedactionmsg: failAction
                    };

                    ajax.call([
                        {
                            methodname: 'theme_snap_loginstatus',
                            args: args,
                            done: function(thisResp) {
                                if (staticLoginErrorShown) {
                                    return;
                                }
                                // Show login error message or original error message.
                                if (!thisResp.loggedin) {
                                    // Hide ALL confirmation dialog 2nd buttons and close buttons.
                                    // Note - this is not ideal but at this point we need to log in anyway, so not
                                    // an issue.
                                    $('<style>' +
                                        '.confirmation-dialogue .confirmation-buttons input:nth-of-type(2), ' +
                                        '.moodle-dialogue-base.moodle-dialogue-confirm button.yui3-button.closebutton' +
                                        '{ display : none }' +
                                        '</style>'
                                    ).appendTo('head');
                                    notification.confirm(
                                        thisResp.loggedouttitle,
                                        thisResp.loggedoutmsg,
                                        thisResp.loggedoutcontinue,
                                        ' ',
                                        function() {
                                            window.location = M.cfg.wwwroot+'/login/index.php';
                                        }
                                    );
                                    staticLoginErrorShown = true;
                                  } else {
                                    // This is not a login issue, show original error message.
                                    errorNotification(response);
                                }
                            },
                            fail: function() {
                                // The ajax call to determine the login status failed, just show original error message.
                                errorNotification(response);
                            }
                        }
                    ], false, false);
                    // Has a login error.
                    return true;
                }
                // No login error.
                return false;
            }
        };
    }
);
