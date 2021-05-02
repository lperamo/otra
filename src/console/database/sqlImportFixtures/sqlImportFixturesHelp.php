<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlImportFixtures;

use otra\console\TasksManager;
use const otra\console\{CLI_INFO,CLI_WARNING};

return [
  'Import the fixtures from database into ' . CLI_WARNING . 'config/data/yml/fixtures' . CLI_INFO . '.',
  [
    'databaseName' => 'The database name ! If not specified, we use the database specified in the configuration file.',
    'configuration' => 'The configuration that you want to use from your configuration file.'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Database'
];
