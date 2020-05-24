<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Executes the sql script',
  [
    'file' => 'File that will be executed',
    'database' => 'Database to use for this script'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Database'
];
