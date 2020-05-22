<?php
declare(strict_types=1);

return [
  'Compiles the typescripts, sass and php configuration files (modulo the binary mask).',
  [
    'verbose' => '0 => Quite silent, 1 => Tells which file has been updated.',
    'mask' => '1 => SCSS, 2 => TS, ..., 4 => routes, ..., 8 => PHP, 15 => ALL. Default to 15.',
    'gcc' => 'Should we use Google Closure Compiler for javascript/typescript files ? Defaults to false.',
    'scope' => '0 => project files (default), 1 => OTRA files, 2 => All the files'
  ],
  ['optional', 'optional', 'optional', 'optional', 'optional'],
  'Deployment'
];
