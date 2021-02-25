<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\database
 */


use otra\console\Database;

Database::createFixtures(
  $argv[2],
  true === isset($argv[3]) ? (int) $argv[3] : 0
);
