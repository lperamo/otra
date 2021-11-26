<?php
/** Main sql management
 *
 * @author Lionel Péramo */
declare(strict_types=1);

namespace otra\bdd;

use Exception;
use otra\OtraException;
use otra\cache\php\Logger;
use otra\config\AllConfig;
use PDO;
use PDOStatement;
use const otra\cache\php\{APP_ENV,CORE_PATH,DEV};

/**
 * @package otra\bdd
 */
class Sql
{
  /**
   * @type array  $dbmsCollection       Available dbmsCollection
   * @type array  $_activeConn
   */
  private static array
    $dbmsCollection = ['Pdomysql'],
    $_activeConn = [];

  public static ?PDO $currentConn = null;
  public static string
    $currentDBMS = '',
    $currentConnectionName;

  public static ?Sql $instance;

  private function __construct()
  {
  }

  /** Destructor that closes the connection */
  public function __destruct() {
    self::close();
  }

  /**
   * Retrieves an instance of this class or creates it if it not exists yet.
   *
   * @param ?string $connection
   * @param bool    $haveDatabase Generic operation ? Can be no, to CREATE or DROP a database for example, no database name
   *                           needed in this case.
   *
   * @return bool|Sql
   *
   * @throws OtraException
   *
   * @internal param string $dbms       Kind of dbms
   * @internal param string $connection Connection used (see AllConfig files)
   * @internal param bool   $selectDb   Do we have to select the default database ? (omits it for PDO connection)
   */
  public static function getDb(?string $connection = null, bool $haveDatabase = true) : bool|Sql
  {
    /* If the connection is :
     * - specified => active we use it, otherwise => added if exists
     * - not specified => we use default connection, and we add it
     */
    if (null !== $connection)
    {
      if (isset(self::$_activeConn[$connection]))
      {
        $currentConnection = $connection;
      } elseif (isset(AllConfig::$dbConnections[$connection]))
      {
        $currentConnection = $connection;
        self::$_activeConn[$connection] = null;
      } else
        throw new OtraException(
          'There is no \'' . $connection . '\' configuration available in the AllConfig file !'
        );
    } else
    {
      if (AllConfig::$defaultConn === '')
        throw new OtraException(
          'There is no default connection in your configuration ! Check your configuration.',
          E_CORE_ERROR
        );

      $connection = AllConfig::$defaultConn;

      // If it's not already added, we add it
      if (!isset(self::$_activeConn[$connection]))
        self::$_activeConn[$connection] = null;

      $currentConnection = $connection;
    }

    [
      'db' => $database,
      'driver' => $driver,
      'port' => $port,
      'host' => $host,
      'login' => $login,
      'password' => $password
    ] = AllConfig::$dbConnections[$connection];

    $driver = ucfirst(strtolower($driver));

    // Is this driver available ?
    if (in_array($driver, self::$dbmsCollection))
    {
      if (null === self::$_activeConn[$currentConnection])
      {
        self::$currentDBMS = __NAMESPACE__ . '\\' . ucfirst(strtolower($driver));
        self::$_activeConn[$currentConnection]['instance'] = new Sql();
        require CORE_PATH . 'bdd/' . $driver . '.php';
      }

      [
        'db' => $database,
        'port' => $port,
        'host' => $host,
        'login' => $login,
        'password' => $password
      ] = AllConfig::$dbConnections[$connection ?: AllConfig::$defaultConn];

      $activeConn = &self::$_activeConn[$currentConnection];
      $activeConn['db'] = $database;
      self::$instance = $activeConn['instance'];

      try
      {
        $activeConn['conn'] = $activeConn['instance']->connect(
          strtolower(substr($driver, 3))
            . ($haveDatabase  ? ':dbname=' . $database . ';' : ':')
            . 'host=' . ('' == $port ? $host : $host . ':' . $port),
          $login,
          $password
        );

        self::$currentConn = $activeConn['conn'];
        self::$currentConnectionName = $currentConnection;
      } catch(Exception $exception)
      {
        throw new OtraException($exception->getMessage());
      }
    }else
      throw new OtraException(
        'This DBMS \'' . $driver . '\' is not available...yet ! Available DBMS are : ' .
        implode(', ', self::$dbmsCollection),
        E_CORE_ERROR
      );

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
    return call_user_func_array([self::$currentDBMS, 'connect'], $params);
  }

  /**
   * Connects to a database
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool True if successful
   */
  public function selectDb(...$params) : bool
  {
    $return = call_user_func_array(self::$currentDBMS . '::selectDb', $params);
    // @codeCoverageIgnoreStart
    $this->query('SET NAMES UTF8');

    return $return;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Sends an SQL query !
   *
   * @param string $query SQL query.
   * The query string should not end with a semicolon. Data inside the query should be properly escaped.
   *
   * @return null|resource Returns a resource on success, otherwise an exception is raised
   */
  public function query(string $query)
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    if(DEV === $_SERVER[APP_ENV])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func(self::$currentDBMS . '::query', $query, self::$currentConn);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The results
   */
  public function fetchAssoc(...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::fetchAssoc', $params);
  }

    /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The results
   */
  public function fetchArray(...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::fetchArray', $params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The results
   */
  public function getColumnMeta(...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::getColumnMeta', $params);
  }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?object The next result
   */
  public function fetchObject(...$params) : ?object
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::fetchObject', $params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The next result
   */
  public static function fetchRow(...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::fetchRow', $params);
  }

  /**
   * Returns all the results in an associative array
   *
   * @param mixed $params See the driver for more info.
   *
   * @return null|bool|array The results. Returns false if there are no results.
   */
  public function values(...$params) : null|bool|array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::values', $params);
  }

  /**
   * Returns all the results in an associative array (use it when the result set contains only one column)
   *
   * @param mixed $params See the driver for more info.
   *
   * @return null|bool|array The results. Returns false if there are no results.
   */
  public function valuesOneCol(...$params) : null|bool|array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::valuesOneCol', $params);
  }

  /**
   * Returns the only expected result.
   *
   * @param mixed $params See the driver for more info.
   *
   * @return mixed The result. Returns false if there are no result.
   */
  public function single(...$params) : mixed
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::single', $params);
  }

  /**
   * Close MySQL connection
   *
   * @param ?bool $instanceToClose
   *
   * @return bool Returns true on success or false on failure
   */
  private static function close(?bool $instanceToClose = true) : bool
  {
    if (isset(self::$instance))
      return call_user_func_array(self::$currentDBMS . '::close', [&$instanceToClose]);

    return false;
  }

  /**
   * Free result memory
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?bool Returns true on success or false on failure.
   */
  public function freeResult(...$params) : ?bool
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return call_user_func_array(self::$currentDBMS . '::freeResult', $params);
  }

  /**
   * Return the last inserted id
   *
   * @param string|null $sequenceName
   *
   * @return string The last inserted id
   */
  public function lastInsertedId(string $sequenceName = null) : string
  {
    return call_user_func(self::$currentDBMS . '::lastInsertedId', $sequenceName);
  }

  /**
   * @param string $string
   *
   * @return string
   */
  public function quote(string $string) : string
  {
    return call_user_func(self::$currentDBMS . '::quote', $string);
  }

  /**
   * @return bool
   */
  public function beginTransaction() : bool
  {
    return call_user_func(self::$currentDBMS . '::beginTransaction');
  }

  /**
   * @return bool
   */
  public function inTransaction() : bool
  {
    return call_user_func(self::$currentDBMS . '::inTransaction');
  }

  /**
   * @return bool
   */
  public function commit() : bool
  {
    // this condition is useful because ... quoting from php.net
    // Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language
    // (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction. The implicit COMMIT will
    // prevent you from rolling back any other changes within the transaction boundary.
    if (Sql::$instance->inTransaction())
      return call_user_func(self::$currentDBMS . '::commit');

    return true;
  }

  /**
   * @return bool
   */
  public function rollBack() : bool
  {
    return call_user_func(self::$currentDBMS . '::rollBack');
  }

  /**
   * @return array
   */
  public function errorInfo() : array
  {
    return call_user_func(self::$currentDBMS . '::errorInfo');
  }

  /**
   * @param string $query
   * @param array  $options
   *
   * @return PDOStatement|false
   */
  public function prepare(string $query, array $options = []): PDOStatement|false
  {
    if (DEV === $_SERVER[APP_ENV])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        (isset($trace[1]['file'])) ? $trace[1]['file'] : $trace[0]['file'],
        (isset($trace[1]['line'])) ? $trace[1]['line'] : $trace[0]['line'],
        $query,
        'sql');
    }

    return call_user_func(self::$currentDBMS . '::prepare', $query, $options);
  }
}
