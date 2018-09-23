<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @author Lionel Péramo
 * @doesNotPerformAssertions
 * @runTestsInSeparateProcesses
 */
class AjaxUsersTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
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
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testDeleteAction()
  {
    Router::get('deleteUser');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testEditAction()
  {
    Router::get('editUser');
  }


  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testIndexAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('backendAjaxUsers');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testSearchAction()
  {
    Router::get('searchUser');
  }
}
?>
