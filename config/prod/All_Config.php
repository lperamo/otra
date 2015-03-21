<?
/** THE framework production config
 *
 * @author Lionel Péramo */
namespace config;

define('CACHE_TIME', 300); // 5 minutes(5*60)
class All_Config
{
  public static $verbose = 0,
    /* In order to not make new All_Config::$blabla before calling CACHE_PATH, use directly All_Config::$cache_path in this case
    (if we not use All_Config::$blabla it will not load All_Config even if it's in the use statement so the "defines" aren't accessible ) */
    $cache_path = CACHE_PATH,
    $version = 'v1',
    $defaultConn = 'CMS', // mandatory
    $dbConnections = array( // mandatory
      'CMS' => array(
        'driver' => 'Mysql',
        'host' => 'localhost',
        'port' => '',
        'db' => 'lpcms',
        'login' => '_lionel_87',
        'password' => 'e94b8f58',
        'motor' => 'InnoDB'
      )
    ); // 178.183.7.251 ip externe du nas
}
?>
