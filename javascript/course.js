/**
 * Created by gthomas2 on 6/18/15.
 */
M.theme_snap = M.theme_snap || {
    courseid : false
};
M.theme_snap.course = {
    init : function () {

        /**
         * Item being moved - actual dom element.
         * @type {object|boolean}
         */
        var movingobject = false;

        /**
         * Name of item being moved.
         * @type {string|null}
         */
        var moving = null;

        /**
         * @type {boolean}
         */
        var ajaxing = false;

        /**
         * @type {*|jQuery|HTMLElement}
         */
        var snap_move_message = $('#snap-move-message');

        /**
         * Logging function.
         *
         * @param msg
         * @param object
         */
        var log = function (msg, object) {
            if (!M.cfg.developerdebug) {
                return;
            }
            if (object) {
                console.log(msg, object);
            } else {
                console.log(msg)
            }
        }

        /**
         * General move request
         *
         * @param srcid
         * @param targetid
         * @param actionclass
         * @param onsuccess
         */
        var ajax_req_move_general = function (params, target, onsuccess) {
            if (ajaxing) {
                // Request already made.
                log('Skipping ajax request, one already in progress');
                return;
            }

            // Add spinner.
            $('#snap-move-notice .snap-move-notice-title').append('<span class="spinner-three-quarters"></span>');

            // Set common params.
            params.sesskey = M.cfg.sesskey;
            params.courseId = M.theme_snap.courseid;
            params.field = 'move';

            log('Making course/rest.php request', params);
            var req = $.ajax({
                type: "POST",
                async: true,
                data: params,
                url: M.cfg.wwwroot + M.theme_snap.courseconfig.ajaxurl
            });
            req.done(function (data) {
                if (data.error) {
                    log('Ajax request fail');
                    move_failed();
                } else {
                    log('Ajax request successful');
                    if (onsuccess) {
                        onsuccess();
                    }
                    stop_moving();
                }
            });
            req.fail(function () {
                move_failed();
            });
            req.always(function () {
                ajaxing = false;
            });
        };

        /**
         * Ajax request to move asset to target.
         * @param target
         */
        var ajax_req_move_asset = function (target) {
            var params = {};

            // Prepare request parameters
            params['class'] = 'resource';
            params.id = Number(movingobject.id.replace('module-', ''));

            if (target && !$(target).hasClass('snap-drop')) {
                params.beforeId = Number($(target)[0].id.replace('module-', ''));
            } else {
                params.beforeId = 0;
            }

            if (target) {
                params.sectionId = Number($(target).parents('li.section.main')[0].id.replace('section-', ''));
            } else {
                params.sectionId = Number($(movingobject).parents('li.section.main')[0].id.replace('section-', ''));
            }
            log('asset move - moving', movingobject);
            log('asset move - target', target);
            $(target).before($(movingobject));

            ajax_req_move_general(params, target, function () {
                // TODO - action move here.
            });
        }

        /**
         * Move fail - sad face :(.
         */
        var move_failed = function () {
            $('.snap-move-notice').addClass('movefail');
            $('.snap-move-notice .spinner-three-quarters').remove();
            var actname = $(moving).find('.instancename').html();

            $('.snap-move-notice-title').html(M.util.get_string('movefailed', 'theme_snap', actname));
            // Stop moving in 2 seconds so that the user has time to see the failed moving notice.
            window.setTimeout(function () {
                // Don't pass in target, we want to abort the move!
                stop_moving(false);
            }, 2000);
        };

        /**
         * When section move link is clicked, get the data we need and start the move.
         */
        var move_section_listener = function() {
            // Listen clicks on move links.
            $("#region-main").on('click', '.snap-section-editing.actions .snap-move', function (e) {
                e.stopPropagation();
                e.preventDefault();

                $('body').addClass('snap-move-inprogress');

                // Moving a section.
                var sectionid = $(this).data("id");
                log('Section is', sectionid);
                var section = $('#section-' + sectionid);
                var sectionname = section.find('.sectionname').text();

                log('Moving this section', sectionname);
                movingobject = section;

                // This should never happen, but just in case...
                $('.section-moving').removeClass('section-moving');
                section.addClass('section-moving');
                $('a[href$=#section-' + sectionid + ']').parent('li').addClass('section-moving');
                $('body').addClass('snap-move-section');

                var title = M.util.get_string('moving', 'theme_snap', sectionname);
                snap_move_message.find('.snap-move-message-title').html(title);
                snap_move_message.focus();

                $('.section-drop').each(function(){
                    var sectiondropmsg = M.util.get_string('movingdropsectionhelp', 'theme_snap',
                        {moving:sectionname, before:$(this).data('title')}
                    );
                    $(this).html(sectiondropmsg);
                });

                $('#snap-move-message p.sr-only').html(
                    M.util.get_string('movingstartedhelp', 'theme_snap', sectionname)
                );
            });
        };

        /**
         * When assett move link is clicked, initiate the move.
         */
        var move_asset_listener = function() {
            // TODO - implement asset move listener (current selector is wrong, requires merge of
            // INT-8449_Asset_editing_tools
            $("#region-main").on('click', '.snap-asset-actions .js-snap-move', function (e) {
                // Moving asset - activity or resource.
                var asset = $(this).parents('.snap-asset')[0];
                var assetname = $(asset).find('.snap-asset-link .instancename').html();
                log('Moving this asset', assetname);

                // TODO - snap-mine does not exists for activities
                var classes = $(asset).attr('class');
                var regex = /(?=snap-mime)([a-z0-9\-]*)/;
                var assetclasses = regex.exec(classes);
                var classes = '';
                if (assetclasses) {
                    classes = assetclasses.join(' ');
                }
                log('Moving this class', classes);
                movingobject = asset;


                $('body').addClass('snap-move-asset');
                $(asset).addClass('asset-moving');
                var title = M.util.get_string('moving', 'theme_snap', assetname);
                snap_move_message.find('.snap-move-message-title').html(title);
                snap_move_message.focus();
            });
        };

        /**
         * When an asset or drop zone is clicked, execute move.
         */
        var move_place_listener = function() {
            $(document).on('click', '.snap-move-note, .snap-drop', function (e) {
                log('Snap drop clicked', e);
                if (movingobject) {
                    e.stopPropagation();
                    e.preventDefault();
                    if ($('body').hasClass('snap-move-section')) {
                        ajax_req_move_section(this);
                    } else {
                        var target = $(this).closest('.snap-asset');
                        ajax_req_move_asset(target);
                    }
                }
            });
        };

        /**
         * Ajax request to move section to target.
         * @param target
         */
        var ajax_req_move_section = function (dropzone) {
            var targetsection = $(dropzone).data('id');
            var target = $('#section-' + targetsection);
            var currentsection = $(movingobject).attr('id').replace('section-', '');

            if (currentsection < targetsection) {
                targetsection -=1;
            }

            var params = {
                class: 'section',
                id: currentsection,
                value: targetsection
            };

            ajax_req_move_general(params, target, function () {
                var targetsection = params.value;

                // TODO - INT-8670 - ok, here we can do a page redirect / reload but should probably ajax if we have time!
                location.href = location.href.replace(location.hash, '')+'#section-'+targetsection;
                location.reload(true);
            });
        };

        /**
         * Moving has stopped, clean up.
         */
        var stop_moving = function () {
            $('body').removeClass('snap-move-inprogress');
            $('body').removeClass('snap-move-section');
            $('body').removeClass('snap-move-asset');
            $('.section-moving').removeClass('section-moving');
            $('.asset-moving').removeClass('asset-moving');
            movingobject = '';
        }

        /**
         * When cancel button is pressed in footer, cancel move.
         */
        var move_cancel_listener = function() {
            $(".snap-move-cancel").click(
                function (e) {
                    e.preventDefault();
                    stop_moving();
                }
            );
        };

        /**
         * Initialise script.
         */
        var initialise = function() {
            // Add listeners.
            move_section_listener();
            move_asset_listener();
            move_cancel_listener();
            move_place_listener();
        };
        initialise();
    }
}
