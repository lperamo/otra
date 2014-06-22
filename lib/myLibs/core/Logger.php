<?php
namespace lib\myLibs\core;

/** Simple logger class
 *
 * @author Lionel Péramo
 */

class Logger
{
	/** Returns the date or also the ip address and the browser if different
	 * @return string
	 */
	private static function logIpTest()
	{
		$_SESSION['_date'] = $_SESSION['_ip'] = $_SESSION['_browser'] = '';
		if(!isset($_SESSION['_date']))
			$_SESSION['_date'] = $_SESSION['_ip'] = $_SESSION['_browser'] = '';

		$infos = '';
		$date = date(DATE_ATOM, time());
		if($date != $_SESSION['_date'])
			$infos .= '[' . ($_SESSION['_date'] = $date) . '] ';

		if($_SERVER['REMOTE_ADDR'] != $_SESSION['_ip'])
			$infos .= $infos . '[' . ($_SESSION['_ip'] = $_SERVER['REMOTE_ADDR']) . '] ';

		if($_SERVER['HTTP_USER_AGENT'] != $_SESSION['_browser'])
			return $infos . '[' .  ($_SESSION['_browser'] = $_SERVER['HTTP_USER_AGENT']) . '] ';


		return $infos;
	}

	/** Appends a message to the log file at logs/log.txt */
	public static function log($message) {
		error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . '/../../../logs/log.txt');
	}

	/** Appends a message to the log file at the specified path appended to __DIR__ */
	public static function logToPath($message, $path = '') {
		error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . $path . '.txt');
	}

	/** Appends a message to the log file at the specified path into logo path */
	public static function logTo($message, $path = '') {
		error_log(self::logIpTest() . $message . "\n", 3,  __DIR__ . '/../../../logs/' . $path . '.txt');
	}

  public static function logSQLTo($file, $line, $message, $path = '')
  {
    $path = __DIR__ . '/../../../logs/' . $path . '.txt';
    error_log(((! ($content = file_get_contents($path)) || '' == $content) ? '[' : '') . '{"file":"' . $file . '","line":' . $line . ',"query":"' . preg_replace('/\s\s+/', ' ', str_replace(array("\r", "\r\n", "\n"), '', trim($message))) . '"},', 3, $path);
  }
}
?>
