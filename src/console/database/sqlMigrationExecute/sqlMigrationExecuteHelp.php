<?php
declare(strict_types=1);
namespace otra\console\database\sqlMigrationGenerate;
use otra\console\TasksManager;

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
return [
  'Execute a single migration version up or down manually.',
  [
    'version' => 'The migration version',
    'way' => '\'up\' or \'down\'',
    'connection' => 'The database connection that you want to use from your configuration file'
  ],
  [
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::REQUIRED_PARAMETER,
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Database'
];
