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

define(['core/notification', 'core/ajax', 'core/templates', 'core/str'],

    function(notification, ajax, templates, str) {

        // Module level variables.
        var loginErrorShown = false;
        var loggingOut = false;

        // Module level code.
        $(document).ready(function() {
            $('#fixy-logout').click(function() {
                loggingOut = true;
            });
        });

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
             * @return boolean - error message shown?
             */
            ifErrorShowBestMsg : function(response, failAction, failMsg) {

                if (loginErrorShown) {
                    // We already have a login error message.
                    return true;
                }

                if (loggingOut) {
                    // No point in showing error messages if we are logging out.
                    return false;
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
                        var pwdChangeUrl = M.cfg.wwwroot+'/login/change_password.php';
                        // When a force password change is in effect, warn user in personal menu and redirect to
                        // password change page if appropriate.
                        if ($('#fixy-content').length) {
                            str.get_string('forcepwdwarningpersonalmenu', 'theme_snap', pwdChangeUrl).done(
                                function(forcePwdWarning) {
                                    var alertMsg = {"message": forcePwdWarning, "extraclasses": "force-pwd-warning"};
                                    templates.render('core/notification_warning', alertMsg)
                                        .done(function(result) {
                                            $('#fixy-content').html('<br />' + result);
                                        });
                                }
                            );
                            if ($('#fixy-content').is(':visible')) {
                                // If the personal menu is open then it should have a message in it informing the user
                                // that they need to change their password to proceed.
                                return true;
                            }
                        }

                        if (window.location.href.indexOf('login/change_password.php') > -1) {
                            // We are already on the change password page - avoid redirect loop!
                            return true;
                        }
                        window.location = pwdChangeUrl;
                        loginErrorShown = true; // Not really, but we only want this redirect to happen once.
                        return true;
                    }

                    /**
                     * Error notification function for non logged out issues.
                     * @param response
                     */
                    var errorNotification = function(response) {
                        if (failMsg) {
                            notification.alert(M.util.get_string('error', 'moodle'),
                                    failMsg, M.util.get_string('ok', 'moodle'));
                        } else {
                            if (response.backtrace) {
                                notification.exception(response);
                            } else {
                                var errorstr;
                                if (response.error) {
                                    errorstr = response.error;
                                    if (response.stacktrace) {
                                        errorstr = '<div>' + errorstr + '<pre>' + response.stacktrace + '</pre></div>';
                                    }

                                } else {
                                    if (response.errorcode && response.message) {
                                        errorstr = response.message;
                                    } else {
                                        errorstr = M.util.get_string('unknownerror', 'moodle');
                                    }
                                }
                                notification.alert(M.util.get_string('error', 'moodle'),
                                        errorstr, M.util.get_string('ok', 'moodle'));
                            }
                        }
                    };

                    // Ajax call login status function to see if we are logged in or not.
                    // Note, we can't use a moodle web service for this ajax call because it will not provide
                    // an error response that we can rely on - see MDL-54551.
                    failAction = failAction ? failAction : '';
                    $.ajax({
                        type: "POST",
                        async: true,
                        data: {
                            "sesskey" : M.cfg.sesskey,
                            "failedactionmsg" : failAction
                        },
                        url: M.cfg.wwwroot + '/theme/snap/rest.php?action=get_loginstatus'
                    }).done(function(thisResp) {
                        if (loginErrorShown) {
                            return true;
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
                            loginErrorShown = true;
                        } else {
                            // This is not a login issue, show original error message.
                            console.log(response);
                            errorNotification(response);
                        }
                    });
                    return true;
                }

                return false;
            }
        };
    }
);
