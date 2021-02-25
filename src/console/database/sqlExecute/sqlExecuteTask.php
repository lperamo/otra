<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */


use otra\console\Database;

Database::executeFile($argv[2], $argv[3] ?? null);
