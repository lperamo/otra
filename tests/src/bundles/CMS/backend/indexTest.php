<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class IndexTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testGeneralAction()
  {
    session_start();
    $_SESSION['sid']['uid'] = 1;
    Router::get('backendGeneral');
  }

  /**
   * @author Lionel Péramo
   */
  public function testIndexAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('backend');
  }

  /**
   * @author Lionel Péramo
   */
  public function testModulesAction()
  {
    Router::get('backendModules');
  }

  /**
   * @author Lionel Péramo
   */
  public function testStatsAction()
  {
    Router::get('backendStats');
  }

  /**
   * @author Lionel Péramo
   */
  public function testUsersAction()
  {
    Router::get('backendUsers');
  }
}
?>
