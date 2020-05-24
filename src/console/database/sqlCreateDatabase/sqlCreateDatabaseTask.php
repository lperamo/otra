<?php
declare(strict_types=1);

use otra\console\Database;
define('SQL_CREATE_DATABASE_ARG_DATABASE_NAME', 2);
define('SQL_CREATE_DATABASE_ARG_FORCE', 3);

Database::createDatabase(
  $argv[SQL_CREATE_DATABASE_ARG_DATABASE_NAME],
  // Forces the value to be a boolean
  isset($argv[SQL_CREATE_DATABASE_ARG_FORCE])
  && 'true' === $argv[SQL_CREATE_DATABASE_ARG_FORCE]
);
