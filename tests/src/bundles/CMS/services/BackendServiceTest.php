<?
use phpunit\framework\TestCase,
    bundles\CMS\services\backendService;

/**
 * @runTestsInSeparateProcesses
 */
class BackendServiceTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

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
