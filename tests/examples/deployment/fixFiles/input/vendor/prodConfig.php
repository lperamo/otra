<?php
declare(strict_types=1);

/** THE framework production config
 *
 * @author Lionel Péramo
 */
namespace otra\config;

use const otra\cache\php\{BASE_PATH,BUNDLES_PATH,CACHE_PATH};

const CACHE_TIME = 300; // 5 minutes(5*60)

/**
 * @package config
 */
abstract class AllConfig
{
  public static int $verbose = 0;
  public static bool $cssSourceMaps = true;
  public static string
    /* To not make new AllConfig::$foo before calling CACHE_PATH, use directly AllConfig::$cachePath in this case
    (if we not use AllConfig::$foo it will not load AllConfig even if it's in the use statement so the "defines" aren't
    accessible) */
    $cachePath = CACHE_PATH,
    $defaultConn = 'CMS',  // mandatory to modify it later if needed
    $nodeBinariesPath = '/home/lionel/.nvm/versions/node/v20.5.0/bin/';  // mandatory to modify it later if needed
  public static array
    $debugConfig = [
    //      'maxChildren' => 130,
    'maxData' => 514,
    'maxDepth' => 6
  ], // mandatory to modify it later if needed
    $deployment = [
    'domainName' => 'otra.tech',
    'server' => 'lionelp@vps812032.ovh.net',
    'port' => 49153,
    'folder' => '/var/www/html/test/',
    'privateSshKey' => '~/.ssh/id_rsa',
    'gcc' => true
  ],
    $dbConnections = [
    'CMS' => [
      'driver' => 'PDOMySQL',
      'host' => 'localhost',
      'port' => '',
      'db' => 'lpcms',
      'motor' => 'InnoDB'
    ]
  ], // mandatory to modify it later if needed
    $sassLoadPaths = [BUNDLES_PATH . 'HelloWorld/resources/']; // mandatory to modify it later if needed
}

echo 'test';
