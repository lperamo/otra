<?php

use lib\myLibs\console\TasksManager;

return [
  'Creates a model.',
  [
    'bundle' => 'The bundle in which the model is created',
    'how' => '1 => Creates from nothing' . PHP_EOL .
      str_repeat(' ', TasksManager::$STRING_PAD_FOR_OPTION_FORMATTING) . '2 => One model from '. CLI_YELLOW . 'schema.yml' .
      CLI_CYAN .
      PHP_EOL .
      str_repeat(' ', TasksManager::$STRING_PAD_FOR_OPTION_FORMATTING) . '3 => All models from ' . CLI_YELLOW .'schema.yml' .
      CLI_CYAN
  ],
  ['optional', 'optional'],
  'Architecture'
];
