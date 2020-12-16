<?php
declare(strict_types=1);

namespace src;

use otra\Logger;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LoggerTest extends TestCase
{
  const LOG_PATH = BASE_PATH . 'logs/';
  private static string $LOGS_PROD_PATH;

  public static function setUpBeforeClass(): void
  {
    $_SERVER[APP_ENV] = 'prod';
    self::$LOGS_PROD_PATH = self::LOG_PATH . $_SERVER[APP_ENV] . '/';
    // @TODO we should be able to do a simple require and not require_once as this code must be executed only once !
œ    require_once CORE_PATH . 'tools/debug/dump.php';

    if (file_exists(self::$LOGS_PROD_PATH) === false)
      mkdir(self::$LOGS_PROD_PATH, 0777, true);
  }

  public static function tearDownAfterClass(): void
  {
    if (OTRA_PROJECT === false)
    {
      rmdir(self::$LOGS_PROD_PATH);
      rmdir(self::LOG_PATH);
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testLog() : void
  {
    // context
    Logger::log('[OTRA_LOGGER_TEST]');
    $logFile = self::$LOGS_PROD_PATH . 'log.txt';
    self::assertRegExp(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_LOGGER_TEST\]@',
      tailCustom($logFile, 1)
    );

    // cleaning
    if (OTRA_PROJECT === false)
      unlink($logFile);
  }

  /**
   * @author Lionel Péramo
   */
  public function testLogToPath() : void
  {
    // context
    // @TODO duplication of code to require dump.php. Why putting this code in setUpBeforeClass creates a fatal
    // error "Cannot redeclare lg()" etc. ? This method should be executed only once ...
    $path = 'logs/otraTests/';
    $logCustomFolder = '../' . $path;
    define('LOG_FILENAME', 'log.txt');
    $absolutePathToFolder = BASE_PATH . $path;
    mkdir($absolutePathToFolder);
    $absolutePathToLogFilename = $absolutePathToFolder . LOG_FILENAME;
    $logCustomPath = $logCustomFolder . 'log';
    touch($absolutePathToLogFilename);

    // testing the logger...
    Logger::logToRelativePath('[OTRA_LOGGER_TEST]', $logCustomPath);
    $this->assertRegExp(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_LOGGER_TEST\]@',
      tailCustom($absolutePathToLogFilename, 1)
    );

    // cleaning
    unlink($absolutePathToLogFilename);
    rmdir($absolutePathToFolder);
  }
}
