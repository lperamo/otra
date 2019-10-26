<?
/** THE framework development config
 *
 * @author Lionel Péramo */
namespace config;

define('CACHE_TIME', 300); // 5 minutes(5*60)
class AllConfig
{
  public static $verbose = 1,
    $debug = false,
    $cache = false,
    /* In order to not make new AllConfig::foo before calling CACHE_PATH, use directly AllConfig::$cache_path in this
    case
    (if we not use AllConfig::foo it will not load AllConfig even if it's in the use statement so the "defines" aren't
    accessible ) */
    $cache_path = CACHE_PATH,
    $version = 'v1',
    $defaultConn = '', // mandatory in order to modify it later if needed
    $dbConnections = []; // mandatory in order to modify it later if needed
}
?>
