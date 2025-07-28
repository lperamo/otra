<?php
declare(strict_types=1);

namespace otra\tests;

use PHPUnit\Framework\TestCase;
use const otra\cache\php\{APP_ENV,PROD};

/**
 * @runTestsInSeparateProcesses
 */
class ConsoleTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = 'test';
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
