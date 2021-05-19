<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlExecute;

use otra\console\database\Database;

Database::executeFile($argv[2], $argv[3] ?? null);
