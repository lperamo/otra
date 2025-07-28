<?php
declare(strict_types=1);
namespace otra\console\helpAndTools\secretManager;

use otra\console\TasksManager;
use const otra\console\{CLI_INFO, CLI_INFO_HIGHLIGHT};

return [
  'Manages encrypted secrets securely with an interactive menu. ' . CLI_INFO_HIGHLIGHT . 'By default, it uses the "dev" environment.' . CLI_INFO,
  ['environment' => 'Specifies which environment to use (dev, test, preprod, prod). If not provided, it defaults to "dev".'],
  [TasksManager::OPTIONAL_PARAMETER],
  'Help and tools'
];
