'use strict';

var fs = require('fs'),
  path = require('path'),
  os = require('os');

var assign = require('object-assign'),
  mkdirp = require('mkdirp'),
  rimraf = require('rimraf');

var CacheSwap = function (opts) {
  this.options = assign({
    tmpDir: os.tmpDir(),
    cacheDirName: 'defaultCacheSwap'
  }, opts);
};

assign(CacheSwap.prototype, {
  clear: function (category, done) {
    var dir = path.join(this.options.tmpDir, this.options.cacheDirName);

    if (category) {
      dir = path.join(dir, category);
    }

    // rm -rf for node
    rimraf(dir, done);
  },

  hasCached: function (category, hash, done) {
    var filePath = this.getCachedFilePath(category, hash);

    fs.exists(filePath, function (exists) {
      return done(exists, exists ? filePath : null);
    });
  },

  getCached: function (category, hash, done) {
    var filePath = this.getCachedFilePath(category, hash);

    fs.readFile(filePath, function (err, fileStream) {
      if (err) {
        if (err.code === 'ENOENT') {
          return done();
        }

        return done(err);
      }

      done(null, {
        contents: fileStream.toString(),
        path: filePath
      });
    });
  },

  addCached: function (category, hash, contents, done) {
    var filePath = this.getCachedFilePath(category, hash);

    this._prepPath(filePath, function (err) {
      if (err) {
        return done(err);
      }

      fs.writeFile(filePath, contents, {mode: parseInt('0777', 8)}, function (err) {
        if (err) {
          return done(err);
        }

        fs.chmod(filePath, parseInt('0777', 8), function () {
          done(null, filePath);
        });
      });
    });
  },

  removeCached: function (category, hash, done) {
    var filePath = this.getCachedFilePath(category, hash);

    fs.unlink(filePath, function (err) {
      if (err) {
        if (err.code === 'ENOENT') {
          return done();
        }
        return done(err);
      }

      done();
    });
  },

  getCachedFilePath: function (category, hash) {
    return path.join(this.options.tmpDir, this.options.cacheDirName, category, hash);
  },

  _prepCategory: function (category, done) {
    var filePath = this.getCachedFilePath(category, 'prep');

    this._prepPath(filePath, done);
  },

  _prepPath: function (filePath, done) {
    mkdirp(path.dirname(filePath), {mode: parseInt('0777', 8)}, done);
  }
});

module.exports = CacheSwap;
