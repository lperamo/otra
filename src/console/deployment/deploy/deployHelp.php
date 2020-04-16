<?php

use src\console\TasksManager;

return [
  'Deploy the site. ' . CLI_YELLOW . '[Currently only works for unix systems !]' . END_COLOR,
  [
    'mask' => '0 => Nothing to do (default)' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '1 => Generates PHP production files.' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '2 => JS production files.' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '4 => CSS production files' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '7 => all production files'
    ,
    'verbose' => 'If set to 1 => we print all the warnings during the production php files generation'
  ],
  ['optional', 'optional'],
  'Deployment'
];
