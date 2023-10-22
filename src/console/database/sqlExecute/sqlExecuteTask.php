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
use const otra\cache\php\CORE_PATH;
use function otra\tools\files\returnLegiblePath;

const
  ARG_FILE_TO_EXECUTE = 2,
  ARG_DATABASE_NAME = 3;

/**
 * @param array<int, string> $argumentsVector Command-line arguments, similar to those provided by $argv.
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
    require CORE_PATH . 'tools/files/returnLegiblePath.php';
    throw new OtraException(
      'Procedure aborted in file ' . returnLegiblePath($fileToExecute) . '.' . PHP_EOL . $exception->getMessage()
    );
  }

  $instance->commit();
}
