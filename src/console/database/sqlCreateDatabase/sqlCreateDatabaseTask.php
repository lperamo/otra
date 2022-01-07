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
 * @param array $argv
 *
 * @throws OtraException
 * @return void
 */
function sqlCreateDatabase(array $argv) : void
{
  Database::createDatabase(
    $argv[SQL_CREATE_DATABASE_ARG_DATABASE_NAME],
    // Forces the value to be a boolean
    isset($argv[SQL_CREATE_DATABASE_ARG_FORCE])
    && 'true' === $argv[SQL_CREATE_DATABASE_ARG_FORCE]
  );
}
