<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class AjaxGeneralTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testIndexAction()
  {
    session_start();
    $_SESSION['sid']['uid'] = 1;
    Router::get('backendAjaxGeneral');
  }
}
?>
