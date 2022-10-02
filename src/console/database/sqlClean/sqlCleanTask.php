<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlClean;

use otra\console\database\Database;
use const otra\console\{CLI_BASE, CLI_SUCCESS, END_COLOR};

const SQL_CLEAN_ARG_CLEANING_LEVEL = 2;

/**
 * Cleans sql and yml files in the case where there are problems that had corrupted files.
 *
 *
 * @return void
 */
function sqlClean(array $argumentsVector) : void
{
  $extensive = isset($argumentsVector[SQL_CLEAN_ARG_CLEANING_LEVEL])
    && '1' === $argumentsVector[SQL_CLEAN_ARG_CLEANING_LEVEL];

  Database::initBase();

  if (file_exists(Database::$pathSqlFixtures))
  {
    array_map('unlink', glob(Database::$pathSqlFixtures . '/*.sql'));
    rmdir(Database::$pathSqlFixtures);
  }

  array_map('unlink', array_merge(
    glob(Database::$pathSql . '/*.sql'),
    glob(Database::$pathSql . 'truncate/*.sql')
  ));

  if ($extensive && file_exists(Database::$tablesOrderFile))
    unlink(Database::$tablesOrderFile);

  echo CLI_BASE, ($extensive) ? 'Full cleaning' : 'Cleaning', ' done', CLI_SUCCESS, ' ✔', END_COLOR, PHP_EOL;
}
