<?php
declare(strict_types=1);

use otra\console\TasksManager;

return [
  'Generates a server configuration adapted to OTRA.',
  [
    'file name' => 'Name of the file to put the generated configuration',
    'environment' => CLI_LIGHT_CYAN . 'dev' . CLI_CYAN . ' (default) or ' . CLI_LIGHT_CYAN . 'prod',
    'serverTechnology' => CLI_LIGHT_CYAN . 'nginx' . CLI_CYAN . ' (default) or ' . CLI_LIGHT_CYAN . 'apache' . CLI_YELLOW .
      ' (but works only for Nginx for now)'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];

