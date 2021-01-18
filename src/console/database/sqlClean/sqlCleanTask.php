<?php
declare(strict_types=1);

use otra\console\Database;

define('SQL_CLEAN_ARG_CLEANING_LEVEL', 2);

Database::clean(isset($argv[SQL_CLEAN_ARG_CLEANING_LEVEL]) && '1' === $argv[SQL_CLEAN_ARG_CLEANING_LEVEL]);
