# LESS Lint Grunt plugin
[![Build Status](https://travis-ci.org/jgable/grunt-lesslint.svg)](https://travis-ci.org/jgable/grunt-lesslint)
[![Dependency Status](https://david-dm.org/jgable/grunt-lesslint.svg)](https://david-dm.org/jgable/grunt-lesslint)
[![devDependency Status](https://david-dm.org/jgable/grunt-lesslint/dev-status.svg)](https://david-dm.org/jgable/grunt-lesslint#info=devDependencies)

Lint your [LESS](http://lesscss.org/) files using
[CSS Lint](http://csslint.net/) from [Grunt](http://gruntjs.com/).

This plugin compiles your LESS files, runs the generated CSS through CSS Lint,
and outputs the offending LESS line for any CSS Lint errors found.

## Installing

```sh
npm install grunt-lesslint
```

## Building
  * Clone the repository
  * Run `npm install`
  * Run `grunt` to compile the CoffeeScript code
  * Run `grunt test` to run the specs

## Configuring

Add the following to your `Gruntfile.coffee`:

```coffeescript
grunt.initConfig
  lesslint:
    src: ['src/**/*.less']

grunt.loadNpmTasks('grunt-lesslint')
```

Then run `grunt lesslint` to lint all the `.less` files under `src/`.

By default the plugin uses the `less` and `csslint` config settings to
configure the LESS parser and the CSS Lint validator.

### CSS Lint

You can configure the CSS Lint validator, such as for disabling certain rules
or loading a `.csslintrc` file, by adding a `csslint` option value:

```coffeescript
lesslint:
  src: ['less/*.less']
  options:
    csslint:
      'known-properties': false
      csslintrc: '.csslintrc'
```

### LESS

You can configure the LESS parser, such as for adding include paths,
by adding a `less` option value:

```coffeescript
lesslint:
  src: ['less/*.less']
  options:
    less:
      paths: ['includes']
```

### Linting imports

By default, this plugin does not include any lint errors from imported files
in the output.

You can enable this by adding an `imports` configuration option:

```coffeescript
lesslint:
  src: ['src/**/*.less']
  options:
    imports: ['imports/**/*.less']
```

### Generating reports

This plugin provides the same output formatter options as the CSS Lint plugin
and can be configured similarly:

```coffeescript
lesslint:
  options:
    formatters: [
      id: 'csslint-xml'
      dest: 'report/lesslint.xml'
    ]
```

### Using custom rules

It is possible to create and use your own custom rules. To create rules, please refer to the [official CSSLint guidelines](https://github.com/CSSLint/csslint/wiki/Working-with-Rules). The only addition is that each custom rule file must import `CSSLint` using `CSSLint = require('grunt-lesslint').CSSLint`.

You can enable your custom rules by adding a `customRules` configuration option:

```coffeescript
lesslint:
  options:
    customRules: ['lint-rules/less/**/*.coffee']
```

## Example output

```
> grunt lesslint
Running "lesslint" (lesslint) task
static/editor.less (1)
Values of 0 shouldn't have units specified. You don't need to specify units when a value is 0. (zero-units)
>> 14: line-height: 0px;

>> 1 linting error in 56 files.
```
