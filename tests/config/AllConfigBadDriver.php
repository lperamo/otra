<?php
declare(strict_types=1);

namespace otra\config;
AllConfig::$defaultConn = 'test';
AllConfig::$dbConnections = [ // mandatory
  'test' => [ // do not modify this key name
    'driver' => 'hello',
    'host' => 'localhost',
    'port' => '',
    'db' => 'testDB', // do not modify this!
    'motor' => 'InnoDB'
  ]
];
AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
