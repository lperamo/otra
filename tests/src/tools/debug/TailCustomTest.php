<?php
declare(strict_types=1);

namespace src\tools\debug;

use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class TailCustomTest extends TestCase
{
  // fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  public static function setUpBeforeClass(): void
  {
    require CORE_PATH . 'tools/debug/tailCustom.php';
  }

  /**
   * @author Lionel Péramo
   */
  public function testTailCustom() : void
  {
    self::assertEquals('world', tailCustom(BASE_PATH . 'tests/testTail.txt', 1));
  }

  /**
   * Test with no blank line at the end of the test text file.
   *
   * @author Lionel Péramo
   */
  public function testTailCustom_NoEndBlankLine() : void
  {
    self::assertEquals('world', tailCustom(BASE_PATH . 'tests/testTailNoEndBlankLine.txt', 1));
  }
}
