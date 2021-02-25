<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */


use otra\console\Database;

define('SQL_CLEAN_ARG_CLEANING_LEVEL', 2);

Database::clean(isset($argv[SQL_CLEAN_ARG_CLEANING_LEVEL]) && '1' === $argv[SQL_CLEAN_ARG_CLEANING_LEVEL]);
