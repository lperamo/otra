<?php
/** Main sql management
 *
 * @author Lionel Péramo */

namespace otra\bdd;

use otra\{ OtraException, Logger };
use config\AllConfig;

class Sql
{
  /**
   * @type array  $_dbmsCollection       Available dbmsCollection
   * @type array  $_activeConn
   */
  private static array
    $_dbmsCollection = ['Pdomysql'],
    $_activeConn = [];

  public static ?\PDO $_currentConn = null;
  public static string
    $_currentDBMS = '',
    $_currentConnectionName;

  public static ?Sql $instance;

  private function __construct()
  {
  }

  /** Destructor that closes the connection */
  public function __destruct() {
    $this->close();
  }

  /**
   * Retrieves an instance of this class or creates it if it not exists yet.
   *
   * @param      $conn
   * @param bool $haveDatabase Do we have a database ? Can be no, if we want to CREATE a database.
   *
   * @return bool|Sql|resource
   *
   * @throws OtraException
   *
   * @internal param string $dbms     Kind of dbms
   * @internal param string $conn     Connection used (see AllConfig files)
   * @internal param bool   $selectDb Does we have to select the default database ? (omits it for PDO connection)
   */
  public static function getDB($conn = null, bool $haveDatabase = true) : Sql
  {
    /* If the connection is :
     * - specified => active we use it, otherwise => added if exists
     * - not specified => we use default connection and we adds it
     */
    if (null !== $conn)
    {
      if (true === isset(self::$_activeConn[$conn]))
      {
        $currentConnection = $conn;
      } else if (true === isset(AllConfig::$dbConnections[$conn]))
      {
        $currentConnection = $conn;
        self::$_activeConn[$conn] = null;
      } else
        throw new OtraException('There is no \'' . $conn . '\' configuration available in the AllConfig file !');
    } else
    {
      if (AllConfig::$defaultConn === '')
        throw new OtraException('There is no default connection in your configuration ! Check your configuration.', E_CORE_ERROR);

      $conn = AllConfig::$defaultConn;

      // If it's not already added, we add it
      if (false === isset(self::$_activeConn[$conn]))
        self::$_activeConn[$conn] = null;

      $currentConnection = $conn;
    }

    extract(AllConfig::$dbConnections[$conn]);

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

    $driver = ucfirst(strtolower($driver));

    // Is this driver available ?
    if (true === in_array($driver, self::$_dbmsCollection))
    {
      if (null == self::$_activeConn[$currentConnection])
      {
        self::$_currentDBMS = __NAMESPACE__ . '\\' . ucfirst(strtolower($driver));
        self::$_activeConn[$currentConnection]['instance'] = new Sql;
        require CORE_PATH . 'bdd/' . $driver . '.php';
      }

      extract(AllConfig::$dbConnections[$conn ?: AllConfig::$defaultConn]);
      /**
       *  Extractions give those variables
       *
       * @type string $db
       * @type int    $port
       * @type string $host
       * @type string $login
       * @type string $password
       */

      $activeConn = &self::$_activeConn[$currentConnection];
      $activeConn['db'] = $db;
      self::$instance = $activeConn['instance'];

      try
      {
        $activeConn['conn'] = $activeConn['instance']->connect(
          strtolower(substr($driver, 3))
            . (true === $haveDatabase  ? ':dbname=' . $db . ';' : ':')
            . 'host=' . ('' == $port ? $host : $host . ':' . $port),
          $login,
          $password
        );

        self::$_currentConn = $activeConn['conn'];
        self::$_currentConnectionName = $currentConnection;
      }catch(\Exception $e)
      {
        throw new OtraException($e->getMessage());
      }
    }else
      throw new OtraException('This DBMS \'' . $driver . '\' is not available...yet ! Available DBMS are : ' . implode(', ', self::$_dbmsCollection), E_CORE_ERROR);

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
    return call_user_func_array([self::$_currentDBMS, 'connect'], $params);
  }

  /**
   * Connects to a database
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool True if successful
   * @throws OtraException
   */
  public function selectDb(...$params)
  {
    try
    {
      $return = call_user_func_array(self::$_currentDBMS . '::selectDb', $params);
      // @codeCoverageIgnoreStart
      $this->query('SET NAMES UTF8');

      return $return;
      // @codeCoverageIgnoreEnd
    } catch (\Exception $exception)
    {
      $currentDriver = AllConfig::$dbConnections[self::$_currentConnectionName]['driver'];
      $message = 'This function does not exist with \'' . $currentDriver . '\'.';

      if ($currentDriver === 'PDOMySQL')
        throw new OtraException($message . '.. and mysql driver is now deprecated !');
      else
        throw new OtraException($message); // @codeCoverageIgnore
    }
  }

  /**
   * Sends a SQL query !
   *
   * @param string $query SQL query.
   * The query string should not end with a semicolon. Data inside the query should be properly escaped.
   *
   * @return resource Returns a resource on success, otherwise an exception is raised
   */
  public function query(string $query)
  {
    if (true === isset($_SESSION['bootstrap']))
      return null;

    if('dev' === $_SERVER[APP_ENV])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func(self::$_currentDBMS . '::query', $query, self::$_currentConn);
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
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::fetchAssoc', $params);
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
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::fetchArray', $params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array The results
   */
  public function getColumnMeta(...$params)
  {
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::getColumnMeta', $params);
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
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::fetchObject', $params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array The next result
   */
  public static function fetchRow(...$params)
  {
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::fetchRow', $params);
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
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::values', $params);
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
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::valuesOneCol', $params);
  }

  /**
   * Returns the only expected result.
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public function single(...$params){
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::single', $params);
  }

  /**
   * Close MySQL connection
   *
   * @param bool $instanceToClose
   *
   * @return bool Returns true on success or false on failure
   */
  private static function close($instanceToClose = true) : bool
  {
    if (isset(self::$instance) === true)
      return call_user_func_array(self::$_currentDBMS . '::close', [&$instanceToClose]);

    return false;
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
    if (true === isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$_currentDBMS . '::freeResult', $params);
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
    return call_user_func(self::$_currentDBMS . '::lastInsertedId', $sequenceName);
  }

 /**
 * @param string $string
 * @return mixed
 */
  public function quote(string $string)
  {
    return call_user_func(self::$_currentDBMS . '::quote', $string);
  }

  /**
   * @return bool
   */
  public function beginTransaction() : bool
  {
    return call_user_func(self::$_currentDBMS . '::beginTransaction');
  }

  /**
   * @return bool
   */
  public function inTransaction() : bool
  {
    return call_user_func(self::$_currentDBMS . '::inTransaction');
  }

  /**
   * @return bool
   */
  public function commit() : bool
  {
    return call_user_func(self::$_currentDBMS . '::commit');
  }

  /**
   * @return bool
   */
  public function rollBack() : bool
  {
    return call_user_func(self::$_currentDBMS . '::rollBack');
  }

  /**
   * @return array
   */
  public function errorInfo() : array
  {
    return call_user_func(self::$_currentDBMS . '::errorInfo');
  }
}
?>
