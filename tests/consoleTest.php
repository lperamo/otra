<?php
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ConsoleTest extends TestCase
{
  protected function setUp() : void
  {
    $_SERVER['APP_ENV'] = 'prod';
  }

  /**
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   */
  public function testExecConsole()
  {
    exec('php ' . _DIR_ . '/../otra.php');
  }
}
