<?
use phpunit\framework\TestCase;
use lib\myLibs\{Router, Session};
use lib\myLibs\bdd\Sql;

/**
 * @author Lionel Péramo
 * @runTestsInSeparateProcesses
 */
class AjaxMailingTest extends TestCase
{
  private static $db = 'CMS';

  protected function setUp()
  {
    define('XMODE', 'PROD');
  }

  /**
   * @doesNotPerformAssertions
   *
   * TODO Do assertions and remove the related annotation
   */
  public function testAddAction()
  {
    session_start();
//    $_SESSION['sid']['uid'] = 1;
    $_POST['email'] = 'xxxxxxxxxxxxxxxxx.lp@gmail.com';
    Session::init();
    Session::set('db', self::$db);
    Session::set('dbConn', Sql::getDB());
    Router::get('ajaxMailingList');
    // TODO add assertions here
    // TODO clean the database
  }
}
