# Snap Custom Elements

This project holds Snap custom elements which are used by the Snap theme.

It is an Angular 21 application. The components are registered as custom
elements (`snap-feed`, `feed-error-modal`) in `AppModule.ngDoBootstrap`, and
Snap loads the packaged bundle through RequireJS — see
`theme_snap\hook_callbacks::before_footer_html_generation`.

## Requirements

Angular 21 requires Node.js `^20.19.0`, `^22.12.0` or `>=24.0.0`.

```bash
npm ci
```

## Adding a new element

```bash
ng g component <component name> --inline-style --inline-template
```

The generated element will be found in:
`theme/snap/vendorjs/snap-custom-elements/src/app/<component name>`

Components declared by `AppModule` must be created with `standalone: false`.

## Building the library for use with the Snap theme

```bash
npm run test-and-build
```

This will generate:

* `theme/snap/vendorjs/snap-custom-elements/snap-ce.js`.

`npm run build` produces ES modules under `dist/`, and `npm run package`
(`build-ce.sh`) re-bundles them into `snap-ce.js` as a single classic script,
because RequireJS loads it with a plain `<script>` tag rather than as a module.

`snap-ce.js` is committed, so rebuild and commit it whenever the sources or the
dependencies change.

## Linting and unit tests

```bash
npm run lint
npm run test           # watches, needs a local Chrome
npm run test-headless
```
