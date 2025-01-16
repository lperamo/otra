<?php
declare(strict_types=1);

namespace otra\config;

use const otra\cache\php\CACHE_PATH;

const
  VERSION = '2025.0.0',
  RESOURCE_FILE_MIN_SIZE = 21000, // n characters
  CACHE_TIME = 300; // 5 minutes(5*60)

/**
 * Class AllConfig
 *
 * @package config
 */
class AllConfig
{
  public static int $verbose = 0;
  public static string
    /* In order to not make new AllConfig::$foo before calling CACHE_PATH, use directly AllConfig::$cachePath in this
    case
    (if we not use AllConfig::$foo it will not load AllConfig even if it's in the use statement so the "defines" aren't
    accessible ) */
    $cachePath = CACHE_PATH,
    $version = 'v1',
    $defaultConn = ''; // mandatory in order to modify it later if needed
  public static array $dbConnections = []; // mandatory in order to modify it later if needed
}
