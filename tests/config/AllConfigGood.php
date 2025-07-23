<?php
declare(strict_types=1);

namespace otra\config;
use const otra\cache\php\BUNDLES_PATH;

AllConfig::$defaultConn = 'test';
AllConfig::$nodeBinariesPath = '/home/lionel/.nvm/versions/node/v20.19.1/bin/';
AllConfig::$sassLoadPaths = [BUNDLES_PATH . 'HelloWorld/resources/'];
AllConfig::$local = [ // mandatory
  'db'  => 
  [ 
    'test' => // do not modify this key name
    [ 
      'driver' => 'PDOMySQL',
      'host' => 'localhost',
      'port' => '',
      'db' => 'testDB', // do not modify this!
      'motor' => 'InnoDB'
    ],
    'testOtherDriver' => // do not modify this key name 
    [
      'driver' => 'OtherDriver',
      'host' => 'localhost',
      'port' => '',
      'db' => 'testDB' // do not modify this!
    ]
  ]
];
AllConfig::$debugConfig = [
  'maxChildren' => 130,
  'maxData' => 514,
  'maxDepth' => 11
];
AllConfig::$local['db']['test']['login'] = $_SERVER['TEST_LOGIN'];
AllConfig::$local['db']['test']['password'] = $_SERVER['TEST_PASSWORD'];
