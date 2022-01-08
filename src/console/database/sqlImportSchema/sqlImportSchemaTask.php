<?php
/**
 * @author Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);
namespace otra\console\database\sqlImportSchema;

use otra\console\database\Database;
use otra\OtraException;

const
  SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME = 2,
  SQL_IMPORT_SCHEMA_ARG_CONFIGURATION = 3;

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function sqlImportSchema(array $argumentsVector) : void
{
  if (isset($argumentsVector[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME]))
  {
    if (isset($argumentsVector[SQL_IMPORT_SCHEMA_ARG_CONFIGURATION]))
      Database::importSchema(
        $argumentsVector[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME],
        $argumentsVector[SQL_IMPORT_SCHEMA_ARG_CONFIGURATION]
      );
    else
      Database::importSchema($argumentsVector[SQL_IMPORT_SCHEMA_ARG_DATABASE_NAME]);
  }
  else
    Database::importSchema();
}
