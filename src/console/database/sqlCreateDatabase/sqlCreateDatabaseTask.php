<?php
declare(strict_types=1);

use otra\console\Database;

Database::createDatabase(
  $argv[2],
  true === isset($argv[3])
    ? 'true' == $argv[3] // Forces the value to be a boolean
    : false
);
