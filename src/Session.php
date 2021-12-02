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
  private static string $blowfishAlgorithm = '$2a$07$';

  public static function init() : void { self::$identifier = openssl_random_pseudo_bytes(32); }

  /** Puts a value associated with a key into the session
   *
   * @param string $key
   * @param mixed  $value
   */
  public static function set(string $key, mixed $value) : void
  {
    $_SESSION[crypt($key, self::$blowfishAlgorithm . self::$identifier . '$')] = $value;
  }

  /** Puts all the value associated with the keys of the array into the session
   *
   * @param array $array
   */
  public static function sets(array $array) : void
  {
    foreach($array as $sessionKey => $value)
      $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier . '$')] = $value;
  }

  /** Retrieves a value from the session via its key
   *
   * @param string $sessionKey
   *
   * @return mixed
   */
  #[Pure] public static function get(string $sessionKey) : mixed
  {
    return $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier . '$')];
  }

  public static function getIfExists(string $sessionKey) : mixed
  {
    return $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier . '$')] ?? false;
  }

  /**
   * If the first key exists, get it and the other keys
   *
   * @param array $sessionKeys
   *
   * @return array|bool
   */
  public static function getArrayIfExists(array $sessionKeys) : array|bool
  {
    $firstSessionKey = $sessionKeys[0];
    $firstCryptedKey = crypt($firstSessionKey, self::$identifier);

    if (!isset($_SESSION[$firstCryptedKey]))
      return false;

    $result = [$_SESSION[$firstCryptedKey]];
    array_pop($sessionKeys);

    foreach($sessionKeys as $sessionKey)
    {
      $result[] = $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier . '$')];
    }

    return $result;
  }
}
