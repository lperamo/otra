<?
/** Main sql management
 *
 * @author Lionel Péramo */

namespace lib\myLibs\core\bdd;

use lib\myLibs\core\Lionel_Exception,
  lib\myLibs\core\Session,
  lib\myLibs\core\bdd\Mysql,
  config\All_Config,
  lib\myLibs\core\Logger;

class Sql
{
  private static
    $_sgbds = ['Mysql'], // Available sgbds
    $_db,
    $_chosenSgbd,
    $_link_identifier;

  public static $instance;

  public function __construct($sgbd) { self::$_chosenSgbd = __NAMESPACE__ . '\\' . $sgbd; }

  /** Destructor that closes the connection */
  public function __destruct() { self::close(); }

  /**
   * Retrieves an instance of this class or creates it if it not exists yet
   *
   * @param bool   $selectDb Does we have to select the default database ?
   * @param string $sgbd     Kind of sgbd
   * @param string $conn     Connection used (see All_Config files)
   *
   * @return
   */
  public static function getDB($selectDb = true, $sgbd = false, $conn = false)
  {
    $sgbd = $sgbd ?: All_Config::$dbConnections[All_Config::$defaultConn]['driver'];

    if(in_array($sgbd, self::$_sgbds))
    {
      if (null == self::$instance)
      {
        self::$instance = new Sql($sgbd);
        require $sgbd . '.php';
      }

      extract(All_Config::$dbConnections[$conn ?: All_Config::$defaultConn]);
      self::$_db = $db;
      self::$_link_identifier = self::connect('' == $port ? $host : $host . ':' . $port, $login, $password);
    }else
      throw new Lionel_Exception('This SGBD doesn\'t exist...yet !', 'E_CORE_ERROR');

    $selectDb && self::$instance->selectDb();

    return self::$instance;
  }

  /**
   * Connects to Mysql
   *
   * @param string $server   Mysql server
   * @param string $username Username
   * @param string $password Password
   *
   * @return bool|resource Returns a MySQL link identifier on success, or false on error
   */
  public static function connect($server = 'localhost:3306', $username = 'root', $password = '')
  {
    return call_user_func(self::$_chosenSgbd . '::connect', $server, $username, $password);
  }

  /**
   * Connects to a database
   *
   * @param string $link_identifier Link identifier
   *
   * @return bool True if successful
   */
  public function selectDb()
  {
    $retour = call_user_func(self::$_chosenSgbd . '::selectDb', self::$_db, self::$_link_identifier);
    $this->query('SET NAMES UTF8');

    return $retour;
  }

  /**
   * Sends a SQL query !
   *
   * @param string $query SQL query.
   * The query string should not end with a semicolon. Data inside the query should be properly escaped.
   *
   * @return bool|resource Returns a resource on success, or false on error
   */
  public function query($query)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    if(isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_']){
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func(self::$_chosenSgbd . '::query', $query, self::$_link_identifier);
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result in an associative array
   *
   * @return array The results
   */
  public function fetchAssoc($result)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::fetchAssoc', $result);
  }

    /**
   * Returns the results
   *
   * @param resource $result The query result
   *
   * @return array The results
   */
  public function fetchArray($result)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::fetchArray', $result);
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result in an object
   *
   * @return array The results
   */
  public static function fetchField($result)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::fetchField', $result);
  }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param resource $result The query result
   * @param string   $class_name [optional] <p>
   * Class name to instantiate, set the properties of and return. Default: returns a stdClass object.</p>
   *
   * @param array    $params [optional] Optional array of parameters to pass to the constructor
   *  for class_name objects.
   *
   * @return array The next result
   */
  public static function fetchObject($result, $class_name = null, array $params = [] )
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::fetchObject', $result, $class_name, $params);
  }

  /**
   * Returns all the results in an associative array
   *
   * @param resource $result The query result
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function values($result)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::values', $result);
  }

  /**
   * Returns all the results in an associative array (use it when the result set contains only one column)
   *
   * @param resource $result The query result
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function valuesOneCol($result)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::valuesOneCol', $result);
  }

  /**
   * Returns the only expected result.
   *
   * @param resource $result The query result
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public static function single($result){
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::single', $result);
  }

  /**
   * Close MySQL connection
   *
   * @return bool Returns true on success or false on failure
   */
  private static function close()
  {
    return call_user_func(self::$_chosenSgbd . '::close', self::$_link_identifier);
  }

    /**
   * Free result memory
   *
   * @param resource $result
   *
   * @return bool Returns true on success or false on failure.
   */
  public static function freeResult($result)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func(self::$_chosenSgbd . '::freeResult', $result);
  }

  /**
   * Return the last inserted id
   *
   * @return int The last inserted id
   */
  public static function lastInsertedId()
  {
    return call_user_func(self::$_chosenSgbd . '::lastInsertedId', self::$_link_identifier);
  }
}
?>
