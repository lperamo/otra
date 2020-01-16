<?php

use lib\otra\Logger;
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LoggerTest extends TestCase
{
  const LOG_PATH = BASE_PATH . 'logs/';

  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  /**
   * @author Lionel Péramo
   */
  public function testLog() : void
  {
    require CORE_PATH . 'debugTools.php';
    Logger::log('[OTRA_LOGGER_TEST]');
    $this->assertRegExp(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_LOGGER_TEST\]@',
      tailCustom(self::LOG_PATH . $_SERVER['APP_ENV'] . '/log.txt', 1)
    );
  }

  /**
   * @author Lionel Péramo
   */
  public function testLogToPath() : void
  {
    // context
    require CORE_PATH . 'debugTools.php';
    $path = 'logs/otraTests/';
    $logCustomFolder = '../../' . $path;
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
