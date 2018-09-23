<?
use phpunit\framework\TestCase,
    \lib\myLibs\Router;

/**
 * @runTestsInSeparateProcesses
 */
class IndexTest extends TestCase
{
  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testGeneralAction()
  {
    session_start();
    $_SESSION['sid']['uid'] = 1;
    Router::get('backendGeneral');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testIndexAction()
  {
//    session_start();
//    $_SESSION['sid']['uid'] = 1;
    Router::get('backend');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testModulesAction()
  {
    Router::get('backendModules');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testStatsAction()
  {
    Router::get('backendStats');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testUsersAction()
  {
    Router::get('backendUsers');
  }
}
?>
