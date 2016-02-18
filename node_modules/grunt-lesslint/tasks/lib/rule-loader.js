(function() {
  var CSSLint, RuleLoader, RuleLoaderFactory, path, _,
    __indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

  CSSLint = require('csslint').CSSLint;

  path = require('path');

  _ = require('lodash');

  module.exports = RuleLoaderFactory = (function() {
    var instance;

    function RuleLoaderFactory() {}

    instance = null;

    RuleLoaderFactory.getRuleLoader = function(grunt) {
      if (!instance) {
        instance = new RuleLoader(grunt, require);
      }
      return instance;
    };

    return RuleLoaderFactory;

  })();

  module.exports.RuleLoader = RuleLoader = (function() {
    function RuleLoader(grunt, require) {
      this.grunt = grunt;
      this.require = require;
      this.rulesPerFile = {};
    }

    RuleLoader.prototype.configureRules = function(options) {
      var enabledRules;
      enabledRules = this.enableConfiguredRuleFiles(options);
      return this.getDisabledRules(enabledRules, options);
    };

    RuleLoader.prototype.enableConfiguredRuleFiles = function(options) {
      var customRules, enabledRules, id, ruleFile, ruleFiles;
      enabledRules = [];
      customRules = options.customRules;
      if (customRules != null) {
        ruleFiles = this.grunt.file.expand(customRules);
        for (id in ruleFiles) {
          ruleFile = ruleFiles[id];
          enabledRules = _.union(enabledRules, this.enableRuleFile(ruleFile));
        }
      }
      return enabledRules;
    };

    RuleLoader.prototype.enableRuleFile = function(ruleFile) {
      var newRules, rulesBefore;
      if (!(ruleFile in this.rulesPerFile)) {
        this.grunt.verbose.writeln('Loading custom rules from ' + ruleFile.cyan);
        rulesBefore = this.getCurrentRuleNames();
        this.require(path.resolve(ruleFile));
        newRules = this.getNewRuleNames(rulesBefore);
        this.rulesPerFile[ruleFile] = newRules;
      } else {
        newRules = this.rulesPerFile[ruleFile];
      }
      return newRules;
    };

    RuleLoader.prototype.getCurrentRuleNames = function() {
      return _.keys(CSSLint.getRuleset());
    };

    RuleLoader.prototype.getNewRuleNames = function(previousRuleNames) {
      return _.difference(this.getCurrentRuleNames(), previousRuleNames);
    };

    RuleLoader.prototype.getDisabledRules = function(enabledRules, options) {
      var configuredRules;
      configuredRules = _.keys(options.csslint);
      return _(this.rulesPerFile).values().flatten().filter(function(rule) {
        return __indexOf.call(enabledRules, rule) < 0 && __indexOf.call(configuredRules, rule) < 0;
      }).value();
    };

    return RuleLoader;

  })();

}).call(this);
