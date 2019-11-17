<?php
/**
 * Mysql management
 *
 * @author Lionel Péramo */

namespace lib\myLibs\bdd;
use lib\myLibs\OtraException;
use PDO, PDOStatement;

class Pdomysql
{
  private $conn;

  /**
   * Connects to PDO_MySql
   *
   * @param string $dsn      Dsn (Data Source Name) ex: mysql:dbname=testdb;host=127.0.0.1
   * @param string $username Username
   * @param string $password Password
   *
   * @return bool|PDO Returns a MySQL link identifier on success, or false on error
   * @throws OtraException
   */
  public static function connect($dsn = '127.0.0.1:3306', $username = 'root', $password = '')
  {
    try
    {
      $conn = new PDO($dsn, $username, $password);
    }catch(\PDOException $e)
    {
      throw new OtraException('Database connection failed: ' . $e->getMessage() . ' - Context : ' . $dsn . ' ' . $username . ' ' . $password);
    }

    return $conn;
  }

  /**
   * Sends a SQL query !
   * @param string $query SQL query.
   *                      The query string should not end with a semicolon. Data inside the query should be properly escaped.
   *
   * @return resource Returns a resource on success, otherwise an exception is raised
   *
   * @throws OtraException
   * @link http://php.net/manual/en/function.mysql-query.php
   */
  public static function query($query)
  {
    $result = Sql::$_CURRENT_CONN->query($query);
    // TODO use PDOStatement::debugDumpParams() ?
    if (false === $result)
    {
      $errorInfo = Sql::$_CURRENT_CONN->errorInfo();
      throw new OtraException('Invalid SQL request (error code : ' . $errorInfo[0] . ' ' . $errorInfo[1] . ') : <br><br>' . nl2br($query) . '<br><br>' . $errorInfo[2]);
    } else
      return $result;
  }

  /**
   * Returns the results
   *
   * @param PDOStatement $statement   The query statement
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-assoc.php
   */
  public static function fetchAssoc(PDOStatement $statement) { return $statement->fetch(PDO::FETCH_ASSOC); }

  /**
   * Fetch a result row as an associative array, a numeric array, or both
   *
   * @param PDOStatement $statement   The query statement
   * @param int          $fetch_style The type of array that is to be fetched. See the link for the available values. (PDO::FETCH_BOTH by default)
   *
   * @return array The next result
   * @link http://php.net/manual/en/pdostatement.fetch.php
   */
  public static function fetchArray(PDOStatement $statement, int $fetch_style = PDO::FETCH_BOTH) { return $statement->fetch($fetch_style); }

  /**
   * Returns the results
   *
   * @param PDOStatement $statement The query statement
   *
   * @return array The next result
   * @link http://php.net/manual/en/pdostatement.fetch.php
   */
  public static function fetchRow(PDOStatement $statement) { return $statement->fetch(PDO::FETCH_NUM); }

  /**
   * Returns the results as an object (simplified version of the existing one)
   *
   * @param PDOStatement $statement The query statement
   *
   * @return array The next result
   * @link http://php.net/manual/en/pdostatement.fetch.php
   */
  public static function fetchObject(PDOStatement $statement) { return $statement->fetch(PDO::FETCH_OBJ); }

  /**
   * Returns all the results in an associative array
   *
   * @param PDOStatement $statement The query statement
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function values($statement)
  {
    if (0 == $statement->rowCount())
      return [];

    $results = [];

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) { $results[] = $row; }

    return $results;
  }

  /**
   * Returns all the results in an associative array
   *
   * @param PDOStatement $statement The query statement
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function valuesOneCol(PDOStatement $statement)
  {
    if (0 == $statement->rowCount())
      return false;

    $row = $statement->fetch(PDO::FETCH_ASSOC);
    $results = [];
    $results[] = $row[($key = key($row))];

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) { $results[] = $row[$key]; }

    return $results;
  }

  /**
   * Returns the only expected result.
   *
   * @param PDOStatement $statement The query statement
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public static function single(PDOStatement $statement)
  {
    if (0 == $statement->rowCount())
      return false;

    return current($statement->fetch(PDO::FETCH_ASSOC));
  }

  /**
   * Free result memory
   *
   * @param PDOStatement $statement The query statement
   *
   * @return bool Returns true on success or false on failure.
   * @link http://php.net/manual/en/function.mysql-free-result.php
   */
  public static function freeResult(PDOStatement $statement) { return $statement->closeCursor(); }

  /**
   * Returns metadata for a column in a result set
   *
   * @param PDOStatement $statement The query statement
   * @param int $column
   *
   * @return array The results
   */
  public static function getColumnMeta(PDOStatement $statement, int $column) { return $statement->getColumnMeta($column); }

  /**
   * Closes connection.
   *
   * @param bool|SQL $instanceToClose
   *
   * @return bool Returns true on success or false on failure
   */
  public static function close($instanceToClose = true)
  {
    if ($instanceToClose)
      Sql::$_CURRENT_CONN = null;
    else
      $instanceToClose = null;

    return true;
  }

  /**
   * Get the ID generated in the last query
   *
   * @param string $sequenceName
   *
   * @return int The ID generated for an AUTO_INCREMENT column by the previous query on success, 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established.
   * @link http://php.net/manual/fr/function.mysql-insert-id.php
   */
  public static function lastInsertedId(string $sequenceName = null) : int { return Sql::$_CURRENT_CONN->lastInsertId($sequenceName); }

  /**
   * @param string $string
   *
   * @return string
   */
  public static function quote(string $string)
  {
    return trim(Sql::$_CURRENT_CONN->quote($string), '\'');
  }

  /**
 * @return bool
 */
  public static function beginTransaction() : bool
  {
    return Sql::$_CURRENT_CONN->beginTransaction();
  }

  /**
   * @return bool
   */
  public static function inTransaction() : bool
  {
    return Sql::$_CURRENT_CONN->inTransaction();
  }

  /**
   * @return bool
   */
  public static function commit() : bool
  {
    return Sql::$_CURRENT_CONN->commit();
  }

  /**
   * @return bool
   */
  public static function rollBack() : bool
  {
    return Sql::$_CURRENT_CONN->rollBack();
  }

  /**
   * @return array
   */
  public static function errorInfo() : array
  {
    return Sql::$_CURRENT_CONN->errorInfo();
  }
}
?>
