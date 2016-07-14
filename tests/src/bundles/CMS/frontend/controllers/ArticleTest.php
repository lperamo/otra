<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class ArticleTest extends TestCase
{
  /**
   * @author Lionel Péramo
   */
  public function testShowAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('ajaxShowArticle', ['article2']);
  }
}
