<?php
declare(strict_types=1);

namespace src\tools\debug;

use phpunit\framework\TestCase;
use const otra\cache\php\{BASE_PATH,CORE_PATH};
use function otra\tools\debug\tailCustom;

/**
 * @runTestsInSeparateProcesses
 */
class TailCustomTest extends TestCase
{
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  public static function setUpBeforeClass(): void
  {
    parent::setUpBeforeClass();
    require CORE_PATH . 'tools/debug/tailCustom.php';
  }

  /**
   * @author Lionel Péramo
   */
  public function testTailCustom() : void
  {
    self::assertSame('world', tailCustom(BASE_PATH . 'tests/testTail.txt'));
  }

  /**
   * Test with no blank line at the end of the test text file.
   *
   * @author Lionel Péramo
   */
  public function testTailCustom_NoEndBlankLine() : void
  {
    self::assertSame('world', tailCustom(BASE_PATH . 'tests/testTailNoEndBlankLine.txt'));
  }
}
