<?php

declare(strict_types=1);

namespace {

  use phpunit\framework\TestCase;

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
}
