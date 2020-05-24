<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Creates modules.',
  [
    'bundle' => 'The name of the bundle in which you want to put modules',
    'module' => 'The name of the module!',
    'interactive' => 'If set to false, no question will be asked but the status messages are shown. Defaults to true.'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Architecture'
];
