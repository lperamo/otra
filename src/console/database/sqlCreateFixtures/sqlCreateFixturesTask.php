<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlCreateFixtures;

use otra\console\database\Database;
use otra\OtraException;

const
  SQL_CREATE_FIXTURES_ARG_DATABASE_NAME = 2,
  SQL_CREATE_FIXTURES_ARG_MASK = 3;

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function sqlCreateFixtures(array $argumentsVector) : void
{
  Database::createFixtures(
    $argumentsVector[SQL_CREATE_FIXTURES_ARG_DATABASE_NAME],
    isset($argumentsVector[SQL_CREATE_FIXTURES_ARG_MASK]) ? (int)$argumentsVector[SQL_CREATE_FIXTURES_ARG_MASK] : 0
  );
}
