<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\database
 */
declare(strict_types=1);

namespace otra\console\database\sqlCreateFixtures;

use otra\console\database\Database;

const SQL_CREATE_FIXTURES_ARG_DATABASE_NAME = 2,
SQL_CREATE_FIXTURES_ARG_MASK = 3;
Database::createFixtures(
  $argv[SQL_CREATE_FIXTURES_ARG_DATABASE_NAME],
  isset($argv[SQL_CREATE_FIXTURES_ARG_MASK]) ? (int)$argv[SQL_CREATE_FIXTURES_ARG_MASK] : 0
);
