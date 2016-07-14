<?
use phpunit\framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ConsoleTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }
  
  /**
   * @author Lionel Péramo
   */
  public function testExecConsole()
  {
    exec('php ' . _DIR_ . '/../console.php');
  }
}
