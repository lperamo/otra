<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class AjaxModulesTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testGetElementsAction()
  {
    $_GET['id'] = 1;
    Router::get('getElements');
  }

  /**
   * @author Lionel Péramo
   */
  public function testIndexAction()
  {
    Router::get('backendAjaxModules');
  }

  /**
   * @author Lionel Péramo
   */
  public function testsearchArticleAction()
  {
    $_GET['search'] = 'test';
    Router::get('articleSearch');
  }

  public function testsearchElementAction()
  {
    $_GET['search'] = 'test';
    Router::get('elementSearch');
  }

  public function testsearchModuleAction()
  {
    $_GET['search'] = 'test';
    Router::get('moduleSearch');
  }
}
?>
