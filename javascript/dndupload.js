M.theme_snap.dndupload = M.course_dndupload;

var main_init = M.course_dndupload.init;

M.theme_snap.dndupload.init = function(Y, options) {

    var self = this;
    this.init = main_init;

    this.init(Y, options);

    $('.js-snap-drop-file').change(function() {
        var sectionnumber = $(this).attr('id').replace('snap-drop-file-', '');
        var section = Y.one('#section-'+sectionnumber);

        var file;
        for (var i = 0; i < this.files.length; i++) {
            // Get file and trigger upload.
            file = this.files.item(i);
            self.handle_file(file, section, sectionnumber);
        }
    });
}