<?
use phpunit\framework\TestCase;
use \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class ConnectionTest extends TestCase
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
  public function testAjaxLoginActionHack()
  {
    Router::get('ajaxConnection');
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
   * @expectedExceptionMessage Missing email !
   */
  public function testAjaxLoginActionMissingEmail()
  {
    $_POST['pwd'] = $_POST['email'] = '';
    Router::get('ajaxConnection');
  }

  /**
   * @author Lionel Péramo
   * @expectedException        \lib\myLibs\LionelException
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
    $_POST['pwd'] = 'pgmail';
    $_POST['email'] = 'peramo.lionel@gmail.com';
    $this->expectOutputString('{"status": 1, "url": "/backend/modules"}');
    Router::get('ajaxConnection');
  }


  /**
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testLogoutAction()
  {
    session_start();
    $_SESSION['sid']['uid'] = 1;
    $_SERVER['HTTP_REFERER'] = 'https://dev.frameworkcms.com';
    Router::get('logout');
  }
}
