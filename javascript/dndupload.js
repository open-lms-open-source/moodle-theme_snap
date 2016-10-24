M.theme_snap.dndupload = M.course_dndupload;

var main_init = M.course_dndupload.init;

M.theme_snap.dndupload.init = function(Y, options) {

    var self = this;
    this.init = main_init;

    // Rebuild file handlers without annoying img label handler.
    var imgExts = ['gif', 'jpe', 'jpg', 'jpeg', 'png', 'svg', 'svgz', 'webp'];
    var newfilehandlers = [];
    for (var h in options.handlers.filehandlers) {
        var handler = options.handlers.filehandlers[h];
        if (handler && handler.module) {
            // Prevent label img dialog from showing.
            if (handler.module !== 'label' || imgExts.indexOf(handler.extension.toLowerCase()) === -1) {
                newfilehandlers.push(handler);
            }
        }
    }
    options.handlers.filehandlers = newfilehandlers;

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
};
