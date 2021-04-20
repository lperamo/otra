<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */

use otra\console\Database;

const SQL_CREATE_DATABASE_ARG_DATABASE_NAME = 2;
const SQL_CREATE_DATABASE_ARG_FORCE = 3;

Database::createDatabase(
  $argv[SQL_CREATE_DATABASE_ARG_DATABASE_NAME],
  // Forces the value to be a boolean
  isset($argv[SQL_CREATE_DATABASE_ARG_FORCE])
  && 'true' === $argv[SQL_CREATE_DATABASE_ARG_FORCE]
);
