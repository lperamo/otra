<?php

use src\console\TasksManager;

return [
  'Launches a watcher that will update the PHP class mapping, the ts files and the scss files.',
  [
    'verbose' => '0 => Only tells that the watcher is started.' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) .
      '1 => Tells which file has been updated (default).' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) .
      '2 => Tells which file has been updated and the most important events that have been triggered.' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . 'Default to 1.',
    'mask' => '1 => SCSS, 2 => TS, ..., 4 => routes, ..., 8 => PHP, 15 => ALL. Default to 15.',
    'gcc' => 'Should we use Google Closure Compiler for javascript/typescript files ?'
  ],
  ['optional', 'optional', 'optional'],
  'Deployment'
];
