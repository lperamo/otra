<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlExecute;

use otra\console\database\Database;
use otra\OtraException;

/**
 * @param array $argumentsVector
 *
 * @throws OtraException
 * @return void
 */
function sqlExecute(array $argumentsVector): void
{
  Database::executeFile($argumentsVector[2], $argumentsVector[3] ?? null);
}
