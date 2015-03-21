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

    Sql::getDB();

    $_SESSION['headers'] = \bundles\CMS\models\Header::getAll();
    $_SESSION['footers'] = \bundles\CMS\models\Footer::getAll();

    echo $this->renderView('index.phtml', [
      'headers' => $_SESSION['headers'],
      'footers' => $_SESSION['footers'],
      'lpcms_body' => 'page_t.jpg'
    ]);
  }

  public function modulesAction()
  {
    Sql::getDB();

    echo $this->renderView('modules.phtml', [
      'moduleTypes' => ajaxModulesController::$moduleTypes,
      'right' => ajaxModulesController::$rights,
      'items' => \bundles\CMS\models\Module::getAll()
    ]);
  }

  public function generalAction()
  {
    Sql::getDB();

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
    Sql::getDB();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = [];

    echo $this->renderView('stats.phtml', ['items' => []]);
  }

  public function usersAction()
  {
    echo $this->renderView('users.phtml', \bundles\CMS\services\usersService::getUsersTab());
  }
}
?>
