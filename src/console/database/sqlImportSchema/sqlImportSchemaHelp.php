<?php
declare(strict_types=1);
return [
  'Creates the database schema from your database. (importSchema)',
  [
    'databaseName' => 'The database name ! If not specified, we use the database specified in the configuration file.',
    'configuration' => 'The configuration that you want to use from your configuration file.'
  ],
  ['optional', 'optional'],
  'Database'
];
