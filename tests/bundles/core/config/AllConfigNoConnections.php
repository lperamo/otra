<?
/** THE framework production config
 *
 * @author Lionel Péramo */
namespace config;

define('CACHE_TIME', 300); // 5 minutes(5*60)
class AllConfig
{
  public static $verbose = 0,
    /* In order to not make new AllConfig::$blabla before calling CACHE_PATH, use directly AllConfig::$cache_path in this case
    (if we not use AllConfig::$blabla it will not load AllConfig even if it's in the use statement so the "defines" aren't accessible ) */
    $cache_path = CACHE_PATH,
    $version = 'v1',
    $defaultConn = 'CMS', // mandatory
    $dbConnections = []; // mandatory
}
?>
