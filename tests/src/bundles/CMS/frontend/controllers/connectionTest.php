<?
use phpunit\framework\TestCase;
use \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class ConnectionTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testAjaxLoginActionHack()
  {
    Router::get('ajaxConnection');
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\Lionel_Exception
   * @expectedExceptionMessage Missing email !
   */
  public function testAjaxLoginActionMissingEmail()
  {
    $_POST['pwd'] = $_POST['email'] = '';
    Router::get('ajaxConnection');
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\Lionel_Exception
   * @expectedExceptionMessage Missing password !
   */
  public function testAjaxLoginActionMissingPassword()
  {
    $_POST['pwd'] = '';
    $_POST['email'] = 'xxxxxxx.xxxxx@gmail.com';
    Router::get('ajaxConnection');
  }

  /**
   * @author Lionel Péramo
   */
  public function testAjaxLoginActionBadCredentials()
  {
    $_POST['pwd'] = 'test';
    $_POST['email'] = 'xxxxxxx.xxxxx@gmail.com';
    $this->expectOutputString('{"0": "Bad credentials."}');
    Router::get('ajaxConnection');
  }
  /**
   * @author Lionel Péramo
   */
  public function testAjaxLoginActionOK()
  {
    $_POST['pwd'] = 'lpcms';
    $_POST['email'] = 'peramo.lionel@gmail.com';
    $this->expectOutputString('{"status": 1}');
    Router::get('ajaxConnection');
  }


  /**
   * @author Lionel Péramo
   */
  public function testLogoutAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    $_SERVER['REQUEST_SCHEME'] = 'HTTP';
    $_SERVER['HTTP_HOST'] = 'dev.save-our-space.com';
    Router::get('logout');
  }
}
