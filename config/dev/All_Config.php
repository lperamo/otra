<?
/** THE framework development config
 *
 * @author Lionel Péramo */
namespace config;

define('CACHE_TIME', 300); // 5 minutes(5*60)
class All_Config
{
  public static $verbose = 1,
    $debug = true,
    $cache = false,
    /* In order to not make new All_Config::$blabla before calling CACHE_PATH, use directly All_Config::$cache_path in this case
    (if we not use All_Config::$blabla it will not load All_Config even if it's in the use statement so the "defines" aren't accessible ) */
    $cache_path = CACHE_PATH,
    $version = 'v1',
    $defaultConn = 'CMS', // mandatory
    $dbConnections = array( // mandatory
      'CMS' => array(
        'driver' => 'PDO_MySQL',
        'host' => '127.0.0.1',
        'port' => '',
        'db' => 'lpcms',
        'login' => '_lionel_87',
        'password' => '_lmo5uj4FF*8', // _lmo5uj4FF*8
        'motor' => 'InnoDB'
      )
    );
}
?>
