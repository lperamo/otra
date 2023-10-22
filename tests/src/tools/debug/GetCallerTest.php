<?php
declare(strict_types=1);

namespace src\tools\debug;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\CORE_PATH;
use function otra\tools\debug\getCaller;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class GetCallerTest extends TestCase
{
  /**
   * Lionel Péramo
   */
  public function testGetCaller() : void
  {
    require CORE_PATH . 'tools/debug/getCaller.php';
    self::assertSame(__FILE__ . ':' . __LINE__, (fn() => getCaller())());
  }
}
