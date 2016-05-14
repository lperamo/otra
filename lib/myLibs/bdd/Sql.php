<?
/** Main sql management
 *
 * @author Lionel Péramo */

namespace lib\myLibs\bdd;

use lib\myLibs\{ Lionel_Exception, Session, bdd\Mysql, Logger };
use config\All_Config;

class Sql
{
  /**
   * @type array  $_sgbds       Available sgbds
   * @type array  $_activeConn
   * @type string $_currentConn
   * @type string $_db
   * @type string $_chosenSgbd
   */
  private static
    $_sgbds = ['MySQL', 'PDO_MySQL'],
    $_currentConn,
    $_currentSGBD,
    //$_activeSGBD = [],
    /** @type array Available active connections */
    $_activeConn = [];

  private
    $_db,
    $_chosenSgbd;

  public static
    $instance,
    /** @type sqlResource Shortcut */
    $_CURRENT_CONN;

  /**
   * @param string $sgbd
   */
  public function __construct(string $sgbd)
  {
    self::$_currentSGBD = __NAMESPACE__ . '\\' . $sgbd;
  }

  /** Destructor that closes the connection */
  public function __destruct() { $this->close(); }

  /**
   * Retrieves an instance of this class or creates it if it not exists yet
   *
   * @param $params
   *
   * @return bool|Sql|resource
   *
   * @throws Lionel_Exception
   * @internal param bool $selectDb Does we have to select the default database ? (omits it for PDO connection)
   * @internal param string $sgbd Kind of sgbd
   * @internal param string $conn Connection used (see All_Config files)
   */
  public static function getDB($conn = false) : Sql // $selectDb = true, $sgbd = false, $conn = false
  {
    /* If the connection is :
     * - specified => active we use it, otherwise => added if exists
     * - not specified => we use default connection and we adds it
     */
    if($conn)
    {
      if(isset(self::$_activeConn[$conn]))
      {
        self::$_currentConn = $conn;
      } else if(isset(All_Config::$dbConnections[$conn]))
      {
        self::$_currentConn = $conn;
        self::$_activeConn[$conn] = null;
      } else
        die(lightRedText('There is no ' . $conn . ' configuration available in the All_Config file !') . PHP_EOL);

    } else
    {
      if(!isset(All_Config::$defaultConn))
        throw new LionelException('Default connection not available ! Check your configuration.', 'E_CORE_ERROR');

      $conn = All_Config::$defaultConn;

      // If it's not already added, we add it
      if(!isset(self::$_activeConn[$conn]))
        self::$_activeConn[$conn] = null;

      self::$_currentConn = $conn;
    }

    extract( All_Config::$dbConnections[$conn]);

    /**
     * Extractions give those variables
     *
     * @type string $driver
     * @type string $host
     * @type int    $port
     * @type string $db
     * @type string $login
     * @type string $password
     */

    // Is this driver available ?
    if(in_array($driver, self::$_sgbds))
    {
      if (null == self::$_activeConn[self::$_currentConn])
      {
        self::$_activeConn[self::$_currentConn]['instance'] = new Sql($driver);
        require CORE_PATH . 'bdd/' . $driver . '.php';
      }

      extract(All_Config::$dbConnections[$conn ?: All_Config::$defaultConn]);
      /**
       *  Extractions give those variables
       * @type string $db
       * @type int    $port
       * @type string $host
       * @type string $login
       * @type string $password
       */

      $activeConn = &self::$_activeConn[self::$_currentConn];
      $activeConn['db'] = $db;
      self::$instance = $activeConn['instance'];

      try
      {
        $activeConn['conn'] = $activeConn['instance']->connect(
          strtolower(substr(strchr($driver, '_'), 1)) . ':dbname=' . $db . ';host=' .
          ('' == $port ? $host : $host . ':' . $port), $login, $password);

        self::$_CURRENT_CONN = $activeConn['conn'];
      }catch(\Exception $e)
      {
        echo $e->getMessage();
      }
    }else
      throw new Lionel_Exception('This SGBD doesn\'t exist...yet ! Available SGBD are : ' . implode(', ', self::$_sgbds), 'E_CORE_ERROR');

    return $activeConn['instance'];
  }

  /**
   * Connects to Mysql
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool|resource Returns a MySQL link identifier on success, or false on error
   */
  public static function connect(...$params)
  {
    //return call_user_func_array(self::$_currentSGBD . '::connect', $params);
    return call_user_func_array([self::$_currentSGBD, 'connect'], $params);

/*    return call_user_func_array(array(self::$_activeConn[self::$_currentConn]['instance'], 'connect'), $params);*/
  }

  /**
   * Connects to a database
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool True if successful
   */
  public function selectDb(...$params)
  {
    $retour = call_user_func_array(self::$_currentSGBD . '::selectDb', $params);
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
  public function query(string $query)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    if(isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_'])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func(self::$_currentSGBD . '::query', $query, self::$_CURRENT_CONN);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array The results
   */
  public function fetchAssoc(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::fetchAssoc', $params);
  }

    /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array The results
   */
  public function fetchArray(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::fetchArray', $params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array The results
   */
  public function fetchField(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::fetchField', $params);
  }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array The next result
   */
  public function fetchObject(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::fetchObject', $params);
  }

  /**
   * Returns all the results in an associative array
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public function values(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::values', $params);
  }

  /**
   * Returns all the results in an associative array (use it when the result set contains only one column)
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public function valuesOneCol(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::valuesOneCol', $params);
  }

  /**
   * Returns the only expected result.
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public function single(...$params){
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::single', $params);
  }

  /**
   * Close MySQL connection
   *
   * @return bool Returns true on success or false on failure
   */
  private static function close()
  {
    return call_user_func(self::$_currentSGBD . '::close', self::$_CURRENT_CONN);
  }

    /**
   * Free result memory
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool Returns true on success or false on failure.
   */
  public function freeResult(...$params)
  {
    if(isset($_SESSION['bootstrap']))
      return;

    return call_user_func_array(self::$_currentSGBD . '::freeResult', $params);
  }

  /**
   * Return the last inserted id
   *
   * @param string $sequenceName
   *
   * @return int The last inserted id
   */
  public function lastInsertedId(string $sequenceName = null)
  {
    return call_user_func(self::$_currentSGBD . '::lastInsertedId', $sequenceName);
  }

 /**
 * @param string $string
 * @return mixed
 */
  public function quote(string $string)
  {
    return call_user_func(self::$_currentSGBD . '::quote', $string);
  }
}
?>
