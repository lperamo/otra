<?php
/** Description of Session
 *
 * @author Lionel Péramo */
namespace lib\myLibs;

class Session
{
  private static string $id;

  public static function init() { self::$id = \sha1(\time()); }

  /** Puts a value associated with a key into the session
   *
   * @param string $key
   * @param mixed  $value
   */
  public static function set(string $key, $value) { $_SESSION[sha1(self::$id .$key)] = $value; }

  /** Puts all the value associated with the keys of the array into the session
   *
   * @param array $array
   */
  public static function sets(array $array) { foreach($array as $key => $value) $_SESSION[sha1(self::$id .$key)] = $value; }

  /** Retrieves a value from the session via its key
   *
   * @param string $key
   *
   * @return mixed
   */
  public static function get(string $key) { return $_SESSION[sha1(self::$id . $key)]; }
}
?>
