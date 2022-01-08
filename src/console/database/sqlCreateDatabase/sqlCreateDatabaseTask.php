<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlCreateDatabase;

use otra\console\database\Database;
use otra\OtraException;

const
  SQL_CREATE_DATABASE_ARG_DATABASE_NAME = 2,
  SQL_CREATE_DATABASE_ARG_FORCE = 3;

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function sqlCreateDatabase(array $argumentsVector) : void
{
  Database::createDatabase(
    $argumentsVector[SQL_CREATE_DATABASE_ARG_DATABASE_NAME],
    // Forces the value to be a boolean
    isset($argumentsVector[SQL_CREATE_DATABASE_ARG_FORCE])
    && 'true' === $argumentsVector[SQL_CREATE_DATABASE_ARG_FORCE]
  );
}
