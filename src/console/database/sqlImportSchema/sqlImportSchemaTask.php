<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlImportSchema;
use otra\console\database\Database;

const
  SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME = 2,
  SQL_IMPORT_SCHEMA_ARG_CONFIGURATION = 3;

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


