<?php
declare(strict_types=1);

use otra\console\Database;

isset($argv[2])  === true
? (isset($argv[3]) === true ? Database::importSchema($argv[2], $argv[3]) : Database::importSchema($argv[2]))
: Database::importSchema();
