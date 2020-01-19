<?php

use lib\otra\console\TasksManager;

return [
  'Deploy the site. ' . CLI_YELLOW . '[WIP - Do not use yet !]' . END_COLOR,
  [
    'mode' => '0 => Nothing to do (default)' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '1 => Generates php production files.' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '2 => Same as 1 + resource production files.' . PHP_EOL .
      str_repeat(' ', TasksManager::STRING_PAD_FOR_OPTION_FORMATTING) . '3 => Same as 2 + class mapping',
    'verbose' => 'If set to 1 => we print all the warnings during the production php files generation'
  ],
  ['optional', 'optional'],
  'Deployment'
];
