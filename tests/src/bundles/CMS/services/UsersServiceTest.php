<?
use phpunit\framework\TestCase,
  bundles\CMS\services\UsersService;

/**
 * @runTestsInSeparateProcesses
 */
class UsersServiceTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

  /**
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testGetUsersTab()
  {
    require CORE_PATH . 'Router.php';
    UsersService::getUsersTab();
  }
}
?>
