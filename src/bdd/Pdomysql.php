<?php
declare(strict_types=1);
/**
 * Mysql management
 *
 * @author Lionel Péramo */

namespace otra\bdd;
use otra\OtraException;
use PDO, PDOStatement, PDOException;
use const otra\cache\php\PROD;

/**
 * @package otra\bdd
 */
abstract class Pdomysql
{
  private readonly PDO $conn;
  private const string DEFAULT_MOTOR = 'InnoDB';

  /**
   * Connects to PDO_MySql
   * Putting the charset here IS SUPER IMPORTANT! https://stackoverflow.com/a/12202218/1818095
   *
   * @param string $dsn      Dsn (Data Source Name) ex: mysql:dbname=testdb;host=127.0.0.1;charset=utf8mb4
   * @param string $username Username
   * @param string $password Password
   *
   * @return PDO Returns a MySQL link identifier on success, or false on error
   * @throws OtraException
   */
  public static function connect(string $dsn = '127.0.0.1:3306;charset=utf8mb4', string $username = 'root', string $password = '')
  {
    try
    {
      $connection = new PDO($dsn, $username, $password);
    }catch(PDOException $exception)
    {
      throw new OtraException(
        'Database connection failed: ' . $exception->getMessage() .
        ($_SERVER['APP_ENV'] === PROD
          ? ''
          : ' - Context : ' . $dsn . ' ' . $username . ' ' . $password
        )
      );
    }

    return $connection;
  }

  /**
   * Sends an SQL query !
   *
   * @link http://php.net/manual/en/function.mysql-query.php
   *
   * @param string $query   SQL query.
   *                        The query string should not end with a semicolon. Data inside the query should be properly
   *                        escaped.
   *
   * @param ?int $fetchMode The default fetch mode for the returned PDOStatement. It must be one of the PDO::FETCH_*
   *                        constants.
   *                        If this argument is passed to the function, the remaining arguments will be treated as
   *                        though PDOStatement::setFetchMode() was called on the resultant statement object. The
   *                        subsequent arguments vary depending on the selected fetch mode.
   *
   * @return PDOStatement Returns a resource on success, otherwise an exception is raised
   *
   * @throws OtraException
   */
  public static function query(string $query, ?int $fetchMode = null) : PDOStatement
  {
    $result = Sql::$currentConn->query($query, $fetchMode);

    if (false === $result)
    {
      $errorInfo = Sql::$currentConn->errorInfo();
      throw new OtraException('Invalid SQL request (error code : ' . $errorInfo[0] . ' ' . $errorInfo[1] .
        ') : <br><br>' . nl2br($query) . '<br><br>' . $errorInfo[2]);
    } else
      return $result;
  }

  /**
   * Returns the results with FETCH_ASSOC mask.
   * @link https://www.php.net/manual/fr/pdostatement.fetch.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return mixed The next result
   */
  public static function fetchAssoc(PDOStatement $statement) : mixed
  {
    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Returns the results with FETCH_ASSOC mask.
   * @link https://www.php.net/manual/fr/pdostatement.fetchall.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return array|false The next result
   */
  public static function fetchAllAssoc(PDOStatement $statement): array|false
  {
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Returns the results with FETCH_KEY_PAIR mask.
   *
   * @link https://www.php.net/manual/fr/pdostatement.fetchall.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return array|bool The next result
   */
  public static function fetchAllByPair(PDOStatement $statement): array|bool
  {
    return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
  }

  /**
   * Fetch a result row as an associative array, a numeric array, or both
   * @link http://php.net/manual/en/pdostatement.fetch.php
   *
   * @param PDOStatement $statement   The query statement
   * @param int          $fetch_style The type of array that is to be fetched. See the link for the available values. (PDO::FETCH_BOTH by default)
   *
   * @return false|array The next result
   */
  public static function fetchArray(PDOStatement $statement, int $fetch_style = PDO::FETCH_BOTH)
  {
    return $statement->fetch($fetch_style);
  }

  /**
   * Returns the results
   * @link http://php.net/manual/en/pdostatement.fetch.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return false|array The next result
   */
  public static function fetchRow(PDOStatement $statement)
  {
    return $statement->fetch(PDO::FETCH_NUM);
  }

  /**
   * Returns the results as an object (simplified version of the existing one)
   * @link http://php.net/manual/en/pdostatement.fetch.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return false|object The next result
   */
  public static function fetchObject(PDOStatement $statement)
  {
    return $statement->fetch(PDO::FETCH_OBJ);
  }

  /**
   * Returns all the results in an associative array
   *
   * @param PDOStatement $statement The query statement
   *
   * @return array The results. Returns false if there are no results.
   */
  public static function values(PDOStatement $statement) : array
  {
    if (0 === $statement->rowCount())
      return [];

    $results = [];

    while ($sqlRow = $statement->fetch(PDO::FETCH_ASSOC)) { $results[] = $sqlRow; }

    return $results;
  }

  /**
   * Returns all the results in an associative array
   *
   * @param PDOStatement $statement The query statement
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function valuesOneCol(PDOStatement $statement) : bool|array
  {
    if (0 === $statement->rowCount())
      return false;

    $sqlRow = $statement->fetch(PDO::FETCH_ASSOC);
    $results = [];
    $results[] = $sqlRow[($rowKey = key($sqlRow))];

    while ($sqlRow = $statement->fetch(PDO::FETCH_ASSOC)) { $results[] = $sqlRow[$rowKey]; }

    return $results;
  }

  /**
   * Returns the only expected result.
   *
   * @param PDOStatement $statement The query statement
   *
   * @throws OtraException
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public static function single(PDOStatement $statement)
  {
    if (0 == $statement->rowCount())
      return false;

    $result = $statement->fetch(PDO::FETCH_ASSOC);

    if ($result)
      return current($result);
    else
    {
      ob_start();
      $statement->debugDumpParams();
      throw new OtraException('Cannot retrieve the data : ' . ob_get_clean());
    }
  }

  /**
   * Free result memory
   * @link https://www.php.net/manual/en/pdostatement.closeCursor
   *
   * @param PDOStatement $statement The query statement
   *
   * @return bool Returns true on success or false on failure.
   */
  public static function freeResult(PDOStatement $statement) : bool { return $statement->closeCursor(); }

  /**
   * Returns metadata for a column in a result set
   * @link https://www.php.net/manual/en/pdostatement.getcolumnmeta.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return false|array The results
   */
  public static function getColumnMeta(PDOStatement $statement, int $columnName)
  {
    return $statement->getColumnMeta($columnName);
  }

  /**
   * Closes connection.
   *
   *
   * @return bool Returns true on success or false on failure
   */
  public static function close(bool|Sql &$instanceToClose = true) : bool
  {
    if ($instanceToClose === true)
      Sql::$currentConn = null;
    else
      $instanceToClose = null;

    return true;
  }

  /**
   * Get the ID generated in the last query
   *
   * @param string|null $sequenceName
   *
   * @return string The ID generated for an AUTO_INCREMENT column by the previous query on success, 0 if the previous
   *                query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established.
   */
  public static function lastInsertedId(?string $sequenceName = null) : string
  {
    return Sql::$currentConn->lastInsertId($sequenceName);
  }

  /**
   * @link https://www.php.net/manual/en/pdo.quote.php
   *
   *
   * @return string
   */
  public static function quote(string $string)
  {
    return trim(Sql::$currentConn->quote($string), '\'');
  }

  /**
   * @link https://www.php.net/manual/en/pdo.begintransaction.php
   *
   * @return bool
   */
  public static function beginTransaction() : bool
  {
    return Sql::$currentConn->beginTransaction();
  }

  /**
   * @link https://www.php.net/manual/en/pdo.intransaction.php
   *
   * @return bool
   */
  public static function inTransaction() : bool
  {
    return Sql::$currentConn->inTransaction();
  }

  /**
   * @link https://www.php.net/manual/en/pdo.commit
   *
   * @return bool
   */
  public static function commit() : bool
  {
    return Sql::$currentConn->commit();
  }

  /**
   * @link https://www.php.net/manual/en/pdo.rollback
   *
   * @return bool
   */
  public static function rollBack() : bool
  {
    return Sql::$currentConn->rollBack();
  }

  /**
   * @link https://www.php.net/manual/en/pdo.errorInfo
   *
   * @return array
   */
  public static function errorInfo() : array
  {
    return Sql::$currentConn->errorInfo();
  }

  /**
   * @link https://www.php.net/manual/en/pdo.prepare.php
   *
   *
   * @return PDOStatement|false
   */
  public static function prepare(string $query, array $options = []): PDOStatement|false
  {
    return Sql::$currentConn->prepare($query, $options);
  }

  /**
   * Returns the row count
   * @link http://php.net/manual/en/pdostatement.rowcount.php
   *
   * @param PDOStatement $statement The query statement
   *
   * @return int The row count
   */
  public static function rowCount(PDOStatement $statement): int
  {
    return $statement->rowCount();
  }

  /**
   * Configures a PDO attribute
   * @link https://www.php.net/manual/fr/pdo.setattribute.php
   *
   *
   * @return bool
   */
  public static function setAttribute(int $attribute, mixed $value): bool
  {
    return Sql::$currentConn->setAttribute($attribute, $value);
  }

  /**
   * Gets a database connection attribute
   *
   * @link https://www.php.net/manual/fr/pdo.getattribute.php
   *
   *
   * @return bool|int|string|array|null
   */
  public static function getAttribute(int $attribute): bool|int|string|array|null
  {
    return Sql::$currentConn->getAttribute($attribute);
  }
}
