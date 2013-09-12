<?php

namespace lib\myLibs\core;

/**
 * Description of Session
 *
 * @author Lionel Péramo
 */
class Session
{
//  private static $session;
  private static $id;
  public static $reg;

  public static function init() { self::$id = \sha1(\time()); }

  /**
   * Puts a value associated with a key into the session
   *
   * @param string $key   The key to associate with the value
   * @param mixed  $value The value to put into the session
   */
  public static function set($key, $value) { $_SESSION[sha1(self::$id .$key)] = $value; }

  /**
   * Retrieves a value from the session via its key
   *
   * @param string $key
   *
   * @return mixed The stored value wanted
   */
  public static function get($key) { return $_SESSION[sha1(self::$id . $key)]; }
}
?>
