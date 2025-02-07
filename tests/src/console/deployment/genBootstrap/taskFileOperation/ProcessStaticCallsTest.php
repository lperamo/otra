<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH};
use function otra\console\deployment\genBootstrap\processStaticCalls;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ProcessStaticCallsTest extends TestCase
{
  private const int LEVEL = 1;
  private const string USED_NAMESPACE = 'otra\\console\\deployment\\genBootstrap\\';
  
  /**
   * @author Lionel Péramo
   */
  public function testAllGood(): void
  {
    // context
    define(self::USED_NAMESPACE . 'OTRA_KEY_STATIC', 'static');
    define(self::USED_NAMESPACE . 'NAMESPACE_SEPARATOR', '\\');
    require CONSOLE_PATH . 'deployment/genBootstrap/assembleFiles.php';
    define(self::USED_NAMESPACE . 'VERBOSE', 2);

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
