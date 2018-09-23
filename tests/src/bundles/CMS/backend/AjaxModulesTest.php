<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @author Lionel Péramo
 * @runTestsInSeparateProcesses
 */
class AjaxModulesTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testGetElementsAction_NotConnected()
  {
    $_GET['id'] = 1;
    Router::get('getElements');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testGetElementsAction_Connected()
  {
    $_SESSION['sid'] = 1;
    $_GET['id'] = 1;
    Router::get('getElements');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testIndexAction()
  {
    Router::get('backendAjaxModules');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testsearchArticleAction()
  {
    $_GET['search'] = 'test';
    Router::get('articleSearch');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testsearchElementAction()
  {
    $_GET['search'] = 'test';
    Router::get('elementSearch');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation !
   */
  public function testsearchModuleAction()
  {
    $_GET['search'] = 'test';
    Router::get('moduleSearch');
  }
}
?>
