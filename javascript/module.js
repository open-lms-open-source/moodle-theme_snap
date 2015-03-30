M.theme_snap = M.theme_snap || {
    courseid : false
};
M.theme_snap.core = {
    init: function(Y, courseid, contextid) {
        // Add courseid to moodle cfg variable (this is here for future proofing in case we need it)
        M.theme_snap.courseid = courseid;
        M.cfg.context = contextid;
        $(document).ready(snapInit);
    }
};
