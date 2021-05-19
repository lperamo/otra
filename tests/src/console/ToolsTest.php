<?php
declare(strict_types=1);

namespace src\console;

use phpunit\framework\TestCase;
use const otra\cache\php\{CONSOLE_PATH,TEST_PATH};
use const otra\console\{CLI_ERROR, CLI_GRAY, CLI_SUCCESS};
use function otra\console\{convertArrayFromVarExportToShortVersion,showContext,showContextByError};

/**
 * @runTestsInSeparateProcesses
 */
class ToolsTest extends TestCase
{
  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();

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
    self::assertEquals(
      '[\'test\'=>[\'test2\'=>\'test3\'],\'test4\'=>5]',
      $reducedArray
    );
  }
}
