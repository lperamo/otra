<?php
declare(strict_types=1);

namespace src\console;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use const otra\cache\php\{CONSOLE_PATH,TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_GRAY, CLI_SUCCESS};
use function otra\console\
{convertArrayFromVarExportToShortVersion, convertLongArrayToShort, showContext, showContextByError};

/**
 * @runTestsInSeparateProcesses
 */
class ToolsTest extends TestCase
{
  // fixes isolation related issues
  protected $preserveGlobalState = FALSE;

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'tools.php';
  }

  /**
   * @author Lionel Péramo
   */
  public function testShowContextByError() : void
  {
    // testing
    $this->expectOutputString(
      CLI_SUCCESS . 4 . CLI_GRAY . ' // variables declaration' . PHP_EOL .
      CLI_ERROR . 5 . ' $blabla = "blabla";' . PHP_EOL .
      CLI_SUCCESS . 6 . CLI_GRAY . ' $superCool = \'superCool\';' . PHP_EOL
    );

    // launching
    showContextByError(TEST_PATH . '/examples/tools/toolsExample.php', 'error in line 5', 2);
  }

  /**
   * @author Lionel Péramo
   */
  public function testShowContext(): void
  {
    // testing
    $this->expectOutputString(
      CLI_SUCCESS . 3 . CLI_GRAY .  ' '. PHP_EOL .
      CLI_SUCCESS . 4 . CLI_GRAY . ' // variables declaration' . PHP_EOL .
      CLI_ERROR . 5 . ' $blabla = "blabla";' . PHP_EOL .
      CLI_SUCCESS . 6 . CLI_GRAY . ' $superCool = \'superCool\';' . PHP_EOL .
      CLI_SUCCESS . 7 . CLI_GRAY . ' ' . PHP_EOL
    );

    // launching
    showContext(TEST_PATH . '/examples/tools/toolsExample.php', 5, 4);
  }

  public function testConvertArrayFromVarExportToShortVersion(): void
  {
    // launching
    $reducedArray = convertArrayFromVarExportToShortVersion(
      var_export(
        [
          'test' => ['test2' => 'test3'],
          'test4' => 5
        ],
        true
      )
    );

    // testing
    self::assertSame(
      '[\'test\'=>[\'test2\'=>\'test3\'],\'test4\'=>5]',
      $reducedArray
    );
  }

  /**
   * @throws ReflectionException
   */
  public function testConvertLongArrayToShort():void
  {
    // launching
    $reducedArray = convertLongArrayToShort(
      [
        'test' => ['test2' => ['test3']], // tests deep arrays
        'test2' => [3], // tests integers value without key
        'test3' => [], // tests empty arrays
        'test4' => true // tests booleans
      ]
    );

    // testing
    self::assertSame(
      "['test'=>['test2'=>[0=>'test3']],'test2'=>[0=>3],'test3'=>[],'test4'=>true]",
      $reducedArray
    );
  }
}
