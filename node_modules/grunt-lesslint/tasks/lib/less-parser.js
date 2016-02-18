(function() {
  var LessParser, defaultLessOptions, less, path, _;

  path = require('path');

  _ = require('lodash');

  less = require('less');

  defaultLessOptions = {
    cleancss: false,
    compress: false,
    dumpLineNumbers: 'comments',
    optimization: null,
    syncImport: true
  };

  module.exports = LessParser = (function() {
    function LessParser(fileName, opts) {
      var paths;
      opts = _.defaults(opts || {}, defaultLessOptions);
      paths = [path.dirname(path.resolve(fileName))];
      if (opts.less && opts.less.paths) {
        paths = paths.concat(opts.less.paths);
      }
      this.opts = _.extend({
        filename: path.resolve(fileName),
        paths: paths,
        sourceMap: {}
      }, opts);
    }

    LessParser.prototype.render = function(contents, callback) {
      var err;
      try {
        return less.render(contents, this.opts, function(err, output) {
          return callback(err, output != null ? output.css : void 0, output != null ? output.map : void 0);
        });
      } catch (_error) {
        err = _error;
        return callback(err);
      }
    };

    return LessParser;

  })();

}).call(this);
