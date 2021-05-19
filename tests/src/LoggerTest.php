<?php
declare(strict_types=1);

namespace src;

use otra\cache\php\Logger;
use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV, BASE_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT, PROD};
use function otra\tools\debug\tailCustom;
use function otra\tools\delTree;

/**
 * @runTestsInSeparateProcesses
 */
class LoggerTest extends TestCase
{
  protected const LOG_PATH = BASE_PATH . 'logs/';
  private static string $logsProdPath;
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  public static function setUpBeforeClass() : void
  {
    $_SERVER[APP_ENV] = PROD;
    self::$logsProdPath = self::LOG_PATH . $_SERVER[APP_ENV] . DIR_SEPARATOR;
    require CORE_PATH . 'tools/debug/tailCustom.php';

    if (!file_exists(self::$logsProdPath))
      mkdir(self::$logsProdPath, 0777, true);
  }

  public static function tearDownAfterClass() : void
  {
    if (!OTRA_PROJECT)
    {
      if (file_exists(self::LOG_PATH))
      {
        require CORE_PATH . 'tools/deleteTree.php';
        delTree(self::LOG_PATH);
      }
    }
  }

  /**
   * @author Lionel Péramo
   */
  public function testLog() : void
  {
    // context
    $logFile = self::$logsProdPath . 'log.txt';

    if (!file_exists(self::$logsProdPath))
      mkdir(self::$logsProdPath, 0777,true);

    if (!file_exists($logFile))
      touch($logFile);

    Logger::log('[OTRA_LOGGER_TEST]');
    self::assertMatchesRegularExpression(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_LOGGER_TEST\]@',
      tailCustom($logFile, 1)
    );

    // cleaning
    if (!OTRA_PROJECT)
      unlink($logFile);
  }

  /**
   * @author Lionel Péramo
   */
  public function testLogToPath() : void
  {
    // context
    $testLogsPath = 'logs/otraTests/';
    $logCustomFolder = '../' . $testLogsPath;
    define('src\LOG_FILENAME', 'log.txt');
    $absolutePathToFolder = BASE_PATH . $testLogsPath;
    mkdir($absolutePathToFolder);
    $absolutePathToLogFilename = $absolutePathToFolder . LOG_FILENAME;
    $logCustomPath = $logCustomFolder . 'log';
    touch($absolutePathToLogFilename);

    // testing the logger...
    Logger::logToRelativePath('[OTRA_LOGGER_TEST]', $logCustomPath);
    self::assertMatchesRegularExpression(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_LOGGER_TEST\]@',
      tailCustom($absolutePathToLogFilename, 1)
    );

    // cleaning
    unlink($absolutePathToLogFilename);
    rmdir($absolutePathToFolder);
  }

  /**
   * we use "Depends" and not "depends" (note the uppercase letter) as it does not work with "depends"
   * @Depends src\tools\debug\TailCustomTest::testTailCustom
   *
   * @author Lionel Péramo
   */
  public function testLg() : void
  {
    // context
    define('src\TRACE_LOG_FILE', self::LOG_PATH . $_SERVER[APP_ENV] . '/trace.txt');

    if (!file_exists('trace.txt'))
      touch(TRACE_LOG_FILE);

    // launching
    Logger::lg('[OTRA_TEST_DEBUG_TOOLS_LG]');

    // testing
    self::assertMatchesRegularExpression(
      '@\[\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d\]\s\[OTRA_CONSOLE\]\s\[OTRA_TEST_DEBUG_TOOLS_LG\]@',
      tailCustom(TRACE_LOG_FILE, 1)
    );

    // cleaning
    if (!OTRA_PROJECT)
      unlink(TRACE_LOG_FILE);
  }
}
