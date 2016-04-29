requirejs.config({
    shim: {
        snap_bootstrap: {
            deps: [ "jquery" ],
            exports : 'snap_bootstrap'
        }
    },
    paths: {
        snap_bootstrap: "../javascript/bootstrap.js"
    }
});
