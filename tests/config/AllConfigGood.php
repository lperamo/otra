<?php
declare(strict_types=1);

namespace config;
AllConfig::$defaultConn = 'test';
AllConfig::$dbConnections = [ // mandatory
  'test' => [ // do not modify this key name
    'driver' => 'PDOMySQL',
    'host' => 'localhost',
    'port' => '',
    'db' => 'testDB', // do not modify this !
    'motor' => 'InnoDB'
  ],
  'testOtherDriver' => [ // do not modify this key name
    'driver' => 'OtherDriver',
    'host' => 'localhost',
    'port' => '',
    'db' => 'testDB', // do not modify this !
  ]
];
AllConfig::$dbConnections['test']['login'] = $_SERVER['TEST_LOGIN'];
AllConfig::$dbConnections['test']['password'] = $_SERVER['TEST_PASSWORD'];
