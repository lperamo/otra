<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class AjaxArticleTest extends TestCase
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
  public function testShowAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('ajaxShowArticle', ['article2']);
  }
}
