(function() {
  var CSSLint, LessCachedFile, LessFile, LintCache, LintErrorOutput, Parser, async, chalk, crypto, defaultLessOptions, findLessMapping, findPropertyLineNumber, getPropertyName, path, stripPath, _, _ref, _ref1;

  CSSLint = require('csslint').CSSLint;

  Parser = require('less').Parser;

  _ref = require('./lib/lint-utils'), findLessMapping = _ref.findLessMapping, findPropertyLineNumber = _ref.findPropertyLineNumber, getPropertyName = _ref.getPropertyName;

  LintCache = require('./lib/lint-cache').LintCache;

  _ref1 = require('./lib/less-file'), LessFile = _ref1.LessFile, LessCachedFile = _ref1.LessCachedFile;

  LintErrorOutput = require('./lib/lint-error-output');

  async = require('async');

  path = require('path');

  crypto = require('crypto');

  stripPath = require('strip-path');

  _ = require('lodash');

  chalk = require('chalk');

  defaultLessOptions = {
    cleancss: false,
    compress: false,
    dumpLineNumbers: 'comments',
    optimization: null,
    syncImport: true
  };

  module.exports = function(grunt) {
    var writeToFormatters;
    writeToFormatters = function(options, results) {
      var formatters;
      formatters = options.formatters;
      if (!_.isArray(formatters)) {
        return;
      }
      return formatters.forEach(function(_arg) {
        var dest, filePath, formatter, formatterOutput, id, message, result, _i, _len, _ref2;
        id = _arg.id, dest = _arg.dest;
        if (!(id && dest)) {
          return;
        }
        formatter = CSSLint.getFormatter(id);
        if (formatter == null) {
          return;
        }
        formatterOutput = formatter.startFormat();
        for (filePath in results) {
          result = results[filePath];
          _ref2 = result.messages;
          for (_i = 0, _len = _ref2.length; _i < _len; _i++) {
            message = _ref2[_i];
            if (message.lessLine) {
              message.line = message.lessLine.line - 1;
              message.col = message.lessLine.column - 1;
            }
          }
          formatterOutput += formatter.formatResults(result, filePath, {});
        }
        formatterOutput += formatter.endFormat();
        return grunt.file.write(dest, formatterOutput);
      });
    };
    grunt.registerMultiTask('lesslint', 'Validate LESS files with CSS Lint', function() {
      var done, errorCount, fileCount, options, queue, results;
      options = this.options({
        less: grunt.config.get('less.options'),
        csslint: grunt.config.get('csslint.options'),
        imports: void 0,
        customRules: void 0,
        cache: false,
        failOnError: true
      });
      fileCount = 0;
      errorCount = 0;
      results = {};
      queue = async.queue(function(file, callback) {
        var lessFile;
        grunt.verbose.write("Linting '" + file + "'");
        fileCount++;
        if (!options.cache) {
          lessFile = new LessFile(file, options, grunt);
        } else {
          lessFile = new LessCachedFile(file, options, grunt);
        }
        return lessFile.lint(function(err, result) {
          var errorOutput, fileLintErrors, lintResult;
          if (err != null) {
            errorCount++;
            grunt.log.writeln(err.message);
            return callback();
          }
          result || (result = {});
          lintResult = result.lint;
          if (lintResult) {
            results[file] = lintResult;
            errorOutput = new LintErrorOutput(result, grunt);
            fileLintErrors = errorOutput.display(options.imports);
            errorCount += fileLintErrors;
          }
          return callback();
        });
      });
      this.filesSrc.forEach(function(file) {
        return queue.push(file);
      });
      done = this.async();
      queue.drain = function() {
        writeToFormatters(options, results);
        if (errorCount === 0) {
          grunt.log.ok("" + fileCount + " " + (grunt.util.pluralize(fileCount, 'file/files')) + " lint free.");
          return done();
        } else {
          grunt.log.writeln();
          grunt.log.error("" + errorCount + " lint " + (grunt.util.pluralize(errorCount, 'error/errors')) + " in " + fileCount + " " + (grunt.util.pluralize(fileCount, 'file/files')) + ".");
          return done(!options.failOnError);
        }
      };
      if ((this.filesSrc == null) || this.filesSrc.length === 0) {
        return done();
      }
    });
    return grunt.registerTask('lesslint:clearCache', function() {
      var cache, done;
      done = this.async();
      cache = new LintCache();
      return cache.clear(function(err) {
        if (err) {
          grunt.log.error(err.message);
        }
        return done();
      });
    });
  };

  module.exports.CSSLint = CSSLint;

}).call(this);
