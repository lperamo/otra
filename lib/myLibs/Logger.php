<?
namespace lib\myLibs;

/** Simple logger class
 *
 * @author Lionel Péramo
 */

class Logger
{
  /**
   * Returns the date or also the ip address and the browser if different
   *
   * @return string
   */
  private static function logIpTest() : string
  {
    if (false === isset($_SESSION['_date']))
      $_SESSION['_date'] = $_SESSION['_ip'] = $_SESSION['_browser'] = '';

    $infos = '';
    $date = date(DATE_ATOM, time());

    if ($date !== $_SESSION['_date'])
      $infos .= '[' . ($_SESSION['_date'] = $date) . '] ';

    if ($_SERVER['REMOTE_ADDR'] !== $_SESSION['_ip'])
      $infos .= '[' . ($_SESSION['_ip'] = $_SERVER['REMOTE_ADDR']) . '] ';

    // user agent not set if we come from the console
    if (true === isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != $_SESSION['_browser'])
      return $infos . '[' .  ($_SESSION['_browser'] = $_SERVER['HTTP_USER_AGENT']) . '] ';

    return $infos;
  }

  /**
   * Appends a message to the log file at logs/log.txt
   *
   * @param string $message
   */
  public static function log(string $message) {
    error_log(self::logIpTest() . $message . "\n", 3,  BASE_PATH . 'logs/' . XMODE . '/log.txt');
  }

  /**
   * Appends a message to the log file at the specified path appended to __DIR__
   *
   * @param string $message
   * @param string $path
   */
  public static function logToPath(string $message, string $path = '') {
    error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . $path . '.txt');
  }

  /**
   * Appends a message to the log file at the specified path into log path
   *
   * @param string $message
   * @param string $path
   */
  public static function logTo(string $message, string  $path = '') {
    error_log(self::logIpTest() . $message . "\n", 3,  BASE_PATH . 'logs/' . XMODE . '/' . $path . '.txt');
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
    $path = BASE_PATH . 'logs/' . XMODE . '/' . $path . '.txt';
    error_log(
      ((file_exists($path) === false || ($content = file_get_contents($path)) === false || '' === $content) ? '[' : '') .
      '{"file":"' . $file . '","line":' . $line . ',"query":"' .
      preg_replace(
        '/\s\s+/',
        ' ',
        str_replace(["\r", "\r\n", "\n"], '', trim($message))
      ) . '"},',
      3,
      $path);
  }
}
?>
