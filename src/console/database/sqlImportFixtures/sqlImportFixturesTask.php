<?php
declare(strict_types=1);

use otra\console\Database;

define('SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME', 2);
define('SQL_IMPORT_FIXTURES_ARG_CONFIGURATION', 3);

if (isset($argv[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME]))
{
  if (isset($argv[SQL_IMPORT_FIXTURES_ARG_CONFIGURATION]))
    Database::importFixtures(
      $argv[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME],
      $argv[SQL_IMPORT_FIXTURES_ARG_CONFIGURATION]
    );
  else
    Database::importFixtures($argv[SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME]);
} else
  Database::importFixtures();
