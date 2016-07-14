<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class AjaxUsersTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testAddAction()
  {
    session_start();
    $_SESSION['sid'] = [
      'uid' => 1,
      'role' => 1
    ];
    Router::get('addUser');
  }

  /**
   * @author Lionel Péramo
   */
  public function testDeleteAction()
  {
    Router::get('deleteUser');
  }

  /**
   * @author Lionel Péramo
   */
  public function testEditAction()
  {
    Router::get('editUser');
  }


  /**
   * @author Lionel Péramo
   */
  public function testIndexAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('backendAjaxUsers');
  }

  /**
   * @author Lionel Péramo
   */
  public function testSearchAction()
  {
    Router::get('searchUser');
  }
}
?>
