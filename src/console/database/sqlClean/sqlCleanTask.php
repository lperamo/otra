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
 * @param array $argv
 *
 * @throws OtraException
 * @return void
 */
function sqlClean(array $argv) : void
{
  Database::clean(isset($argv[SQL_CLEAN_ARG_CLEANING_LEVEL]) && '1' === $argv[SQL_CLEAN_ARG_CLEANING_LEVEL]);
}
