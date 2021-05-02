<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\deployment
 */
declare(strict_types=1);

namespace otra\console\deployment\genServerConfig;

use otra\console\TasksManager;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_WARNING};

return [
  'Generates a server configuration adapted to OTRA.',
  [
    'file name' => 'Name of the file to put the generated configuration',
    'environment' => CLI_INFO_HIGHLIGHT . DEV . CLI_INFO . ' (default) or ' . CLI_INFO_HIGHLIGHT . PROD,
    'serverTechnology' => CLI_INFO_HIGHLIGHT . 'nginx' . CLI_INFO . ' (default) or ' . CLI_INFO_HIGHLIGHT . 'apache' .
      CLI_WARNING . ' (but works only for Nginx for now)'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Deployment'
];

