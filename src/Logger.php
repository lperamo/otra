<?php
declare(strict_types=1);
namespace otra;

/** Simple logger class
 *
 * @package otra
 * @author Lionel Péramo
 */

abstract class Logger
{
  private const APPEND_LOG = 3,
    LOGS_PATH = BASE_PATH . 'logs/',
    SESSION_DATE = '_date',
    HTTP_USER_AGENT = 'HTTP_USER_AGENT',
    SESSION_BROWSER = '_browser',
    REMOTE_ADDR = 'REMOTE_ADDR';

  /**
   * Returns the date or also the ip address and the browser if different
   *
   * @return string
   */
  private static function logIpTest() : string
  {
    if (!isset($_SESSION[self::SESSION_DATE]))
      $_SESSION[self::SESSION_DATE] = $_SESSION['_ip'] = $_SESSION[self::SESSION_BROWSER] = '';

    $infos = '';
    $todayDate = date(DATE_ATOM, time());

    if ($todayDate !== $_SESSION[self::SESSION_DATE])
      $infos .= '[' . ($_SESSION[self::SESSION_DATE] = $todayDate) . '] ';

    // if we come from console, adds it to the log
    $infos .= (PHP_SAPI === 'cli') ? '[OTRA_CONSOLE] ' : '';

    /** @var array{REMOTE_ADDR?: string, HTTP_USER_AGENT?: string} $_SERVER */
    // remote address ip is not set if we come from the console or if we are in localhost
    $infos .= (isset($_SERVER[self::REMOTE_ADDR]) && $_SERVER[self::REMOTE_ADDR] !== $_SESSION['_ip'])
      ? '[' . ($_SESSION['_ip'] = $_SERVER[self::REMOTE_ADDR]) . '] '
      : '';

    // user agent not set if we come from the console
    if (isset($_SERVER[self::HTTP_USER_AGENT]) && $_SERVER[self::HTTP_USER_AGENT] != $_SESSION[self::SESSION_BROWSER])
      return $infos . '[' .  ($_SESSION[self::SESSION_BROWSER] = $_SERVER[self::HTTP_USER_AGENT]) . '] ';

    return $infos;
  }

  /**
   * @param string $path
   * @param string $message
   */
  public static function logging(string $path, string $message) : void
  {
    if (is_writable($path))
      error_log($message, self::APPEND_LOG, $path);
    else
      echo 'Cannot log the errors due to a lack of permissions' . (APP_ENV === PROD
        ? '!' . PHP_EOL
        : ' for the file \'' . $path . '\'!' . PHP_EOL);
  }

  /**
   * Appends a message to the log file at logs/log.txt
   *
   * @param string $message
   */
  public static function log(string $message) : void
  {
    self::logging(
      self::LOGS_PATH . $_SERVER[APP_ENV] . '/log.txt',
      self::logIpTest() . $message . PHP_EOL
    );
  }

  /**
   * Appends a message to the log file at the specified path appended to __DIR__
   *
   * @param string $message
   * @param string $path
   */
  public static function logToRelativePath(string $message, string $path = '') : void
  {
    self::logging(
      __DIR__ . '/' . $path . '.txt',
      self::logIpTest() . $message . PHP_EOL
    );
  }

  /**
   * Appends a message to the log file at the specified path into log path
   *
   * @param string $message
   * @param string $path
   */
  public static function logTo(string $message, string  $path = '') : void
  {
    self::logging(
      self::LOGS_PATH . $_SERVER[APP_ENV] . '/' . $path . '.txt',
      self::logIpTest() . $message . PHP_EOL
    );
  }

  /**
   * Logs all sql queries with the file name that launches it and the line number where it occurred.
   *
   * @param string $file
   * @param int    $line
   * @param string $message
   * @param string $path
   */
  public static function logSQLTo(string $file, int $line, string $message, string $path = '') : void
  {
    $logPath = self::LOGS_PATH . $_SERVER[APP_ENV] . '/' . $path . '.txt';

    self::logging(
      $logPath,
      (
      (!file_exists($logPath) || ($content = file_get_contents($logPath)) === false || '' === $content) ? '[' : '') .
      '{"file":"' . $file . '","line":' . $line . ',"query":"' .
      preg_replace(
        '/\s\s+/',
        ' ',
        str_replace(PHP_EOL, '', trim($message))
      ) . '"},'
    );
  }

  /**
   * @param string $message
   * @param string $errorType
   */
  public static function logExceptionOrErrorTo(string $message, string $errorType): void
  {
    self::logging(
      self::LOGS_PATH . $_SERVER[APP_ENV] . '/' .
        ($errorType === 'Exception' ? 'unknownExceptions' : 'unknownFatalErrors') . '.txt',
      self::logIpTest() . $errorType . ' : ' . $message . PHP_EOL . 'Stack trace : ' . PHP_EOL .
        print_r(debug_backtrace(), true) . PHP_EOL
    );
  }

  /**
   * @param string $message
   */
  public static function lg(string $message) : void
  {
    require_once CORE_PATH . 'Logger.php';
    self::logTo($message, 'trace');
  }
}

