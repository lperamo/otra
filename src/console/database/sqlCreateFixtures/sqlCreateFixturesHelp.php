<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlCreateFixtures;

use otra\console\TasksManager;
use const otra\console\STRING_PAD_FOR_OPTION_FORMATTING;

return [
  'Generates fixtures sql files and executes them. (sql_generate_fixtures)',
  [
    'databaseName' => 'The database name !',
    'mask' => '1 => We erase the database' . PHP_EOL .
      STRING_PAD_FOR_OPTION_FORMATTING . '2 => We clean the fixtures sql files and we erase the database.'
  ],
  [TasksManager::REQUIRED_PARAMETER, TasksManager::OPTIONAL_PARAMETER],
  'Database'
];
