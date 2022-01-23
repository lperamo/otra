<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlCreateDatabase;

use Exception;
use otra\bdd\Sql;
use otra\console\database\Database;
use otra\OtraException;
use const otra\console\{CLI_BASE, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, END_COLOR};

const
  SQL_CREATE_DATABASE_ARG_DATABASE_NAME = 2,
  SQL_CREATE_DATABASE_ARG_FORCE = 3;

/**
 * Creates the sql database schema file if it doesn't exist and runs it
 *
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function sqlCreateDatabase(array $argumentsVector) : void
{
  $databaseName = $argumentsVector[SQL_CREATE_DATABASE_ARG_DATABASE_NAME];
  // Forces the value to be a boolean
  /** If true, we erase the database before the creation of tables. */
  $force = isset($argumentsVector[SQL_CREATE_DATABASE_ARG_FORCE])
    && 'true' === $argumentsVector[SQL_CREATE_DATABASE_ARG_FORCE];

  if (!Database::$init)
    Database::init();

  // No need to get DB twice (getDb is already used in dropDatabase function)
  ($force)
    ? Database::dropDatabase($databaseName)
    : Sql::getDb(null, false);

  $instance = &Sql::$instance;
  $instance->beginTransaction();
  $databaseFile = Database::generateSqlSchema($databaseName, $force);

  try
  {
    $dbResult = $instance->query(file_get_contents($databaseFile));
    $instance->commit();
    $instance->freeResult($dbResult);
  } catch(Exception $exception)
  {
    // Checks if we already commit, otherwise we do not have an active transaction, so we check this to prevent
    // showing an error about not having active transaction
    if ($instance->inTransaction())
      $instance->rollBack();

    throw new OtraException('Procedure aborted when executing ' . $exception->getMessage());
  }

  echo CLI_BASE, 'Database ', CLI_INFO_HIGHLIGHT, $databaseName, CLI_BASE, ' created', CLI_SUCCESS, ' ✔', END_COLOR,
  PHP_EOL;
}
