<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlImportFixtures;

use otra\console\database\Database;
use otra\Session;

const
  SQL_IMPORT_FIXTURES_ARG_DATABASE_NAME = 2,
  SQL_IMPORT_FIXTURES_ARG_CONFIGURATION = 3;

Session::init();

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
