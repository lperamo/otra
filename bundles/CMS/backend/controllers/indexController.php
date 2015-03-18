<?
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    lib\myLibs\core\Router;

class indexController extends Controller
{
  public function preExecute()
  {
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      exit;
    }
  }

  public function indexAction()
  {
    if(isset($_SESSION['sid']))
    {
      $this->modulesAction();
      return;
    }

    /** @var Sql $db */
    //    $db = new Sql();
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving headers and footers
    $_SESSION['headers'] = $db->values($db->query('SELECT * FROM lpcms_header'));
    $_SESSION['footers'] = $db->values($db->query('SELECT * FROM lpcms_footer'));

    echo $this->renderView('index.phtml', [
      'headers' => $_SESSION['headers'],
      'footers' => $_SESSION['footers'],
      'lpcms_body' => 'page_t.jpg'
    ]);
  }

  public function modulesAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    $modules = $db->values($db->query('SELECT * FROM lpcms_module'));

    echo $this->renderView('modules.phtml', [
      'moduleTypes' => ajaxModulesController::$moduleTypes,
      'right' => ajaxModulesController::$rights,
      'items' => $modules
    ]);
  }

  public function generalAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = [];

    echo $this->renderView('general.phtml', [
      'items' => []
    ]);
  }

  public function statsAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = [];

    echo $this->renderView('stats.phtml', ['items' => []]);
  }

  public function usersAction()
  {
    list($roles, $users, $count) = \bundles\CMS\services\usersService::getUsersTab();

    echo $this->renderView('users.phtml', [
      'users' => $users,
      'roles' => $roles,
      'count' => !empty($count) ? current($count) : '',
      'limit' => 3
    ]);
  }
}
?>
