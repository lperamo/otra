<?php
declare(strict_types=1);

namespace src\tools\debug;

use phpunit\framework\TestCase;
use const otra\cache\php\CORE_PATH;
use function otra\tools\debug\getCaller;

/**
 * @runTestsInSeparateProcesses
 */
class GetCallerTest extends TestCase
{
  // it fixes issues like when AllConfig is not loaded while it should be
  protected $preserveGlobalState = FALSE;

  /**
   * Lionel Péramo
   */
  public function testGetCaller() : void
  {
    require CORE_PATH . 'tools/debug/getCaller.php';
    self::assertSame(__FILE__ . ':' . __LINE__, (fn() => getCaller())());
  }
}
