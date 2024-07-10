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
 * @package
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log', 'core/ajax', 'core/notification', 'theme_snap/ajax_notification', './cropper'],
    function($, log, ajax, notification, ajaxNotify, Cropper) {

        var cropper = null;
        var originalImageURL = '';
        var cropperRatio = 6/1;
        if ($('#page-site-index').length) {
            cropperRatio = 6/2;
        }
        // TODO - in Moodle 3.1 we should use the core template for this.
        var addCoverImageAlert = function(id, msg, position = null) {
            if (position === "dialogue") {
                var alertPosition = '.snap_cover_image_description';
            } else {
                var alertPosition = '#snap-coverimagecontrol';
            }
            var closestr = M.util.get_string('closebuttontitle', 'moodle');
            if (!$(id).length) {
                $(alertPosition).before(
                    '<div id="' + id + '" class="snap-alert-cover-image alert alert-warning" role="alert">' +
                    msg +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="' + closestr + '">' +
                    '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                    '</div>'
                );
            }
        };

        /**
         * Get human file size from bytes.
         * http://stackoverflow.com/questions/10420352/converting-file-size-in-bytes-to-human-readable.
         * @param {int} size
         * @returns {string}
         */
        var humanFileSize = function(size) {
            var i = Math.floor(Math.log(size) / Math.log(1024));
            return (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
        };

        /**
         * First state - image selection button visible.
         */
        var state1 = function() {
            $('#snap-changecoverimageconfirmation .ok').removeClass('ajaxing');
            $('#snap-alert-cover-image-size').remove();
            $('#snap-alert-cover-image-bytes').remove();
            $('label[for="snap-coverfiles"]').removeClass('ajaxing');
            $('#snap-changecoverimageconfirmation').removeClass('state-visible');
            $('label[for="snap-coverfiles"]').addClass('state-visible');
            $('#snap-coverfiles').val('');
            $('body').removeClass('cover-image-change');
        };

        /**
         * Second state - confirm / cancel buttons visible.
         */
        var state2 = function() {
            $('#snap-alert-cover-image-upload-failed').remove();
            $('#snap-changecoverimageconfirmation').removeClass('disabled');
            $('label[for="snap-coverfiles"]').removeClass('state-visible');
            $('#snap-changecoverimageconfirmation').addClass('state-visible');
            $('body').removeClass('cover-image-change');
        };

        /**
         * Get original image url.
         * @param {string} url
         */
        var getOriginalImageURL = function(url) {
            var newURL = url;
            newURL = newURL.replace("url(", "");
            newURL = newURL.replace(")", "");
            newURL = newURL.replace(/['"]+/g, "");
            newURL = newURL.replace(";", "");
            newURL = newURL.replace("croppedimage", "coverimage");
            newURL = newURL.replace("course-image-cropped", "course-image");
            newURL = newURL.replace("category-image-cropped", "category-image");
            newURL = newURL.replace("site-image-cropped", "site-image");
            return newURL;
        };

        /**
         * Get cropped image url.
         * @param {string} url
         */
        var getCroppedImageURL = function(url) {
            var newURL = url;
            newURL = newURL.replace("url(", "");
            newURL = newURL.replace(")", "");
            newURL = newURL.replace(/['"]+/g, "");
            newURL = newURL.replace(";", "");
            return newURL;
        };

        /**
         * Load current image.
         */
        var loadCurrentImage = function() {
            var currentImageUrl = $('#page-header').css("background-image");
            if (currentImageUrl === "none") {
                $('.snap_cover_image_save_button').attr("disabled", true);
                return "";
            }
            $('.snap_cover_image_save_button').prop("disabled", false);
            currentImageUrl = getOriginalImageURL(currentImageUrl);
            return currentImageUrl;
        };

        /**
         * Moodle dialogue box.
         * @param {string} courseShortName
         * @param {int} categoryId
         * @param {object} fpoptions
         * @param {int} siteMaxBytes
         */
        var moodledialogue = function(courseShortName, categoryId, fpoptions, siteMaxBytes) {
            var maxbytesstr = humanFileSize(siteMaxBytes);
            let title = M.util.get_string('imageproperties', 'theme_snap');
            let coverImageDesc = M.util.get_string('coverimagedesc', 'theme_snap', maxbytesstr);
            let coverImageCropperDesc = M.util.get_string('coverimagecropperdesc', 'theme_snap', maxbytesstr);
            let browseRepositories = M.util.get_string('browserepositories', 'theme_snap');
            let selectImageString = M.util.get_string('selectimage', 'theme_snap');
            let previewDisplay = "'display:none'";
            let currentImageURL = loadCurrentImage();
            originalImageURL = currentImageURL;
            if (currentImageURL !== "") {
                 previewDisplay = "'display:block'";
            }

            let content =
                '<div class="mb-1 snap_cover_image_dialogue">' +
                    '<p class="snap_cover_image_description">' + coverImageDesc + '</p>' +
                    '<p class="snap_cover_image_cropper_description d-none">' + coverImageCropperDesc + '</p>' +
                    '<div class="input-group input-append w-100 snap_cover_image_browser_options">' +
                        '<button class="btn btn-secondary snap_cover_image_browser" id="id_snap_cover_image_browser">' +
                        browseRepositories + '</button>' +
                    '</div>' +
                // Add the image preview.
                '<div class="mdl-align">' +
                    '<div class="snap_cover_image_preview_box">' +
                        '<img id="id_snap_cover_image_preview" class="snap_cover_image_preview" alt="" ' +
                            'style=' + previewDisplay +
                            'src=' + currentImageURL + '>' +
                    '</div>' +
                '</div>' +
                // Add the save button.
                '<div class="snap_cover_image_save">' +
                        '<button class="btn btn-secondary snap_cover_image_save_button" id="id_snap_cover_image_save_button" ' +
                        'disabled>' +
                         selectImageString + '</button>' +
                    '</div>' +
                '</div>';

            var dialogue = new M.core.dialogue({
                headerContent: title,
                bodyContent: content,
                width: '600px',
                modal: true,
                visible: false,
                render: true,
                additionalBaseClass: 'snap_cover_image_dialogue',
            });
            dialogue.show();

            // Preview the original image in the modal.
            if (currentImageURL !== "") {
                $('.snap_cover_image_save_button').prop("disabled", false);
                if (cropper) {
                    cropper.destroy();
                }
                // Initialize cover image cropper.
                var imageToCrop = document.getElementById('id_snap_cover_image_preview');
                cropper = new Cropper(imageToCrop, {
                    viewMode: 3,
                    aspectRatio: cropperRatio,
                    dragMode: "none",
                    zoomable: false,
                    minCropBoxWidth: 300,
                });
                $('.snap_cover_image_cropper_description').removeClass('d-none');
                $('#id_snap_cover_image_browser').click(function() {
                    if (cropper) {
                        cropper.destroy();
                    }
                });
                $('#id_snap_cover_image_save_button').click(function() {
                    if (cropper.getCroppedCanvas() !== null) {
                        var croppedImage = cropper.getCroppedCanvas().toDataURL("image/png");
                        // Ensure that the page-header in courses has the mast-image class.
                        $('.path-course-view #page-header').addClass('mast-image');
                        $('.path-course-view #page-header .breadcrumb-item a').addClass('mast-breadcrumb');

                        $('#page-header').css('background-image', 'url(' + croppedImage + ')');
                        state2();
                        saveImage({}, courseShortName, categoryId, cropper);
                    }
                });
            }

            $('body').addClass('cover-image-change');
            $('label[for="snap-coverfiles"]').addClass('ajaxing');

            $('#id_snap_cover_image_browser').click(function(e) {
                e.preventDefault();
                showFilepicker('image', fpoptions, filepickerCallback(courseShortName, categoryId), dialogue);
            });
            $('#id_snap_cover_image_save_button').click(function() {
                dialogue.hide();
            });
            $('.snap_cover_image_dialogue .closebutton, .moodle-dialogue-lightbox').click(function() {
                state1();
            });
            dialogue.after("visibleChange", function() {
                if ($('#snap-changecoverimageconfirmation .ok').hasClass('ajaxing')) {
                    state2();
                }
                if (!dialogue.get('visible')) {
                    dialogue.destroy(true);
                }
            });
        };

        /**
         * Load the image in the preview box after being uploaded using the file picker.
         * @param {object} params
         * @param {string} courseShortName
         * @param {int} categoryId
         */
        var loadPreviewImage = function(params, courseShortName, categoryId) {

            var image = new Image();
            image.onerror = function() {
                var preview = document.getElementById('id_snap_cover_image_preview');
                preview.setAttribute('style', 'display:none');
            };

            image.onload = function() {
                var input;
                var imageWidth = this.width;
                input = document.getElementById('id_snap_cover_image_preview');
                input.setAttribute('src', params.url);
                input.setAttribute('style', 'display:block');
                $('.snap_cover_image_save_button').prop("disabled", false);

                // Warn if image resolution is too small.
                if (imageWidth < 1024) {
                    $('#snap-alert-cover-image-size').remove();
                    addCoverImageAlert('snap-alert-cover-image-size',
                        M.util.get_string('error:coverimageresolutionlow', 'theme_snap'),
                        'dialogue'
                    );
                } else {
                    $('#snap-alert-cover-image-size').remove();
                }

                if (cropper !== null) {
                    cropper.replace(params.url);
                } else {
                    // Initialize cover image cropper.
                    var imageToCrop = document.getElementById('id_snap_cover_image_preview');
                    cropper = new Cropper(imageToCrop, {
                        viewMode: 3,
                        aspectRatio: cropperRatio,
                        dragMode: "none",
                        zoomable: false,
                        minCropBoxWidth: 300,
                    });
                }
                $('.snap_cover_image_cropper_description').removeClass('d-none');
                $('#id_snap_cover_image_browser').click(function() {
                    if (cropper) {
                        cropper.destroy();
                    }
                });
                $('#id_snap_cover_image_save_button').click(function() {
                    var croppedImage = cropper.getCroppedCanvas().toDataURL("image/png");
                    // Ensure that the page-header in courses has the mast-image class.
                    $('.path-course-view #page-header').addClass('mast-image');
                    $('.path-course-view #page-header .breadcrumb-item a').addClass('mast-breadcrumb');

                    $('#page-header').css('background-image', 'url(' + croppedImage + ')');

                    state2();
                    saveImage(params, courseShortName, categoryId, cropper);
                });

            };
            image.src = params.url;
        };


        /**
         * Callback for file picker.
         * @param {string} courseShortName
         * @param {int} categoryId
         */
        var filepickerCallback = function(courseShortName, categoryId) {
            return function(params) {
            if (params.url !== '') {
                // Load the preview image.
                loadPreviewImage(params, courseShortName, categoryId);
                }
            };
        };

        /**
         * Create file picker.
         * @param {string} type
         * @param {object} fpoptions
         * @param {Function} callback
         * @param {object} dialogue
         */
        var showFilepicker = function(type, fpoptions, callback, dialogue) {
            Y.use('core_filepicker', function(Y) {
                var options = fpoptions;
                options.formcallback = callback;
                M.core_filepicker.show(Y, options);
            });
            $('.filepicker .closebutton').click(function() {
                dialogue.destroy(true);
                state1();
            });
        };

        /**
         * Save image after confirmation.
         * @param {object} params
         * @param {string} courseShortName
         * @param {int} categoryId
         * @param {object} cropper
         */
        var saveImage = function(params, courseShortName, categoryId, cropper) {

            $('#snap-changecoverimageconfirmation .ok').click(function() {
                var ajaxParams = {};

                if (categoryId !== null) {
                    ajaxParams.categoryid = categoryId;
                } else if (courseShortName !== null) {
                    ajaxParams.courseshortname = courseShortName;
                } else {
                    return;
                }

                if (params.id !== undefined) {
                    ajaxParams.fileid = params.id;
                } else if (params.file !== undefined) {
                        var fileNameWithoutSpaces = params.file.replace(/ .*/, "");
                        var regex = new RegExp("draft\\/(\\d+)\\/" + fileNameWithoutSpaces, "g");
                        var urlId = params.url.match(regex);
                        ajaxParams.fileid = urlId[0].match(/\d+/)[0];
                }

                if (params.file !== undefined) {
                    ajaxParams.imagefilename = params.file;
                }
                if (params.url !== undefined) {
                    ajaxParams.originalimageurl = params.url;
                } else {
                    ajaxParams.originalimageurl = originalImageURL;
                }

                cropper.getCroppedCanvas().toBlob((blob) => {
                    var reader = new FileReader();
                    reader.readAsDataURL(blob);
                    reader.onloadend = function() {
                        var imageBase64Data = reader.result;
                        ajaxParams.croppedimagedata = imageBase64Data;
                        ajax.call([
                            {
                                methodname: 'theme_snap_cover_image',
                                args: {params: ajaxParams},
                                done: function(response) {
                                    state1();
                                    if (response.contrast) {
                                        addCoverImageAlert('snap-alert-cover-image-size',
                                            response.contrast
                                        );
                                    }
                                    if (!response.success && response.warning) {
                                        addCoverImageAlert('snap-alert-cover-image-upload-failed', response.warning);
                                    }
                                    var newUrl = getCroppedImageURL(response.imageurl);
                                    $('#page-header').css('background-image', 'url(' + newUrl + ')');
                                    $('#page-header').data('servercoverfile', $('#page-header').css('background-image'));
                                    $('#snap-changecoverimageconfirmation .ok').off("click");

                                },
                                fail: function(response) {
                                    state1();
                                    ajaxNotify.ifErrorShowBestMsg(response);
                                }
                            }
                        ], true, true);
                    };
                });

            });

            $('#snap-changecoverimageconfirmation .cancel').click(function() {

                if ($(this).parent().hasClass('disabled')) {
                    return;
                }
                $('#page-header').css('background-image', $('#page-header').data('servercoverfile'));
                if ($('#page-header').data('servercoverfile') === "none") {
                    $('.path-course-view #page-header').removeClass('mast-image');
                    $('.path-course-view #page-header .breadcrumb-item a').removeClass('mast-breadcrumb');
                }
                state1();
                params = {};
            });
        };

        /**
         *
         * @param {object} ajaxParams
         * @param {string} courseShortName
         * @param {int} categoryId
         * @param {int} siteMaxBytes
         */
        var coverImage = function(ajaxParams, courseShortName = null, categoryId = null, siteMaxBytes) {

            if (courseShortName === null && categoryId === null) {
                return;
            }

            ajax.call([
                {
                    methodname: 'theme_snap_file_manager_options',
                    args: [],
                    done: function(data) {
                        var fpoptions = JSON.parse(data.fpoptions);
                        // Take a backup of what the current background image url is (if any).
                        $('#page-header').data('servercoverfile', $('#page-header').css('background-image'));
                        $('#snap-coverimagecontrol').addClass('snap-js-enabled');
                        $('#snap-coverfiles').click(function() {
                            moodledialogue(courseShortName, categoryId, fpoptions, siteMaxBytes);
                        });
                    },
                    fail: function() {
                        return;
                    }
                }
            ], true, true);
        };

        /**
         * @param {int} categoryId
         * @param {int} siteMaxBytes
         */
        var categoryCoverImage = function(categoryId, siteMaxBytes) {
            var ajaxParams = {imagefilename: null, imagedata: null, categoryid: categoryId,
                    courseshortname: null};
            coverImage(ajaxParams, null, categoryId, siteMaxBytes);
        };

        /**
         * @param {string} courseShortName
         * @param {int} siteMaxBytes
         */
        var courseCoverImage = function(courseShortName, siteMaxBytes) {
            var ajaxParams = {imagefilename: null, imagedata: null, categoryid: null,
                    courseshortname: courseShortName};

            coverImage(ajaxParams, courseShortName, null, siteMaxBytes);
        };
        return {courseImage: courseCoverImage, categoryImage: categoryCoverImage};
    }
);
