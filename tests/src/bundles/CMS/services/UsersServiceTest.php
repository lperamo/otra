<?
use phpunit\framework\TestCase,
  bundles\CMS\services\usersService;

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
   */
  public function testGetUsersTab()
  {
    require CORE_PATH . 'Router.php';
    usersService::getUsersTab();
  }
}
?>
