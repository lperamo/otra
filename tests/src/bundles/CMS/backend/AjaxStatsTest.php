<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class AjaxStatsTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

  /**
   * @author Lionel Péramo
   */
  public function testIndexAction()
  {
    session_start();
    $_SESSION['sid']['uid'] = 1;
    Router::get('backendAjaxStats');
  }
}
?>
