<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\deployment
 */

use otra\console\TasksManager;

return [
  'Generates a server configuration adapted to OTRA.',
  [
    'file name' => 'Name of the file to put the generated configuration',
    'environment' => CLI_INFO_HIGHLIGHT . 'dev' . CLI_INFO . ' (default) or ' . CLI_INFO_HIGHLIGHT . 'prod',
    'serverTechnology' => CLI_INFO_HIGHLIGHT . 'nginx' . CLI_INFO . ' (default) or ' . CLI_INFO_HIGHLIGHT . 'apache' . CLI_WARNING .
      ' (but works only for Nginx for now)'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];

