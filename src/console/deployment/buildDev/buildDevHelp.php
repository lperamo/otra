<?php

return [
  'Compiles the typescripts, sass and php configuration files (modulo the binary mask).',
  [
    'verbose' => '0 => Quite silent, 1 => Tells which file has been updated.',
    'mask' => '1 => SCSS, 2 => TS, ..., 4 => routes, ..., 8 => PHP, 15 => ALL. Default to 15.',
    'source maps' => '0 => No source maps (default)' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '1 => Source maps for SASS/SCSS/CSS' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . 'Source maps for TS are handled by tsconfig.json.',
    'gcc' => 'Should we use Google Closure Compiler for javascript/typescript files ? Defaults to false.',
    'scope' => '0 => project files (default), 1 => OTRA files, 2 => All the files'
  ],
  ['optional', 'optional', 'optional', 'optional', 'optional'],
  'Deployment'
];
