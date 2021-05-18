<?php
declare(strict_types=1);

namespace src\console\deployment\taskFileOperation;

use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH, CORE_PATH};
use const otra\console\{CLI_WARNING, END_COLOR};
use function otra\console\deployment\genBootstrap\{analyzeUseToken};

/**
 * @runTestsInSeparateProcesses
 */
class AnalyzeUseTokenTest extends TestCase
{
  private const
    LABEL_TESTING_FILES_TO_CONCAT = 'Testing $filesToConcat variable...',
    LABEL_TESTING_PARSED_FILES = 'Testing $parsedFiles variable...',
    PHP_EXTENSION = '.php';

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = false;

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
  public function testRouterAlwaysIncluded()
  {
    // context
    define('DEBUG_LEVEL', 1);
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
    static::assertEquals([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertEquals([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testIsDevControllerTrait()
  {
    // context
    define('DEBUG_LEVEL', 1);
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
    $filename = CORE_PATH . 'dev/' . $class . self::PHP_EXTENSION;
    static::expectOutputString('');
    static::assertEquals(
      [
        'php' =>
        [
          'use' => [$filename]
        ]
      ],
      $filesToConcat,
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    static::assertEquals([$filename], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testIsProdControllerTrait()
  {
    // context
    define('DEBUG_LEVEL', 1);
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
    static::assertEquals([
      'php' =>
        [
          'use' => [$filename]
        ]
    ],
      $filesToConcat,
      self::LABEL_TESTING_FILES_TO_CONCAT
    );
    static::assertEquals([$filename], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testIsBlockSystem()
  {
    // context
    define('DEBUG_LEVEL', 1);
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
    static::assertEquals([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertEquals([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }

  public function testHasSlashAtFirstAndExternalLibraryClass()
  {
    // context
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);
    define('DEBUG_LEVEL', 1);
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
    static::assertEquals([], $filesToConcat, self::LABEL_TESTING_FILES_TO_CONCAT);
    static::assertEquals([], $parsedFiles, self::LABEL_TESTING_PARSED_FILES);
  }
}
