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
use const otra\cache\php\{APP_ENV, CORE_PATH, DEV, PROD};

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

  public static ?Sql $instance = null;
  private const int QUERY = 0;

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
   * @param bool    $haveDatabase Generic operation? Can be no, to CREATE or DROP a database, for example, no database name
   *                           needed in this case.
   *
   * @return bool|Sql
   *
   * @throws OtraException
   *
   * @internal param string $dbms       Kind of dbms
   * @internal param string $connection Connection used (see AllConfig files)
   * @internal param bool   $selectDb   Do we have to select the default database? (omits it for PDO connection)
   */
  public static function getDb(?string $connection = null, bool $haveDatabase = true) : bool|Sql
  {
    /* If the connection is:
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
      'host' => $hostName,
      'login' => $login,
      'password' => $password
    ] = AllConfig::$dbConnections[$connection];

    $driver = ucfirst(strtolower($driver));

    // Is this driver available? 
    if (in_array($driver, self::$dbmsCollection))
    {
      if (null === self::$_activeConn[$currentConnection])
      {
        self::$currentDBMS = __NAMESPACE__ . '\\' . ucfirst(strtolower($driver));
        self::$_activeConn[$currentConnection]['instance'] = new Sql();
        require CORE_PATH . 'bdd/' . $driver . '.php';
      }

      [
        'charset' => $charset,
        'db' => $database,
        'dsnDriver' => $dsnDriver,
        'port' => $databasePort,
        'host' => $hostName,
        'login' => $login,
        'password' => $password
      ] = AllConfig::$dbConnections[$connection ?: AllConfig::$defaultConn] +
      [
        'dsnDriver' => 'mysql',
        'charset' => 'utf8mb4'
      ];

      $activeConn = &self::$_activeConn[$currentConnection];
      $activeConn['db'] = $database;
      self::$instance = $activeConn['instance'];

      try
      {
        // Putting the charset in the DNS here IS SUPER IMPORTANT for security!
        // https://stackoverflow.com/a/12202218/1818095
        $activeConn['conn'] = $activeConn['instance']->connect(
          $dsnDriver . ($haveDatabase  ? ':dbname=' . $database . ';' : ':') .
            'host=' . ('' == $databasePort ? $hostName : $hostName . ':' . $databasePort) .
            ';charset=' . $charset,
          $login,
          $password
        );

        self::$currentConn = $activeConn['conn'];
        self::$currentConnectionName = $currentConnection;
      } catch(Exception $exception)
      {
        throw new OtraException($_SERVER[APP_ENV] === PROD
          ? 'Cannot connect to the database'
          : $exception->getMessage()
        );
      }
    } else
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
  public static function connect(mixed ...$params)
  {
    return ([self::$currentDBMS, 'connect'])(...$params);
  }

  /**
   * Connects to a database
   *
   * @param mixed $params See the driver for more info.
   *
   * @return bool True if successful
   */
  public function selectDb(mixed ...$params) : bool
  {
    $return = call_user_func_array(self::$currentDBMS . '::selectDb', $params);
    // @codeCoverageIgnoreStart
    $this->query('SET NAMES utf8mb4');

    return $return;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Sends an SQL query !
   *
   * @param mixed $params See the driver for more info.
   *
   * @return mixed Returns a resource on success, otherwise an exception is raised
   */
  public function query(mixed ...$params) : mixed
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    if(DEV === $_SERVER[APP_ENV])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        $trace[1]['file'] ?? $trace[0]['file'],
        $trace[1]['line'] ?? $trace[0]['line'],
        str_replace('"', '\\"', $params[self::QUERY]),
        'sql');
    }

    return (self::$currentDBMS . '::query')(...$params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array|false|null The results
   */
  public function fetchAssoc(mixed ...$params) : array|false|null
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::fetchAssoc')(...$params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return array|false|null The results
   */
  public function fetchAllAssoc(mixed ...$params) : array|false|null
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::fetchAllAssoc')(...$params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The results
   */
  public function fetchAllByPair(mixed ...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::fetchAllByPair')(...$params);
  }

    /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The results
   */
  public function fetchArray(mixed ...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::fetchArray')(...$params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The results
   */
  public function getColumnMeta(mixed ...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::getColumnMeta')(...$params);
  }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?object The next result
   */
  public function fetchObject(mixed ...$params) : ?object
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::fetchObject')(...$params);
  }

  /**
   * Returns the results
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?array The next result
   */
  public static function fetchRow(mixed ...$params) : ?array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::fetchRow')(...$params);
  }

  /**
   * Returns all the results in an associative array
   *
   * @param mixed $params See the driver for more info.
   *
   * @return null|bool|array The results. Returns false if there are no results.
   */
  public function values(mixed ...$params) : null|bool|array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::values')(...$params);
  }

  /**
   * Returns all the results in an associative array (use it when the result set contains only one column)
   *
   * @param mixed $params See the driver for more info.
   *
   * @return null|bool|array The results. Returns false if there are no results.
   */
  public function valuesOneCol(mixed ...$params) : null|bool|array
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::valuesOneCol')(...$params);
  }

  /**
   * Returns the only expected result.
   *
   * @param mixed $params See the driver for more info.
   *
   * @return mixed The result. Returns false if there are no result.
   */
  public function single(mixed ...$params) : mixed
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::single')(...$params);
  }

  /**
   * Close MySQL connection
   *
   *
   * @return bool Returns true on success or false on failure
   */
  private static function close(?bool $instanceToClose = true) : bool
  {
    if (isset(self::$instance))
      return (self::$currentDBMS . '::close')(...[&$instanceToClose]);

    return false;
  }

  /**
   * Free result memory
   *
   * @param mixed $params See the driver for more info.
   *
   * @return ?bool Returns true on success or false on failure.
   */
  public function freeResult(mixed ...$params) : ?bool
  {
    if (isset($_SESSION['bootstrap']))
      return null;

    return (self::$currentDBMS . '::freeResult')(...$params);
  }

  /**
   * Return the last inserted id
   *
   * @param string|null $sequenceName
   *
   * @return int|string The last inserted id (can be a string for certain DBMSes?)
   */
  public function lastInsertedId(?string $sequenceName = null) : int|string
  {
    $lastId = (self::$currentDBMS . '::lastInsertedId')($sequenceName);
    return is_numeric($lastId) ? (int)($lastId) : $lastId;
  }

  /**
   * @return string
   */
  public function quote(string $string) : string
  {
    return (self::$currentDBMS . '::quote')($string);
  }

  /**
   * @return bool
   */
  public function beginTransaction() : bool
  {
    return (self::$currentDBMS . '::beginTransaction')();
  }

  /**
   * @return bool
   */
  public function inTransaction() : bool
  {
    return (self::$currentDBMS . '::inTransaction')();
  }

  /**
   * @return bool
   */
  public function commit() : bool
  {
    // This condition is useful because ... quoting from php.net
    // Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language
    // (DDL) statement such as DROP TABLE or CREATE TABLE is issued within a transaction. The implicit COMMIT will
    // prevent you from rolling back any other changes within the transaction boundary.
    if (Sql::$instance->inTransaction())
      return (self::$currentDBMS . '::commit')();

    return true;
  }

  /**
   * @return bool
   */
  public function rollBack() : bool
  {
    return (self::$currentDBMS . '::rollBack')();
  }

  /**
   * @return array
   */
  public function errorInfo() : array
  {
    return (self::$currentDBMS . '::errorInfo')();
  }

  /**
   *
   * @return PDOStatement|false
   */
  public function prepare(string $query, array $options = []): PDOStatement|false
  {
    if (DEV === $_SERVER[APP_ENV])
    {
      $trace = debug_backtrace();

      Logger::logSQLTo(
        $trace[1]['file'] ?? $trace[0]['file'],
        $trace[1]['line'] ?? $trace[0]['line'],
        str_replace('"', '\\"', $query),
        'sql');
    }

    return (self::$currentDBMS . '::prepare')($query, $options);
  }

  /**
   * Returns the row count
   *
   * @param mixed $params See the driver for more info.
   *
   * @return int The row count
   */
  public static function rowCount(mixed ...$params) : int
  {
    return (self::$currentDBMS . '::rowCount')(...$params);
  }

  /**
   * Configures a PDO attribute
   *
   *
   * @return bool
   */
  public static function setAttribute(mixed ...$params): bool
  {
    return (self::$currentDBMS . '::setAttribute')(...$params);
  }

  /**
   * Gets a database connection attribute
   *
   *
   * @return bool|int|string|array|null
   */
  public static function getAttribute(int $attribute): bool|int|string|array|null
  {
    return (self::$currentDBMS . '::getAttribute')($attribute);
  }
}
