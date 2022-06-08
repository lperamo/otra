<?php
declare(strict_types=1);

namespace otra\cache\php;

/** Simple logger class
 *
 * @package otra
 * @author Lionel Péramo
 */

abstract class Logger
{
  private const
    APPEND_LOG = 3,
    LOGS_PATH = BASE_PATH . 'logs/',
    SESSION_DATE = '_date',
    HTTP_USER_AGENT = 'HTTP_USER_AGENT',
    SESSION_BROWSER = '_browser',
    REMOTE_ADDR = 'REMOTE_ADDR';

  final public const LOG_JSON_MASK = JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;

  /**
   * Returns the date or also the ip address and the browser if different
   *
   * @return array
   */
  private static function logIpTest() : array
  {
    if (!isset($_SESSION[self::SESSION_DATE]))
      $_SESSION[self::SESSION_DATE] = $_SESSION['_ip'] = $_SESSION[self::SESSION_BROWSER] = '';

    $infos = [];
    $todayDate = date(DATE_ATOM, time());

    if ($todayDate !== $_SESSION[self::SESSION_DATE])
      $infos['d'] = $_SESSION[self::SESSION_DATE] = $todayDate;

    // if we come from console, adds it to the log
    $infos['c'] = (PHP_SAPI === 'cli') ? '1' : '0';

    /** @var array{REMOTE_ADDR?: string, HTTP_USER_AGENT?: string} $_SERVER */
    // remote address ip is not set if we come from the console or if we are in localhost
    $infos['i'] = (isset($_SERVER[self::REMOTE_ADDR]) && $_SERVER[self::REMOTE_ADDR] !== $_SESSION['_ip'])
      ? ($_SESSION['_ip'] = $_SERVER[self::REMOTE_ADDR])
      : 'l';

    // user agent not set if we come from the console
    if (isset($_SERVER[self::HTTP_USER_AGENT])
      && $_SERVER[self::HTTP_USER_AGENT] != $_SESSION[self::SESSION_BROWSER])
      $infos['u'] = $_SESSION[self::SESSION_BROWSER] = $_SERVER[self::HTTP_USER_AGENT];

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
    $infos = self::logIpTest();
    $infos['m'] = $message;
    self::logging(
      self::LOGS_PATH . $_SERVER[APP_ENV] . '/log.txt',
      json_encode($infos, self::LOG_JSON_MASK) . PHP_EOL
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
    $infos = self::logIpTest();
    $infos['m'] = $message;
    self::logging(
      __DIR__ . DIR_SEPARATOR . $path . '.txt',
      json_encode($infos, self::LOG_JSON_MASK) . PHP_EOL
    );
  }

  /**
   * Appends a message to the log file at the specified path into log path
   *
   * @param string $message
   * @param string $logPath
   */
  public static function logTo(string $message, string  $logPath = 'log') : void
  {
    $infos = self::logIpTest();
    $infos['m'] = $message;
    $logPath = self::LOGS_PATH . $_SERVER[APP_ENV] . DIR_SEPARATOR . $logPath . '.txt';
    $filePointer = fopen($logPath, 'r+');

    if (!fread($filePointer, 1))
      fwrite($filePointer, '[');

    fclose($filePointer);

    clearstatcache();
    self::logging(
      $logPath,
      (!file_exists($logPath) || filesize($logPath) === 0 ? '[' : '') .
      json_encode($infos, self::LOG_JSON_MASK) . ',' . PHP_EOL
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
    $logPath = self::LOGS_PATH . $_SERVER[APP_ENV] . DIR_SEPARATOR . $path . '.txt';
    clearstatcache();
    self::logging(
      $logPath,
      (
      (!file_exists($logPath) || filesize($logPath) === 0) ? '[' : '') .
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
   * @param array  $traces
   */
  public static function logExceptionOrErrorTo(string $message, string $errorType, array $traces): void
  {
    clearstatcache();
    $filePath = self::LOGS_PATH . $_SERVER[APP_ENV] . DIR_SEPARATOR .
      ($errorType === 'Exception' ? 'unknownExceptions' : 'unknownFatalErrors') . '.txt';
    $infos = self::logIpTest();
    $infos['m'] = $errorType . ' : ' . $message;
    $infos['s'] = self::formatStackTracesForLog($traces);
    self::logging(
      $filePath,
      (!file_exists($filePath) || filesize($filePath) === 0 ? '[' : '') .
      json_encode($infos, self::LOG_JSON_MASK) . ',' . PHP_EOL
    );
  }

  /**
   * @param string $message
   */
  public static function lg(string $message) : void
  {
    self::logTo($message, 'trace');
  }

  /**
   * @param array $traces
   *
   * @return array
   */
  public static function formatStackTracesForLog(array $traces) : array
  {
    foreach ($traces as &$traceItems)
    {
      foreach ($traceItems as $traceKey => &$traceValue)
      {
        if ($traceKey === 'file')
        {
          $traceValue = str_replace(
            [
              BASE_PATH,
              CORE_PATH
            ],
            [
              'BASE_PATH + ',
              'CORE_PATH + '
            ],
            $traceValue
          );
        }
      }
    }

    return $traces;
  }

  /**
   * We keep this code into a function only to lighten to useful code
   * (that the code be slow when there is an error/exception is less important)
   *
   * @param $message
   * @param $traces
   */
  public static function logWithStackTraces($message, $traces)
  {
    Logger::logTo(
      json_encode(
        [
          'm' =>  $message,
          's' => self::formatStackTracesForLog($traces)
        ],
        Logger::LOG_JSON_MASK
      ),
      'classNotFound'
    );
  }
}
