<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH};
use function otra\console\deployment\genBootstrap\processStaticCalls;

/**
 * @runTestsInSeparateProcesses
 */
class ProcessStaticCallsTest extends TestCase
{
  private const LEVEL = 1;

  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  /**
   * @author Lionel Péramo
   */
  public function testAllGood(): void
  {
    // context
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);

    $contentToAdd = 'namespace otra;' . PHP_EOL .
      'self::test();' . PHP_EOL .
      'parent::test2();' . PHP_EOL .
      '\PDO::query();' . PHP_EOL .
      '$renderController::$path = $_SERVER[\'DOCUMENT_ROOT\'] . \'..\';' . PHP_EOL .
      'MasterController::getCacheFileName();';
    $filesToConcat = $parsedFiles = $classesFromFile = [];

    // launching
    ob_start();
    processStaticCalls(
      self::LEVEL,
      $contentToAdd,
      $filesToConcat,
      $parsedFiles,
      $classesFromFile
    );

    // testing
    self::assertSame(
      [
        'php' => [
          'static' => [
            CORE_PATH . 'MasterController.php'
          ]
        ]
      ],
      $filesToConcat,
      'Testing $filesToConcat...'
    );
    self::assertSame(
      'namespace otra;' . PHP_EOL .
      'self::test();' . PHP_EOL .
      'parent::test2();' . PHP_EOL .
      '\PDO::query();' . PHP_EOL .
      '$renderController::$path = $_SERVER[\'DOCUMENT_ROOT\'] . \'..\';' . PHP_EOL .
      'MasterController::getCacheFileName();',
      $contentToAdd,
      'Testing $contentToAdd...'
    );
    self::assertSame(
      [CORE_PATH . 'MasterController.php'],
      $parsedFiles,
      'Testing $parsedFiles...'
    );

    self::assertSame(
      '',
      ob_get_clean(),
      'Testing output string...'
    );
  }
}
