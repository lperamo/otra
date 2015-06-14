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
  /**
   * @type array  $_sgbds             Available sgbds
   * @type array  $_activeConn
   * @type string $_currentConn
   * @type string $_db
   * @type string $_chosenSgbd
   */
  private static
    $_sgbds = ['MySQL', 'PDO_MySQL'],
    $_currentConn,
    $_activeConn = [];

  private
    $_db,
    $_chosenSgbd;

  public
    $_i,
    $connection;

  /**
   * @param string $sgbd
   */
  public function __construct($sgbd) { $this->_chosenSgbd = __NAMESPACE__ . '\\' . $sgbd; }

  /** Destructor that closes the connection */
  public function __destruct() { self::close(); }

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
  public function getDB($conn = false) // $selectDb = true, $sgbd = false, $conn = false
  {
    /* If the connection is :
     * - specified => active we use it, otherwise => added if exists
     * - not specified => we use default connection and we adds it
     */
    if($conn)
    {
      if(isset($_activeConnections[$conn]))
      {
        self::$_currentConn = $conn;
      } else
      {
        if(isset(All_Config::$dbConnections[$conn]))
        {
          self::$_currentConn = $conn;
          self::$_activeConn[] = $conn;
        }
      }
    } else
    {
      if(!isset(All_Config::$defaultConn))
        throw new LionelException('Default connection not available ! Check your configuration.', 'E_CORE_ERROR');

      $conn = All_Config::$defaultConn;

      if(!isset($_activeConnections[$conn]))
      {
        self::$_activeConn[] = $conn;
      }

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
    if(in_array($driver, self::$_sgbds))
    {
      if (null == $this->_i)
      {
        $this->_i = new Sql($driver);
        require $driver . '.php';
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
      $this->_db = $db;
      //var_dump(strtolower(substr(strchr($driver, '_'), 1)) . ':dbname=' . $db . ';host=' .
      //         ('' == $port ? $host : $host . ':' . $port), $login, $password);die;
      try
      {
        $this->_i = $this->_i->connect(
          strtolower(substr(strchr($driver, '_'), 1)) . ':dbname=' . $db . ';host=' .
          ('' == $port ? $host : $host . ':' . $port), $login, $password);
      }catch(\Exception $e)
      {
        echo $e->getMessage();
      }
    }else
      throw new Lionel_Exception('This SGBD doesn\'t exist...yet ! Available SGBD are : ' . implode(', ', $this->_sgbds), 'E_CORE_ERROR');

    return $this->_i;
  }

  /**
   * Connects to Mysql
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool|resource Returns a MySQL link identifier on success, or false on error
   */
  public function connect(...$params)
  {
    return call_user_func_array($this->_chosenSgbd . '::connect', $params);
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
    $retour = call_user_func_array($this->_chosenSgbd . '::selectDb', $params);
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

    if(isset($_SESSION['debuglp_']) && 'Dev' == $_SESSION['debuglp_'])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func($this->_chosenSgbd . '::query', $query, $this->connection);
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

    return call_user_func_array($this->_chosenSgbd . '::fetchAssoc', $params);
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

    return call_user_func_array($this->_chosenSgbd . '::fetchArray', $params);
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

    return call_user_func_array($this->_chosenSgbd . '::fetchField', $params);
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

    return call_user_func_array($this->_chosenSgbd . '::fetchObject', $params);
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

    var_dump($this->_chosenSgbd);die;
    return call_user_func_array($this->_chosenSgbd . '::values', $params);
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

    return call_user_func_array($this->_chosenSgbd . '::valuesOneCol', $params);
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

    return call_user_func_array($this->_chosenSgbd . '::single', $params);
  }

  /**
   * Close MySQL connection
   *
   * @return bool Returns true on success or false on failure
   */
  private function close()
  {
    return call_user_func($this->_chosenSgbd . '::close', $this->connection);
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

    return call_user_func_array($this->_chosenSgbd . '::freeResult', $params);
  }

  /**
   * Return the last inserted id
   *
   * @return int The last inserted id
   */
  public function lastInsertedId()
  {
    return call_user_func($this->_chosenSgbd . '::lastInsertedId', $this->_instance);
  }
}
?>
