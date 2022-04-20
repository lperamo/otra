<?php
declare(strict_types=1);
/** Description of Session
 *
 * @author Lionel Péramo */
namespace otra;
use ReflectionException;
use const otra\cache\php\{CACHE_PATH, CORE_PATH};
use const otra\config\VERSION;
use function otra\console\convertLongArrayToShort;
use function otra\tools\isSerialized;

/**
 * @package otra
 */
abstract class Session
{
  public static string $sessionsCachePath = CACHE_PATH . 'php/sessions/';
  private static bool $initialized = false;
  private static ?string
    $identifier,
    $blowfishAlgorithm,
    $sessionId,
    $sessionFile;
  private static array $matches = [];

  /**
   * @param int $rounds Number of rounds for the blowfish algorithm that protects the session
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public static function init(int $rounds = 7) : void
  {
    if ($rounds < 4 || $rounds > 31)
      throw new OtraException('Rounds must be in the range 4-31');

    self::$sessionId = session_id();
    self::$sessionFile = self::$sessionsCachePath . sha1('ca' . self::$sessionId . VERSION . 'che') . '.php';

    // blowfish algorithm must put leading zeros if the rounds are less than 10
    if ($rounds < 10)
      $rounds = '0' . $rounds;

    self::$blowfishAlgorithm = '$2y$' . $rounds . '$';

    if (!file_exists(self::$sessionFile))
    {
      if (!touch(self::$sessionFile))
        throw new OtraException('Cannot create the session file.');

      // will produce a string of 11 * 2 = 22 characters which is the minimum (?) for the
      // Blowfish algorithm of the kind $2y$
      self::$identifier = bin2hex(openssl_random_pseudo_bytes(11));
      self::toFile();
    } else
    {
      $sessionData = require self::$sessionFile;
      self::$identifier = $sessionData['otra_i'];
      require_once CORE_PATH . 'tools/isSerialized.php';

      if (!self::$initialized)
      {
        self::$matches = [];

        foreach ($sessionData as $sessionDatumKey => $sessionDatum)
        {
          // only objects are serialized in session files, that's why we deserialize then enforce serialization
          if (!str_starts_with($sessionDatumKey, 'otra_'))
            self::set($sessionDatumKey, isSerialized($sessionDatum) ? unserialize($sessionDatum) : $sessionDatum);
        }
      }
    }

    self::$initialized = true;
  }

  /**
   * @param string $sessionKey
   * @param mixed  $value
   */
  public static function set(string $sessionKey, mixed $value) : void
  {
    self::$matches[$sessionKey] = [
      'notHashed' => $value,
      'hashed' => crypt(serialize($value), self::$blowfishAlgorithm . self::$identifier)
    ];

    $_SESSION[$sessionKey] = self::$matches[$sessionKey]['hashed'];
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
      self::$matches[$sessionKey] = [
        'notHashed' => $value,
        'hashed' => crypt(serialize($value), self::$blowfishAlgorithm . self::$identifier)
      ];

      $_SESSION[$sessionKey] = self::$matches[$sessionKey]['hashed'];
    }
  }

  /**
   * Returns false if it does not exist in the cache...should not occur.
   *
   * @param string $sessionKey
   *
   * @return mixed
   */
  public static function get(string $sessionKey) : mixed
  {
    return self::$matches[$sessionKey]['notHashed'];
  }

  /**
   * @param string $sessionKey
   *
   * @return mixed
   */
  public static function getIfExists(string $sessionKey) : mixed
  {
    return isset($_SESSION[$sessionKey])
      ? self::$matches[$sessionKey]['notHashed']
      : false;
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

    $firstKey = $sessionKeys[0];

    if (!isset($_SESSION[$firstKey]))
      return false;

    $result = [
      $firstKey => self::$matches[$firstKey]['notHashed']
    ];
    array_shift($sessionKeys);

    foreach($sessionKeys as $sessionKey)
    {
      $result[$sessionKey] = self::$matches[$sessionKey]['notHashed'];
    }

    return $result;
  }

  public static function getAll(): array
  {
    $result = [];

    foreach(self::$matches as $sessionKey => $sessionValueInformation)
    {
      $result[$sessionKey] = $sessionValueInformation['notHashed'];
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

    if (file_exists(self::$sessionFile) && !unlink(self::$sessionFile))
      throw new OtraException('Cannot remove session file!');

    self::$identifier = self::$blowfishAlgorithm = self::$sessionId = self::$sessionFile = null;

    foreach (array_keys(self::$matches) as $sessionKey)
      unset($_SESSION[$sessionKey], self::$matches[$sessionKey]);
  }

  /**
   * @throws ReflectionException
   */
  public static function toFile(): void
  {
    // Get in memory session data
    $actualSessionData = [];

    foreach(self::$matches as $sessionKey => $sessionValueInformation)
    {
      $actualSessionData[$sessionKey] = $sessionValueInformation['notHashed'];
    }

    $actualSessionData['otra_i'] = self::$identifier;
    $actualSessionData['otra_b'] = self::$blowfishAlgorithm;

    require_once CORE_PATH . 'console/colors.php';
    require_once CORE_PATH . 'console/tools.php';

    $dataInformation = convertLongArrayToShort($actualSessionData);
    $fileContent = '<?php declare(strict_types=1);namespace otra\cache\php\sessions;';

    // Updates the file with the merged version
    file_put_contents(
      self::$sessionFile,
      $fileContent . 'return ' . $dataInformation . ';' . PHP_EOL
    );

    if ($_SERVER['REMOTE_ADDR'] === '::1')
      chmod(self::$sessionFile, 0775);
  }

  public static function getNativeSessionData(): array
  {
    $result = [];

    foreach($_SESSION as $sessionKey => $sessionValue)
    {
      if (!in_array($sessionKey, array_values(self::$matches)))
        $result[$sessionKey] = $sessionValue;
    }

    return $result;
  }
}
