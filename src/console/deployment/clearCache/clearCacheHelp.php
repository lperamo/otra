<?php

use otra\console\TasksManager;

return [
  'Clears whatever cache you want to clear.',
  [
    'mask' => '  1 => PHP OTRA internal cache' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '  2 => PHP bootstraps' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '  4 => CSS' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '  8 => JS' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . ' 16 => Templates' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . ' 32 => Route management' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . ' 64 => Class mapping (development & production)' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . ' 128 => Console tasks metadata' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . ' 256 => Debugging files' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . ' 511 => All files from the cache (default)'
    ,
    'route name' => 'If you want to clear cache for only one route. (useful only for bits 2, 4, 8 of the ' .
      CLI_LIGHT_CYAN . 'mask' . CLI_CYAN . ' parameter)'
  ],
  ['optional', 'optional'],
  'Deployment'
];
