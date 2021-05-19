<?php
declare(strict_types=1);

namespace src\console\deployment\taskFileOperation;

use otra\OtraException;
use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH};
use const otra\console\{CLI_WARNING, END_COLOR};
use const otra\console\deployment\genBootstrap\{ANNOTATION_DEBUG_PAD, BASE_PATH_LENGTH};
use function otra\console\deployment\genBootstrap\{showFile, evalPathVariables};

/**
 * @runTestsInSeparateProcesses
 */
class ShowFileTest extends TestCase
{
  private const
    ADDITIONAL_TEXT = 'additional text',
    FILENAME = 'filename.php',
    FILENAME_ABSOLUTE_PATH = BASE_PATH . self::FILENAME,
    LEVEL_ZERO = 0,
    LEVEL_ONE = 1;

  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testVerbose_LevelNotZero() : void
  {
    // context
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);

    // testing string output
    static::expectOutputString(
      str_pad(
        '  | ' .
        self::FILENAME,
        ANNOTATION_DEBUG_PAD,
        '.'
      ) . CLI_WARNING . self::ADDITIONAL_TEXT . END_COLOR . PHP_EOL
    );

    // launching
    showFile(self::LEVEL_ONE, self::FILENAME_ABSOLUTE_PATH, self::ADDITIONAL_TEXT);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testVerbose_LevelZero() : void
  {
    // context
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);

    // testing string output
    static::expectOutputString(
      str_pad(
        self::FILENAME,
        ANNOTATION_DEBUG_PAD,
        '.'
      ) . CLI_WARNING . self::ADDITIONAL_TEXT . END_COLOR . PHP_EOL
    );

    // launching
    showFile(self::LEVEL_ZERO, self::FILENAME_ABSOLUTE_PATH, self::ADDITIONAL_TEXT);
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testNoVerbose() : void
  {
    // context
    $fileContent = 'echo $test';
    define('otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS', ['test' => 'value']);

    // launching
    [$fileContent, $isTemplate] = evalPathVariables($fileContent, self::FILENAME, 'echo $test;');

    // testing
    static::assertEquals(
      'echo \'value\'',
      $fileContent
    );
    static::assertEquals(
      false,
      $isTemplate
    );
  }
}
