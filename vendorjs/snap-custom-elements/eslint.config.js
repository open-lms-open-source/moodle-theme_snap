// @ts-check
const eslint = require("@eslint/js");
const tseslint = require("typescript-eslint");
const angular = require("angular-eslint");

module.exports = tseslint.config(
  {
    ignores: ["dist/**", "coverage/**", ".angular/**"],
  },
  {
    files: ["**/*.ts"],
    extends: [
      eslint.configs.recommended,
      ...tseslint.configs.recommended,
      ...angular.configs.tsRecommended,
    ],
    processor: angular.processInlineTemplates,
    rules: {
      "@angular-eslint/directive-selector": [
        "error",
        {type: "attribute", prefix: "snap", style: "camelCase"},
      ],
      // The custom element tag names are fixed by the markup Snap renders
      // (snap-feed, feed-error-modal), so the selector prefix rule cannot apply.
      "@angular-eslint/component-selector": "off",
      // These components are deliberately declared by AppModule rather than
      // being standalone, so that ngDoBootstrap can register them as custom
      // elements. Converting them is tracked separately.
      "@angular-eslint/prefer-standalone": "off",
      // Migrating constructor injection to inject() is a refactor of its own.
      "@angular-eslint/prefer-inject": "off",
      // The Moodle web service responses are untyped by nature.
      "@typescript-eslint/no-explicit-any": "off",
      "@typescript-eslint/no-unused-vars": [
        "error",
        {args: "none", caughtErrors: "none"},
      ],
    },
  },
  {
    files: ["**/*.spec.ts"],
    rules: {
      // The fixtures hold Moodle web service payloads captured verbatim, where
      // the forward slashes arrive escaped. Keeping them as captured matters
      // more than the redundant backslashes.
      "no-useless-escape": "off",
    },
  },
  {
    files: ["**/*.html"],
    extends: [
      ...angular.configs.templateRecommended,
      ...angular.configs.templateAccessibility,
    ],
    rules: {
      // Migrating *ngIf/*ngFor to the built-in @if/@for blocks is a refactor of
      // its own and would need the feed UI re-tested end to end.
      "@angular-eslint/template/prefer-control-flow": "off",
      // Pre-existing accessibility gaps in the feed templates: reported so they
      // stay visible, but not failing the build.
      "@angular-eslint/template/click-events-have-key-events": "warn",
      "@angular-eslint/template/interactive-supports-focus": "warn",
      "@angular-eslint/template/label-has-associated-control": "warn",
    },
  }
);
