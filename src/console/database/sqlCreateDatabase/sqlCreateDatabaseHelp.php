<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Database creation, tables creation.(sql_generate_basic)',
  [
    'databaseName' => 'The database name !',
    'force' => 'If true, we erase the database !'
  ],
  [TasksManager::REQUIRED_PARAMETER, TasksManager::OPTIONAL_PARAMETER],
  'Database'
];
