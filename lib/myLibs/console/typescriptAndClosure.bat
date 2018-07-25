@echo off
REM %1 FileDir; %2 FileName; %3 FileNameWithoutExtension; %4 %FileParentDir, %5 ProjectFileDir %6 Optimization type
REM if %6 is 'NO' then we don't use google closure compiler service

REM exit /B 0 sends a success return code
if "%6" == "NO" (
  C:\Users\lionel\AppData\Roaming\npm\tsc.cmd %1/%3.ts --typeRoots %5/node_modules/@types --sourcemap %1/%2 --out %4/js/%3.js || echo 'Errors to fix but these are not blocking.' && exit /B 0;  
) else (
  (C:\Users\lionel\AppData\Roaming\npm\tsc.cmd %1/%3.ts --typeRoots %5/node_modules/@types --sourcemap %1/%2 --out %1/%3.js || echo 'Errors to fix but these are not blocking.') && java -jar %5/lib/myLibs/console/compiler.jar --compilation_level %6 --rewrite_polyfills=false --js %1/%3.js --js_output_file %4/js/%3.js
)
