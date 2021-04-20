<?php
declare(strict_types=1);
/** Description of Session
 *
 * @author Lionel Péramo */
namespace otra;

use JetBrains\PhpStorm\Pure;

/**
 * @package otra
 */
abstract class Session
{
  private static string $identifier;

  public static function init() : void { self::$identifier = sha1((string)time()); }

  /** Puts a value associated with a key into the session
   *
   * @param string $key
   * @param mixed  $value
   */
  public static function set(string $key, mixed $value) : void
  {
    $_SESSION[sha1(self::$identifier . $key)] = $value;
  }

  /** Puts all the value associated with the keys of the array into the session
   *
   * @param array $array
   */
  public static function sets(array $array) : void
  {
    foreach($array as $sessionKey => $value)
      $_SESSION[sha1(self::$identifier . $sessionKey)] = $value;
  }

  /** Retrieves a value from the session via its key
   *
   * @param string $sessionKey
   *
   * @return mixed
   */
  #[Pure] public static function get(string $sessionKey) : mixed
  {
    return $_SESSION[sha1(self::$identifier . $sessionKey)];
  }
}

