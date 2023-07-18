<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_INFO, CLI_WARNING, END_COLOR};
use function otra\console\deployment\genBootstrap\{analyzeUseToken};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class AnalyzeUseTokenTest extends TestCase
{
  private const
    LABEL_TESTING_FILES_TO_CONCAT = 'Testing $filesToConcat variable...',
    LABEL_TESTING_PARSED_FILES = 'Testing $parsedFiles variable...',
    PHP_EXTENSION = '.php',
    CONST_NAME_DEBUG_LEVEL = '\\DEBUG_LEVEL';

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * @author Lionel Péramo
   * @Depends ShowFileTest::testVerbose_LevelNotZero()
   * @Depends ShowFileTest::testVerbose_LevelZero()
   * @Depends ShowFileTest::testNoVerbose()
   */
  public function testRouterAlwaysIncluded(): void
  {
    // context
    define(__NAMESPACE__ . self::CONST_NAME_DEBUG_LEVEL, 1);
    $filesToConcat = [];
    $parsedFiles = [];
    $class = 'config\\Router';

    // launching
    analyzeUseToken(
      DEBUG_LEVEL,
      $filesToConcat,
      $class,
      $parsedFiles,
      $class
    );

    // testing
    static::expectOutputString('');
    static::assertSame([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertSame([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testIsDevControllerTrait(): void
  {
    // context
    define(__NAMESPACE__ . self::CONST_NAME_DEBUG_LEVEL, 1);
    $filesToConcat = $parsedFiles = [];
    $class = 'DevControllerTrait';

    // launching
    analyzeUseToken(
      DEBUG_LEVEL,
      $filesToConcat,
      $class,
      $parsedFiles,
      $class
    );

    // testing
    static::expectOutputString(
      CLI_INFO . 'We will not send the development controller in production.' . END_COLOR . PHP_EOL
    );
    static::assertSame([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertSame([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testIsProdControllerTrait(): void
  {
    // context
    define(__NAMESPACE__ . self::CONST_NAME_DEBUG_LEVEL, 1);
    $filesToConcat = $parsedFiles = [];
    $class = 'ProdControllerTrait';

    // launching
    analyzeUseToken(
      DEBUG_LEVEL,
      $filesToConcat,
      $class,
      $parsedFiles,
      $class
    );

    // testing
    $filename = CORE_PATH . 'prod/' . $class . self::PHP_EXTENSION;
    static::expectOutputString('');
    static::assertSame([
      'php' =>
        [
          'use' => [$filename]
        ]
    ],
      $filesToConcat,
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    static::assertSame([$filename], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testIsBlockSystem(): void
  {
    // context
    define(__NAMESPACE__ . self::CONST_NAME_DEBUG_LEVEL, 1);
    $filesToConcat = [];
    $parsedFiles = [];
    $class = 'cache\\php\\BlocksSystem';

    // launching
    analyzeUseToken(
      DEBUG_LEVEL,
      $filesToConcat,
      $class,
      $parsedFiles,
      $class
    );

    // testing
    static::expectOutputString('');
    static::assertSame([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertSame([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testHasSlashAtFirstAndExternalLibraryClass(): void
  {
    // context
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);
    define(__NAMESPACE__ . self::CONST_NAME_DEBUG_LEVEL, 1);
    $filesToConcat = $parsedFiles = [];
    $class = '\ProdControllerTrait';

    // launching
    analyzeUseToken(
      DEBUG_LEVEL,
      $filesToConcat,
      $class,
      $parsedFiles,
      $class
    );

    // testing
    static::expectOutputString(CLI_WARNING . 'EXTERNAL LIBRARY CLASS : ' . $class . END_COLOR . PHP_EOL);
    static::assertSame([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertSame([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }
}
