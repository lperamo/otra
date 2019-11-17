<?php
/**
 * Mysql management
 *
 * @author Lionel Péramo */

namespace lib\myLibs\bdd;
use lib\myLibs\OtraException;

class Mysql
{
  static private $db;

  /**
   * Connects to Mysql
   *
   * @param string $server   Mysql server
   * @param string $username Username
   * @param string $password Password
   *
   * @return bool|resource Returns a MySQL link identifier on success, or false on error
   * @link http://php.net/manual/en/function.mysql-connect.php
   */
  public static function connect($server = 'localhost:3306', $username = 'root', $password = '') {
    return \mysql_connect($server, $username, $password);
  }

  /**
   * Sends a SQL query !
   *
   * @param string $query SQL query.
   *                      The query string should not end with a semicolon. Data inside the query should be properly escaped.
   * @param        $link_identifier
   *
   * @return resource Returns a resource on success, otherwise an exception is raised
   *
   * @throws OtraException
   * @link http://php.net/manual/en/function.mysql-query.php
   */
  public static function query(string $query, $link_identifier)
  {
    if (!$result = mysql_query($query, $link_identifier))
      throw new OtraException('Invalid SQL request : <br><br>' . nl2br($query) . '<br><br>' . mysql_error());
    else
      return $result;
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result in an associative array
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-assoc.php
   */
  public static function fetchAssoc($result) { return mysql_fetch_assoc($result); }

  /**
   * Fetch a result row as an associative array, a numeric array, or both
   *
   * @param resource $result      The query result
   * @param int      $result_type The type of array that is to be fetched. It's a constant and can take the
   * following values: MYSQL_ASSOC, MYSQL_NUM, and MYSQL_BOTH.
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-array.php
   */
  public static function fetchArray($result, $result_type) {
    return mysql_fetch_array($result, $result_type);
  }

  /**
   * Returns the results
   *
   * @param resource $result The query result
   *
   * @return array The next result
   * @link http://php.net/manual/en/function.mysql-fetch-row.php
   */
  public static function fetchRow($result) { return mysql_fetch_row($result); }

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
   * @link http://php.net/manual/en/function.mysql-fetch-object.php
   */
  public static function fetchObject($result, $class_name = null, array $params = []) {
    return mysql_fetch_object(func_get_args());
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
    if (0 == mysql_num_rows($result))
      return [];

    while ($row = mysql_fetch_assoc($result)) { $results[] = $row; }

    return $results;
  }

  /**
   * Returns all the results in an associative array
   *
   * @param resource $result The query result
   *
   * @return bool|array The results. Returns false if there are no results.
   */
  public static function valuesOneCol($result)
  {
    if (0 == mysql_num_rows($result))
      return false;

    $row = mysql_fetch_assoc($result);
    $results[] = $row[($key = key($row))];

    while ($row = mysql_fetch_assoc($result)) { $results[] = $row[$key]; }

    return $results;
  }

  /**
   * Returns the only expected result.
   *
   * @param resource $result The query result
   *
   * @return bool|mixed The result. Returns false if there are no result.
   */
  public static function single($result)
  {
    if (0 == mysql_num_rows($result))
      return false;

    return current(mysql_fetch_assoc($result));
  }

  /**
   * Free result memory
   *
   * @param resource $result
   *
   * @return bool Returns true on success or false on failure.
   * @link http://php.net/manual/en/function.mysql-free-result.php
   */
  public static function freeResult($result) { return mysql_free_result($result); }

    /**
   * Returns the results
   *
   * @param resource $result The query result in an object
   *
   * @return array The results
   */
  public static function getColumnMeta($result) { return mysql_fetch_field($result); }

  /**
   * Close MySQL connection
   *
   * @param null $link_identifier
   *
   * @return bool Returns true on success or false on failure
   */
  public static function close($link_identifier = null) { return \mysql_close($link_identifier); }

  /**
   * Get the ID generated in the last query
   *
   * @param $link_identifier
   *
   * @return int The ID generated for an AUTO_INCREMENT column by the previous query on success, 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established.
   *
   * @link http://php.net/manual/fr/function.mysql-insert-id.php
   */
  public static function lastInsertedId($link_identifier) { return mysql_insert_id($link_identifier); }

  public static function quote(&$string)
  {
    return mysql_real_escape_string($string);
  }
}
?>
