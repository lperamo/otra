<?php
declare(strict_types=1);

namespace otra\tests;

use phpunit\framework\TestCase;
use const otra\cache\php\{APP_ENV,PROD};

/**
 * @runTestsInSeparateProcesses
 */
class ConsoleTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
  }

  /**
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   */
  public function testExecConsole(): void
  {
    exec('php ' . __DIR__ . '/../otra.php');
  }
}
