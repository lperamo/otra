<?
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ConsoleTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testExecConsole()
  {
    exec('php ' . _DIR_ . '/../console.php');
  }
}
