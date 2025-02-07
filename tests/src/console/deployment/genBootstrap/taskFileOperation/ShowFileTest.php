<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\{BASE_PATH, CONSOLE_PATH};
use const otra\console\{CLI_WARNING, END_COLOR};
use const otra\console\deployment\genBootstrap\{ANNOTATION_DEBUG_PAD};
use function otra\console\deployment\genBootstrap\{showFile, evalPathVariables};

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ShowFileTest extends TestCase
{
  private const int
    LEVEL_ZERO = 0,
    LEVEL_ONE = 1;

  private const string
    ADDITIONAL_TEXT = 'additional text',
    FILENAME = 'filename.php',
    FILENAME_ABSOLUTE_PATH = BASE_PATH . self::FILENAME;

  protected function setUp(): void
  {
    parent::setUp();
    define('otra\\console\\deployment\\genBootstrap\\BASE_PATH_LENGTH', strlen(BASE_PATH));
  }

  /**
   * @author Lionel Péramo
   */
  public function testVerbose_LevelNotZero() : void
  {
    // context
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);
    require CONSOLE_PATH . '/deployment/genBootstrap/showFile.php';

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
   */
  public function testVerbose_LevelZero() : void
  {
    // context
    define('otra\\console\\deployment\\genBootstrap\\VERBOSE', 2);
    require CONSOLE_PATH . '/deployment/genBootstrap/showFile.php';

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
   */
  public function testNoVerbose() : void
  {
    // context
    $fileContent = 'echo $test';
    define('otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS', ['test' => 'value']);
    require CONSOLE_PATH . '/deployment/genBootstrap/evalPathVariables.php';

    // launching
    [$fileContent, $isTemplate] = evalPathVariables($fileContent, self::FILENAME, 'echo $test;');

    // testing
    static::assertSame(
      'echo \'value\'',
      $fileContent
    );
    static::assertSame(
      false,
      $isTemplate
    );
  }
}
