<?php
declare(strict_types=1);
return [
  'Import the fixtures from database into ' . CLI_YELLOW . 'config/data/yml/fixtures' . CLI_CYAN . '.',
  [
    'databaseName' => 'The database name ! If not specified, we use the database specified in the configuration file.',
    'configuration' => 'The configuration that you want to use from your configuration file.'
  ],
  ['optional', 'optional'],
  'Database'
];
