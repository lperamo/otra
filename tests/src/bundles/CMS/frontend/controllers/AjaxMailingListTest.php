<?
use phpunit\framework\TestCase,
  \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class AjaxMailingTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testAddAction()
  {
    session_start();
//    $_SESSION['sid']['uid'] = 1;
    $_POST['email'] = 'xxxxxxxxxxxxxxxxx.lp@gmail.com';
    Router::get('ajaxMailingList');
  }
}
