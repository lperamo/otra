<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use const otra\console\{CLI_ERROR, CLI_WARNING, END_COLOR};
use function otra\console\deployment\genBootstrap\evalPathVariables;

/**
 * @runTestsInSeparateProcesses
 */
class EvalPathVariablesTest extends TestCase
{
  // it fixes issues like when AllConfig is not loaded while it should be
  private const
    FILENAME = 'filename.php',
    CONSTANT_PATH_CONSTANTS = 'otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS';

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
  public function testNoVariables() : void
  {
    // context
    $backupFileContent = $fileContent = 'echo \'test\';';

    // launching
    [$fileContent, $isTemplate] = evalPathVariables($fileContent, self::FILENAME, 'echo \'test\'');

    // testing
    static::assertSame(
      $backupFileContent,
      $fileContent
    );
    static::assertSame(
      false,
      $isTemplate
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testVariableReplacedNoTemplate() : void
  {
    // context
    $fileContent = 'echo $test';
    define(self::CONSTANT_PATH_CONSTANTS, ['test' => 'value']);

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

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testVariableCannotBeReplacedNoTemplate() : void
  {
    // context
    $fileContent = 'echo $test';
    $trimmedMatch = 'echo $test;';
    $filename = self::FILENAME;
    define(self::CONSTANT_PATH_CONSTANTS, []);

    // testing exceptions and output string
    self::expectException(OtraException::class);
    self::expectOutputString(
      CLI_ERROR . 'CANNOT EVALUATE THE REQUIRE STATEMENT BECAUSE OF THE NON DEFINED DYNAMIC VARIABLE ' .
      CLI_WARNING . '$test' . CLI_ERROR . ' in ' . CLI_WARNING . $trimmedMatch . CLI_ERROR .
      ' in the file ' . CLI_WARNING . $filename . CLI_ERROR . ' !' . END_COLOR . PHP_EOL
    );

    // launching
    [$fileContent, $isTemplate] = evalPathVariables($fileContent, $filename, $trimmedMatch);

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

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testIsTemplate() : void
  {
    // context
    $fileContent = 'require $templateFilename';
    $trimmedMatch = 'require $templateFilename;';
    $filename = self::FILENAME;
    define(self::CONSTANT_PATH_CONSTANTS, []);

    // launching
    [$fileContent, $isTemplate] = evalPathVariables($fileContent, $filename, $trimmedMatch);

    // testing
    static::assertSame(
      'require $templateFilename',
      $fileContent
    );
    static::assertSame(
      true,
      $isTemplate
    );
  }
}
