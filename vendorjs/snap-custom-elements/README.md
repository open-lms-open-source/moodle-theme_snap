# Snap Custom Elements

This project holds Snap custom elements which are used by the Snap theme.


## Adding a new element

```bash
ng g component <component name> --inline-style --inline-template
```

The generated element will be found in:
`theme/snap/vendorjs/snap-custom-elements/src/app/<component name>`

## Building the library for use with the Snap theme

```bash
npm run build && npm run package && npm run package-es5
```

This will generate `theme/snap/vendorjs/snap-custom-elements/snap-ce.js`.
