<?

use phpunit\framework\TestCase,
  \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class IndexActionTest extends TestCase
{
  protected function setUp(): void
  {
    $_SERVER['APP_ENV'] = 'prod';
    define('CACHE_PATH', BASE_PATH . 'cache/');
    define('LAYOUT', BASE_PATH . 'bundles/views/layout.phtml'); // It has to be layout
    define('VERSION', 'v1');
  }

  /**
   * @author Lionel Péramo
   * @doesNotPerformAssertions
   *
   * TODO Finish cleanup of the framework, tests side.
   */
  public function testIndexAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('index');
  }
}
