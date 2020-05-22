<?php
declare(strict_types=1);

namespace otra;

/** Simple logger class
 *
 * @author Lionel Péramo
 */

abstract class Logger
{
  private const APPEND_LOG = 3,
    LOGS_PATH = BASE_PATH . 'logs/';

  /**
   * Returns the date or also the ip address and the browser if different
   *
   * @return string
   */
  private static function logIpTest() : string
  {
    // Only needed to ease maintainability, maybe pass those to class static variables on order to get rid of the condition ?
    if (defined('SESSION_DATE') === false)
    {
      define('SESSION_DATE', '_date');
      define('HTTP_USER_AGENT', 'HTTP_USER_AGENT');
      define('SESSION_BROWSER', '_browser');
      define('REMOTE_ADDR', 'REMOTE_ADDR');
    }

    if (false === isset($_SESSION[SESSION_DATE]))
      $_SESSION[SESSION_DATE] = $_SESSION['_ip'] = $_SESSION[SESSION_BROWSER] = '';

    $infos = '';
    $date = date(DATE_ATOM, time());

    if ($date !== $_SESSION[SESSION_DATE])
      $infos .= '[' . ($_SESSION[SESSION_DATE] = $date) . '] ';

    // if we come from console, adds it to the log
    $infos .= (PHP_SAPI === 'cli') ? '[OTRA_CONSOLE] ' : '';

    // remote address ip is not set if we come from the console or if we are in localhost
    $infos .= (true === isset($_SERVER[REMOTE_ADDR]) && $_SERVER[REMOTE_ADDR] !== $_SESSION['_ip'])
      ? '[' . ($_SESSION['_ip'] = $_SERVER[REMOTE_ADDR]) . '] '
      : '';

    // user agent not set if we come from the console
    if (true === isset($_SERVER[HTTP_USER_AGENT]) && $_SERVER[HTTP_USER_AGENT] != $_SESSION[SESSION_BROWSER])
      return $infos . '[' .  ($_SESSION[SESSION_BROWSER] = $_SERVER[HTTP_USER_AGENT]) . '] ';

    return $infos;
  }

  /**
   * Appends a message to the log file at logs/log.txt
   *
   * @param string $message
   */
  public static function log(string $message)
  {
    error_log(
      self::logIpTest() . $message . "\n",
      self::APPEND_LOG,
      LOGS_PATH . $_SERVER[APP_ENV] . '/log.txt'
    );
  }

  /**
   * Appends a message to the log file at the specified path appended to __DIR__
   *
   * @param string $message
   * @param string $path
   */
  public static function logToRelativePath(string $message, string $path = '')
  {
    error_log(
      self::logIpTest() . $message . "\n",
      self::APPEND_LOG,
      __DIR__ . '/' . $path . '.txt'
    );
  }

  /**
   * Appends a message to the log file at the specified path into log path
   *
   * @param string $message
   * @param string $path
   */
  public static function logTo(string $message, string  $path = '') {
    error_log(
      self::logIpTest() . $message . "\n",
      self::APPEND_LOG,
      LOGS_PATH . $_SERVER[APP_ENV] . '/' . $path . '.txt'
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
  public static function logSQLTo(string $file, int $line, string $message, string $path = '')
  {
    $path = LOGS_PATH . $_SERVER[APP_ENV] . '/' . $path . '.txt';

    // If there is no SQL content logged, we start with '[', otherwise with ''
    error_log(
      ((file_exists($path) === false || ($content = file_get_contents($path)) === false || '' === $content) ? '[' : '') .
      '{"file":"' . $file . '","line":' . $line . ',"query":"' .
      preg_replace(
        '/\s\s+/',
        ' ',
        str_replace(["\r", "\r\n", "\n"], '', trim($message))
      ) . '"},',
      self::APPEND_LOG,
      $path);
  }

  /**
   * @param string $message
   * @param string $errorType
   */
  public static function logExceptionOrErrorTo(string $message, string $errorType): void
  {
    self::logTo(
      $errorType . ' : ' . $message . PHP_EOL .
      'Stack trace : ' . PHP_EOL .
      print_r(debug_backtrace(), true),
      $errorType === 'Exception' ? 'unknownExceptions' : 'unknownFatalErrors'
    );
  }
}

