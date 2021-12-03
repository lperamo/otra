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
  private static string $blowfishAlgorithm;
  private static array $matches;

  /**
   * @param int $rounds Number of rounds for the blowfish algorithm that protects the session
   */
  public static function init(int $rounds = 7) : void
  {
    if (!(isset(self::$identifier) && $_SESSION[self::$identifier]))
    {
      self::$identifier = bin2hex(openssl_random_pseudo_bytes(32));

      if ($rounds < 10)
        $rounds = '0' . $rounds;

      self::$blowfishAlgorithm = '$2a$' . $rounds . '$';
    }
  }

  /**
   * @param string $sessionKey
   * @param mixed  $value
   */
  public static function set(string $sessionKey, mixed $value) : void
  {
    if (!isset(self::$matches[$sessionKey]))
      self::$matches[$sessionKey] = crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier);

    $_SESSION[self::$matches[$sessionKey]] = $value;
  }

  /**
   * Puts all the value associated with the keys of the array into the session
   *
   * @param array $array
   */
  public static function sets(array $array) : void
  {
    foreach($array as $sessionKey => $value)
    {
      if (!isset(self::$matches[$sessionKey]))
        self::$matches[$sessionKey] = crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier);

      $_SESSION[self::$matches[$sessionKey]] = $value;
    }
  }

  /**
   * @param string $sessionKey
   *
   * @return mixed
   */
  #[Pure] public static function get(string $sessionKey) : mixed
  {
    return $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier )];
  }

  public static function getIfExists(string $sessionKey) : mixed
  {
    return $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier )] ?? false;
  }

  /**
   * If the first key exists, get it and the other keys. Otherwise, returns false.
   *
   * @param array $sessionKeys
   *
   * @throws OtraException
   * @return array|bool
   */
  public static function getArrayIfExists(array $sessionKeys) : array|bool
  {
    if (!isset(self::$identifier))
      throw new OtraException('You must initialize OTRA session before using "getArrayIfExists"');

    $firstCryptedKey = crypt($sessionKeys[0], self::$identifier);

    if (!isset($_SESSION[$firstCryptedKey]))
      return false;

    $result = [$_SESSION[$firstCryptedKey]];
    array_pop($sessionKeys);

    foreach($sessionKeys as $sessionKey)
    {
      $result[] = $_SESSION[crypt($sessionKey, self::$blowfishAlgorithm . self::$identifier )];
    }

    return $result;
  }

  /**
   * Cleans OTRA session (but keeps PHP sessions keys that are not related to OTRA)
   *
   * @throws OtraException
   */
  public static function clean(): void
  {
    if (!isset(self::$matches))
      throw new OtraException('You cannot clean an OTRA session that is not initialized.');

    foreach (self::$matches as $match)
    {
      unset($_SESSION[$match]);
    }
  }
}
