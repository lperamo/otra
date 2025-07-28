<?php
declare(strict_types=1);

namespace src;

use otra\cache\php\Logger;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV, BASE_PATH, CORE_PATH, DIR_SEPARATOR, OTRA_PROJECT, PROD};
use function otra\tools\debug\tailCustom;
use function otra\tools\delTree;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class LoggerTest extends TestCase
{
  protected const string
    LOG_PATH = BASE_PATH . 'logs/',
    ATOM_DATE_REGEX = '\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d',
    IP_ADDRESS_REGEX = '((25[0–5]|2[0–4][0–9]|[01]?[0–9][0–9]?).(25[0–5]|2[0–4][0–9]|[01]?[0–9][0–9]?).(25[0–5]|2[0–4][0–9]|[01]?[0–9][0–9]?).(25[0–5]|2[0–4][0–9]|[01]?[0–9][0–9]?))|((([0–9A-Fa-f]{1,4}:){7}[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){6}:[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){5}:([0–9A-Fa-f]{1,4}:)?[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){4}:([0–9A-Fa-f]{1,4}:){0,2}[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){3}:([0–9A-Fa-f]{1,4}:){0,3}[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){2}:([0–9A-Fa-f]{1,4}:){0,4}[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){6}((b((25[0–5])|(1d{2})|(2[0–4]d)|(d{1,2}))b).){3}(b((25[0–5])|(1d{2})|(2[0–4]d)|(d{1,2}))b))|(([0–9A-Fa-f]{1,4}:){0,5}:((b((25[0–5])|(1d{2})|(2[0–4]d)|(d{1,2}))b).){3}(b((25[0–5])|(1d{2})|(2[0–4]d)|(d{1,2}))b))|(::([0–9A-Fa-f]{1,4}:){0,5}((b((25[0–5])|(1d{2})|(2[0–4]d)|(d{1,2}))b).){3}(b((25[0–5])|(1d{2})|(2[0–4]d)|(d{1,2}))b))|([0–9A-Fa-f]{1,4}::([0–9A-Fa-f]{1,4}:){0,5}[0–9A-Fa-f]{1,4})|(::([0–9A-Fa-f]{1,4}:){0,6}[0–9A-Fa-f]{1,4})|(([0–9A-Fa-f]{1,4}:){1,7}:))';

  private static string
    $logsProdPath,
    $simpleLogPath;

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    $_SERVER[APP_ENV] = 'test';
    self::$logsProdPath = self::LOG_PATH . $_SERVER[APP_ENV] . DIR_SEPARATOR;
    self::$simpleLogPath = self::$logsProdPath . 'simpleLog.txt';
    require CORE_PATH . 'tools/debug/tailCustom.php';

    if (!file_exists(self::$logsProdPath))
      mkdir(self::$logsProdPath, 0777, true);
  }

  public static function tearDownAfterClass() : void
  {
    parent::tearDownAfterClass();

    if (!OTRA_PROJECT && file_exists(self::LOG_PATH))
    {
      foreach(glob(BASE_PATH . 'logs/**/**.txt') as $logFile)
      {
        file_put_contents($logFile, '');
      }

      $otraTestsPath = self::LOG_PATH . 'otraTests/';
      $otraTestsLogFilePath = $otraTestsPath . 'log.txt';

      if (file_exists($otraTestsLogFilePath))
        unlink($otraTestsLogFilePath);

      if (file_exists($otraTestsPath))
        rmdir($otraTestsPath);
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
      '@\{
      "d":"' . self::ATOM_DATE_REGEX. '",
      "c":[01], 
      "i":"l|' . self::IP_ADDRESS_REGEX . '",
      "m":"\[OTRA_LOGGER_TEST\]"
      \}@mx',
      tailCustom($logFile)
    );

    // cleaning
    if (!OTRA_PROJECT)
      file_put_contents($logFile, '');
  }

  /**
   * @author Lionel Péramo
   */
  public function testLogToPath() : void
  {
    // context
    $testLogsPath = 'logs/otraTests/';
    $logCustomFolder = '../' . $testLogsPath;
    define(__NAMESPACE__ . '\\LOG_FILENAME', 'log.txt');
    $absolutePathToFolder = BASE_PATH . $testLogsPath;
    mkdir($absolutePathToFolder);
    $absolutePathToLogFilename = $absolutePathToFolder . LOG_FILENAME;
    $logCustomPath = $logCustomFolder . 'log';
    touch($absolutePathToLogFilename);

    // testing the logger...
    Logger::logToRelativePath('[OTRA_LOGGER_TEST]', $logCustomPath);
    self::assertMatchesRegularExpression(
      '@\{
      "d":"' . self::ATOM_DATE_REGEX. '",
      "c":[01], 
      "i":"l|' . self::IP_ADDRESS_REGEX . '",
      "m":"\[OTRA_LOGGER_TEST\]"
      \}@mx',
      tailCustom($absolutePathToLogFilename)
    );

    // cleaning
    file_put_contents($absolutePathToLogFilename, '');
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
    define(__NAMESPACE__ . '\\TRACE_LOG_FILE', self::LOG_PATH . $_SERVER[APP_ENV] . '/trace.txt');

    if (!file_exists('trace.txt'))
      touch(TRACE_LOG_FILE);

    // launching
    Logger::lg('[OTRA_TEST_DEBUG_TOOLS_LG]');

    // testing
    self::assertMatchesRegularExpression(
      '@\{
      "d":"' . self::ATOM_DATE_REGEX. '",
      "c":[01], 
      "i":"l|' . self::IP_ADDRESS_REGEX . '",
      "m":"\[OTRA_TEST_DEBUG_TOOLS_LG\]"
      \}@mx',
      tailCustom(TRACE_LOG_FILE)
    );

    // cleaning
    if (!OTRA_PROJECT)
      file_put_contents(TRACE_LOG_FILE, '');
  }

  /**
   * @author Lionel Péramo
   */
  public function testSimpleLogTo() : void
  {
    // context
    if (!file_exists(self::$logsProdPath))
      mkdir(self::$logsProdPath, 0777,true);

    if (!file_exists(self::$simpleLogPath))
      touch(self::$simpleLogPath);

    // Log a simple message
    Logger::simpleLogTo('[SIMPLE_LOG_TEST]', 'simpleLog');

    // Verify if the message is appended correctly
    $logContents = file_get_contents(self::$simpleLogPath);
    self::assertSame('[SIMPLE_LOG_TEST]' . PHP_EOL, $logContents);

    // cleaning
    if (!OTRA_PROJECT)
      file_put_contents(self::$simpleLogPath, '');
  }
}
