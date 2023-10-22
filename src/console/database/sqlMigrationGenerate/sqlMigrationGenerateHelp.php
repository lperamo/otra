<?php
declare(strict_types=1);
namespace otra\console\database\sqlMigrationGenerate;
use otra\console\TasksManager;

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
return [
  'Creates a new blank database migration file',
  [
    'connection' => 'The database connection that you want to use from your configuration file'
  ],
  [
    TasksManager::OPTIONAL_PARAMETER
  ],
  'Database'
];
