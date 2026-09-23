#!/bin/bash
#
# Packages the Angular build output into snap-ce.js.
#
# The Angular application builder emits ES modules (main.js plus any shared
# chunks it decides to split off), but Snap loads snap-ce.js through RequireJS
# as a plain <script> tag, which is not a module context. So the emitted modules
# are re-bundled into a single classic IIFE script.
#
# The input is already optimised by `ng build --configuration production`;
# esbuild pretty-prints its output unless told otherwise, so --minify is needed
# here just to keep the bundle at the size Angular already produced.

set -euo pipefail

DIST=./dist/snap-custom-elements
ENTRY=$DIST/snap-ce.entry.mjs
OUT=snap-ce.js

for required in "$DIST/polyfills.js" "$DIST/main.js"; do
  if [ ! -f "$required" ]; then
    echo "Missing $required. Run 'npm run build' first." >&2
    exit 1
  fi
done

# Polyfills must be evaluated before the application bootstraps.
cat > "$ENTRY" <<'ENTRYJS'
import './polyfills.js';
import './main.js';
ENTRYJS

npx --no-install esbuild "$ENTRY" \
  --bundle \
  --format=iife \
  --minify \
  --legal-comments=none \
  --log-level=warning \
  --outfile="$OUT"

rm -f "$ENTRY"

echo "Packaged project into $OUT"
