<?
use phpunit\framework\TestCase,
  bundles\CMS\services\usersService;

/**
 * @runTestsInSeparateProcesses
 */
class UsersServiceTest extends TestCase
{
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
