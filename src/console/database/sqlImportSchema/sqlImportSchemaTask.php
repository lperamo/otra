<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */


use otra\console\Database;

define('SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME', 2);
define('SQL_IMPORT_SCHEMA_ARG_CONFIGURATION', 3);

if (isset($argv[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME]))
{
  if (isset($argv[SQL_IMPORT_SCHEMA_ARG_CONFIGURATION]))
    Database::importSchema(
      $argv[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME],
      $argv[SQL_IMPORT_SCHEMA_ARG_CONFIGURATION]
    );
  else
    Database::importSchema($argv[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME]);
} else
  Database::importSchema();


