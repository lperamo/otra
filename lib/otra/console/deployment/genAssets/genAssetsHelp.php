<?php

//namespace lib\otra\console\deployment;

use lib\otra\console\TasksManager;

return [
  'Generates one css file and one js file that contain respectively all the minified css files and all the obfuscated minified js files.',
  [
    'mask' => '1 => templates' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '2 => css' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '4 => js' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '7 => all (default)',
    'js_level_compilation' => 'Optimization level for Google Closure Compiler' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '0 for WHITESPACE_ONLY' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '1 for SIMPLE_OPTIMIZATIONS (default)' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '2 for ADVANCED_OPTIMIZATIONS',
    'route' => 'The route for which you want to generate resources.'
  ],
  ['optional', 'optional', 'optional'],
  'Deployment'
];
