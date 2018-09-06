<?
use phpunit\framework\TestCase,
    bundles\CMS\services\BackendService;

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
    BackendService::checkConnection('backendModules');
  }
}
?>
