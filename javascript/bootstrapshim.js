requirejs.config({
    shim: {
        snapBootstrap: {
            deps: [ "jquery" ],
            exports : 'snapBootstrap'
        }
    },
    paths: {
        snapBootstrap: "../javascript/bootstrap.js"
    }
});
