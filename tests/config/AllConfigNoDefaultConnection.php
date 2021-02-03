<?php
declare(strict_types=1);

namespace config;

define('CACHE_PATH', BASE_PATH . 'cache/');

// Framework core resources
define('CORE_VIEWS_PATH', CORE_PATH . 'views/');
define('CORE_CSS_PATH', CORE_PATH . 'resources/css/');
define('CORE_JS_PATH', CORE_PATH . 'resources/js/');

define('VERSION', 'v1');
define('RESOURCE_FILE_MIN_SIZE', 21000); // n characters

define('CACHE_TIME', 300); // 5 minutes(5*60)
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
