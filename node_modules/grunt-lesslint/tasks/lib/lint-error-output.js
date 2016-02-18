(function() {
  var LintErrorOutput, SourceMapConsumer, chalk, path, stripPath, _;

  path = require('path');

  SourceMapConsumer = require('source-map').SourceMapConsumer;

  _ = require('lodash');

  chalk = require('chalk');

  stripPath = require('strip-path');

  LintErrorOutput = (function() {
    function LintErrorOutput(result, grunt) {
      this.result = result;
      this.grunt = grunt;
    }

    LintErrorOutput.prototype.display = function(importsToLint) {
      var column, errorCount, file, fileContents, fileLines, filePath, fullRuleMessage, isThisFile, less, lessSource, line, message, messageGroups, messages, rule, ruleMessages, source, sourceMap, _i, _len, _ref;
      sourceMap = new SourceMapConsumer(this.result.sourceMap);
      errorCount = 0;
      messages = this.result.lint.messages;
      less = this.result.less;
      file = path.resolve(this.result.file);
      filePath = stripPath(file, process.cwd());
      fileContents = {};
      fileLines = {};
      messages = messages.filter((function(_this) {
        return function(message) {
          var isThisFile, source, sourceArray;
          if (message.line === 0 || message.rollup) {
            return true;
          }
          source = sourceMap.originalPositionFor({
            line: message.line,
            column: message.col
          }).source;
          if (source === null) {
            return false;
          }
          if (source) {
            source = path.resolve(source);
          }
          isThisFile = source === file;
          sourceArray = [stripPath(source, process.cwd()), stripPath(source, process.cwd() + '\\')];
          return isThisFile || _this.grunt.file.isMatch(importsToLint, sourceArray);
        };
      })(this));
      if (messages.length < 1) {
        return 0;
      }
      this.result.lint.messages = messages;
      messageGroups = _.groupBy(messages, function(_arg) {
        var fullMsg, message, rule;
        message = _arg.message, rule = _arg.rule;
        fullMsg = "" + message;
        if (rule.desc && rule.desc !== message) {
          fullMsg += " " + rule.desc;
        }
        return fullMsg;
      });
      this.grunt.log.writeln("" + (chalk.yellow(filePath)) + " (" + messages.length + ")");
      for (fullRuleMessage in messageGroups) {
        ruleMessages = messageGroups[fullRuleMessage];
        rule = ruleMessages[0].rule;
        this.grunt.log.writeln(fullRuleMessage + chalk.grey(" (" + rule.id + ")"));
        for (_i = 0, _len = ruleMessages.length; _i < _len; _i++) {
          message = ruleMessages[_i];
          errorCount += 1;
          if (message.line === 0 || message.rollup) {
            continue;
          }
          _ref = sourceMap.originalPositionFor({
            line: message.line,
            column: message.col
          }), line = _ref.line, column = _ref.column, source = _ref.source;
          isThisFile = source === file;
          message.lessLine = {
            line: line,
            column: column
          };
          if (!fileContents[source]) {
            if (isThisFile) {
              fileContents[source] = less;
            } else {
              fileContents[source] = this.grunt.file.read(source);
            }
            fileLines[source] = fileContents[source].split('\n');
          }
          filePath = stripPath(source, process.cwd());
          lessSource = fileLines[source][line - 1].slice(column);
          this.grunt.log.error(chalk.gray("" + filePath + " [Line " + line + ", Column " + (column + 1) + "]:\t") + (" " + (lessSource.trim())));
        }
      }
      return errorCount;
    };

    return LintErrorOutput;

  })();

  module.exports = LintErrorOutput;

}).call(this);
