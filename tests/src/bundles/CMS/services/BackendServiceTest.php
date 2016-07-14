<?
use phpunit\framework\TestCase,
    bundles\CMS\services\backendService;

/**
 * @runTestsInSeparateProcesses
 */
class BackendServiceTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testCheckConnection()
  {
    require CORE_PATH . 'Router.php';
    backendService::checkConnection('backendModules');
  }
}
?>
