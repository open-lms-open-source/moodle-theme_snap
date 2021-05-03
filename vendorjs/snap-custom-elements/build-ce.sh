#!/bin/bash

# Package es2015 package.
cat ./dist/snap-custom-elements/runtime-es2015.js \
./dist/snap-custom-elements/polyfills-es2015.js \
./dist/snap-custom-elements/scripts.js \
./dist/snap-custom-elements/main-es2015.js > snap-ce.js

echo "Packaged es2015 project into snap-ce.js"

# Package es5 package.
cat ./dist/snap-custom-elements/runtime-es5.js \
./dist/snap-custom-elements/polyfills-es5.js \
./dist/snap-custom-elements/scripts.js \
./dist/snap-custom-elements/main-es5.js > snap-ce-es5.js

echo "Packaged es5 project into snap-ce-es5.js"
