<?php
declare(strict_types=1);

namespace otra\config;
AllConfig::$defaultConn = 'test';
AllConfig::$local = [ // mandatory
  'db' =>
  [
    'test' => // do not modify this key name
    [
      'driver' => 'hello',
      'host' => 'localhost',
      'port' => '',
      'db' => 'testDB', // do not modify this!
      'motor' => 'InnoDB'
    ]
  ]
];
AllConfig::$local['db']['test']['login'] = $_SERVER['TEST_LOGIN'];
AllConfig::$local['db']['test']['password'] = $_SERVER['TEST_PASSWORD'];
