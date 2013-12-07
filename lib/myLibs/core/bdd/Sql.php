<?php

namespace lib\myLibs\core\bdd;

use lib\myLibs\core\Lionel_Exception,
  lib\myLibs\core\Session,
  lib\myLibs\core\bdd\Mysql,
  config\All_Config;

/**
 * Main sql management
 *
 * @author Lionel Péramo
 */
class Sql
{
  private static $_instance,
    $_sgbds = array('Mysql'),
    $_chosenSgbd,
    $_db,
    $_link_identifier;

  public function __construct($sgbd) { die; self::$_chosenSgbd = __NAMESPACE__ . '\\' . $sgbd; }

  /** Destructor that closes the connection */
  public function __destruct() { self::close(); }

  /**
   * Retrieves an instance of this class or creates it if it not exists yet
   *
   * @param string $sgbd Kind of sgbd
   */
  public static function getDB($sgbd)
  {
    if(in_array($sgbd, self::$_sgbds))
    {
      if (null == self::$_instance)
      {

        self::$_instance = new Sql($sgbd);
        dump(__NAMESPACE__ . '\\' . $sgbd);die;
        require($sgbd . '.php');
      }

      extract(All_Config::$dbConnections[Session::get('db')]);
      self::$_db = $db;
      $server = ('' == $port) ? $host : $host . ':' . $port;
      self::$_link_identifier = self::connect($server, $login, $password);
      //var_dump(self::$_link_identifier);die;
      return self::$_instance;
    }else
      throw new Lionel_Exception('This SGBD doesn\'t exist...yet !', 'E_CORE_ERROR');
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
    return call_user_func(self::$_chosenSgbd . '::selectDb', self::$_db, self::$_link_identifier);
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
  public static function fetchObject($result, $class_name = null, array $params = array() )
  {
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
    return call_user_func(self::$_chosenSgbd . '::values', $result);
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
    return call_user_func(self::$_chosenSgbd . '::freeResult');
  }

  /**
   * Return the last inserted id
   *
   * @return int The last inserted id
   */
  public static function lastInsertedId()
  {
    return call_user_func(self::$_chosenSgbd . '::lastInsertId');
  }
}
?>
