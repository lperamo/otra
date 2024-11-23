<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use const otra\console\
{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use function otra\console\deployment\genBootstrap\evalPathVariables;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class EvalPathVariablesTest extends TestCase
{
  // it fixes issues like when AllConfig is not loaded while it should be
  private const string
    FILENAME = 'filename.php',
    CONSTANT_PATH_CONSTANTS = 'otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS';

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/evalPathVariables.php';
  }

  /**
   * @author Lionel Péramo
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
   */
  public function testVariableCannotBeReplacedNoTemplate() : void
  {
    // context
    $fileContent = 'echo $test';
    $trimmedMatch = 'echo $test;';
    $filename = self::FILENAME;
    define(self::CONSTANT_PATH_CONSTANTS, []);

    // testing exceptions and output string
    self::expectOutputString(
      CLI_WARNING . 'require/include statement not evaluated because of the non defined dynamic variable ' .
      CLI_INFO_HIGHLIGHT . '$test' . CLI_WARNING . ' in' . PHP_EOL .
      '  ' . CLI_INFO_HIGHLIGHT . $trimmedMatch . CLI_WARNING . PHP_EOL .
      '  in the file ' . CLI_INFO_HIGHLIGHT . $filename . CLI_WARNING . '!' . END_COLOR . PHP_EOL
    );

    // launching
    [$fileContent, $isTemplate] = evalPathVariables($fileContent, $filename, $trimmedMatch);

    // testing
    static::assertSame(
      'echo $test',
      $fileContent
    );
    static::assertSame(
      false,
      $isTemplate
    );
  }

  /**
   * @author Lionel Péramo
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
