<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlExecute;

use Exception;
use otra\bdd\Sql;
use otra\console\database\Database;
use otra\OtraException;

const
  ARG_FILE_TO_EXECUTE = 2,
  ARG_DATABASE_NAME = 3;

/**
 * @param array $argumentsVector
 *
 * @throws OtraException if the file to execute doesn't exist
 * @return void
 */
function sqlExecute(array $argumentsVector): void
{
  $fileToExecute = $argumentsVector[ARG_FILE_TO_EXECUTE];
  /** Where to execute the SQL file */
  $databaseName = $argumentsVector[ARG_DATABASE_NAME] ?? null;

  if (!file_exists($fileToExecute))
    throw new OtraException(
      'The file "' . $fileToExecute . '" does not exist !',
      E_CORE_ERROR,
      __FILE__,
      __LINE__
    );

  if (!Database::$init)
    Database::init();

  Sql::getDb();

  $instance = &Sql::$instance;
  $instance->beginTransaction();

  if (null !== $databaseName)
  {
    // Selects the database by precaution
    $dbResult = $instance->query('USE `' . $databaseName . '`');
    $instance->freeResult($dbResult);
  }

  try
  {
    // Runs the file
    $dbResult = $instance->query(file_get_contents($fileToExecute));
    $instance->freeResult($dbResult);
  } catch(Exception $exception)
  {
    $instance->rollBack();
    throw new OtraException('Procedure aborted. ' . $exception->getMessage());
  }

  $instance->commit();
}
