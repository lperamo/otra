<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlImportFixtures;

use otra\console\database\Database;
use otra\OtraException;
use otra\Session;
use ReflectionException;

const
  SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME = 2,
  SQL_IMPORT_FIXTURES_ARG_CONFIGURATION = 3;

/**
 * @param array $argumentsVector
 *
 * @throws ReflectionException
 * @throws OtraException
 * @return void
 */
function sqlImportFixtures(array $argumentsVector): void
{
  Session::init();

  if (isset($argumentsVector[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME]))
  {
    if (isset($argumentsVector[SQL_IMPORT_FIXTURES_ARG_CONFIGURATION]))
      Database::importFixtures(
        $argumentsVector[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME],
        $argumentsVector[SQL_IMPORT_FIXTURES_ARG_CONFIGURATION]
      );
    else
      Database::importFixtures($argumentsVector[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME]);
  }
  else
    Database::importFixtures();
}
