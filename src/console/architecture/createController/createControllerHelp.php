<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Creates controllers.',
  [
    'bundle' => 'The bundle where you want to put controllers',
    'module' => 'The module where you want to put controllers',
    'controller' => 'The name of the controller!',
    'interactive' => 'If set to false, no question will be asked but the status messages are shown. Defaults to true.'
  ],
  [
    TasksManager::OPTIONAL_REQUIRED,
    TasksManager::OPTIONAL_REQUIRED,
    TasksManager::OPTIONAL_REQUIRED,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Architecture'
];
