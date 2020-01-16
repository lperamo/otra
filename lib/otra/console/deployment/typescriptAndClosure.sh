#!/usr/bin/env bash
# %APPDATA% generally looks like C:\Users\MyUser\AppData\Roaming
# %1 FileDir; %2 FileName; %3 FileNameWithoutExtension; %4 %FileParentDir, %5 ProjectFileDir %6 Optimization type
# if %6 is 'NO' then we don't use google closure compiler service
if [[ "$6" == "NO" ]]; then
  tsc $1/$3.ts --pretty --typeRoots $5/node_modules/@types --sourcemap $1/$2 --out $4/js/$3.js || echo 'Errors to fix but these are not blocking.' && exit 1;
else
  (tsc $1/$3.ts --pretty --typeRoots $5/node_modules/@types --sourcemap $1/$2 --out $4/js/$3.js --project $5/tsconfig.json || echo 'Errors to fix but these are not blocking.') && java -jar $5/lib/otra/console/compiler.jar -W VERBOSE -O $6 --rewrite_polyfills=false --js $4/js/$3.js --js_output_file $4/js/$3.js;
fi
