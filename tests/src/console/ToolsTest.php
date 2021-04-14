<?php
declare(strict_types=1);

namespace src\console\helpAndTools;

use phpunit\framework\TestCase;

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
      CLI_GREEN . 4 . CLI_LIGHT_GRAY . ' // variables declaration' . PHP_EOL .
      CLI_RED . 5 . ' $blabla = "blabla";' . PHP_EOL .
      CLI_GREEN . 6 . CLI_LIGHT_GRAY . ' $superCool = \'superCool\';' . PHP_EOL
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
      CLI_GREEN . 3 . CLI_LIGHT_GRAY .  ' '. PHP_EOL .
      CLI_GREEN . 4 . CLI_LIGHT_GRAY . ' // variables declaration' . PHP_EOL .
      CLI_RED . 5 . ' $blabla = "blabla";' . PHP_EOL .
      CLI_GREEN . 6 . CLI_LIGHT_GRAY . ' $superCool = \'superCool\';' . PHP_EOL .
      CLI_GREEN . 7 . CLI_LIGHT_GRAY . ' ' . PHP_EOL
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
    $this->assertEquals(
      '[\'test\'=>[\'test2\'=>\'test3\'],\'test4\'=>5]',
      $reducedArray
    );
  }
}
