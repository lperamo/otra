<?
use phpunit\framework\TestCase,
    bundles\CMS\services\configService;

/**
 * @runTestsInSeparateProcesses
 */
class ConfigServiceTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testGetConfigTab()
  {
    session_start();
    $_SESSION['sid']['uid'] = 1;
    configService::getConfigTab();
  }
}
?>
