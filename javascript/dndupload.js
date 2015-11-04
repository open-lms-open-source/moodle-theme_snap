M.theme_snap.dndupload = M.course_dndupload;

var main_init = M.course_dndupload.init;

M.theme_snap.dndupload.init = function(Y, options) {

    var self = this;
    this.init = main_init;

    this.init(Y, options);

    $('#snap-drop-file').change(function() {
        var currentSectionId = $('.main.state-visible, #coursetools.state-visible').attr('id');
        if (currentSectionId === 'coursetools') {
            return;
        }
        var section = Y.one('#'+currentSectionId);
        var sectionnumber = parseInt(currentSectionId.replace('section-'));
        var file;
        for (var i = 0; i < this.files.length; i++) {
            // Get file and trigger upload.
            file = this.files.item(i);
            self.handle_file(file, section, sectionnumber);
        }
    });
}