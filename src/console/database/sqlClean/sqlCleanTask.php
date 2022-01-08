<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlClean;

use otra\console\database\Database;
use otra\OtraException;

const SQL_CLEAN_ARG_CLEANING_LEVEL = 2;

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function sqlClean(array $argumentsVector) : void
{
  Database::clean(isset($argumentsVector[SQL_CLEAN_ARG_CLEANING_LEVEL]) && '1' === $argumentsVector[SQL_CLEAN_ARG_CLEANING_LEVEL]);
}
