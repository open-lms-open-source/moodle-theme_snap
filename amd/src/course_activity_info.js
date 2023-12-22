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
 * Module to manage activity info in snap for tiles format
 *
 * @module     theme_snap/course_activity_info
 * @copyright  Copyright (c) 2023 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "theme_snap/ajax_notification"], function (
    $,
    ajax,
    ajaxNotify
) {
    var updateModCompletion = function (module, completionhtml) {
        // Update completion tracking icon.
        module.find(".snap-asset-completion-tracking").html(completionhtml);
        module.find(".btn-link").focus();
        $(document).trigger("snapModuleCompletionChange", module);
    };

    /**
     * Listen for manual completion toggle.
     */
    const listenManualCompletion = function () {
        $(".course-content").on("submit", "form.togglecompletion", function (e) {
            e.preventDefault();
            var form = $(this);

            if (form.hasClass("ajaxing")) {
                // Request already in progress.
                return;
            }

            var id = $(form).find('input[name="id"]').val();
            var completionState = $(form).find('input[name="completionstate"]').val();
            var module = $(form).parents("li.snap-asset").first();
            form.addClass("ajaxing");

            ajax.call(
                [
                    {
                        methodname: "theme_snap_course_module_completion",
                        args: { id: id, completionstate: completionState },
                        done: function (response) {
                            $("#module-" + id + " div.manual-completion-button").html(
                                response.completionhtml
                            );
                            ajaxNotify
                                .ifErrorShowBestMsg(response)
                                .done(function (errorShown) {
                                    form.removeClass("ajaxing");
                                    if (errorShown) {
                                        return;
                                    } else {
                                        // No errors, update completion html for this module instance.
                                        updateModCompletion(module, response.completionhtml);
                                    }
                                });
                        },
                        fail: function (response) {
                            ajaxNotify.ifErrorShowBestMsg(response).then(function () {
                                form.removeClass("ajaxing");
                            });
                        },
                    },
                ],
                true,
                true
            );
        });
    };

    const listenBtnReady = () => {
        $(document).on("btnready", () => {
            $(".manual-completion-button").each((index, btn) => {
                $(btn).hide();
                const parent = $(btn).parents().filter('#' + btn.id);
                if (parent.length > 0 && parent.attr('id') === btn.id) {
                    $(btn).show();
                }
            });
        });
    };

    return {
        init: function () {
            listenManualCompletion();
            listenBtnReady();
        }
    };
});
