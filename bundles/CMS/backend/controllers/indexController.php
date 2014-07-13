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
    if($this->action != 'index' && !isset($_SESSION['sid'])){
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
    $db->values($db->query('SELECT * FROM lpcms_header'));
    $db->values($db->query('SELECT * FROM lpcms_footer'));

    echo $this->renderView('index.phtml', array(
      'headers' => $_SESSION['headers'],
      'footers' => $_SESSION['footers'],
      'lpcms_body' => 'page_t.jpg'
    ));
  }

  public function modulesAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    $modules = $db->values($db->query('SELECT * FROM lpcms_module'));

    echo $this->renderView('modules.phtml', array(
      'moduleTypes' => ajaxModulesController::$moduleTypes,
      'right' => ajaxModulesController::$rights,
      'items' => $modules
    ));
  }

  public function generalAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = array();

    echo $this->renderView('general.phtml', array(
      'items' => array()
    ));
  }

  public function statsAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = array();

    echo $this->renderView('stats.phtml', array('items' => array()));
  }

  public function usersAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

 // $db->query('ALTER TABLE `lpcms`.`lpcms_user_role`
 //  ADD CONSTRAINT `fk_lpcms_user_role`
 //  FOREIGN KEY (`fk_id_role`)
 //  REFERENCES `lpcms`.`lpcms_role` (`id_role`)
 //  ON DELETE NO ACTION
 //  ON UPDATE NO ACTION;');

    // Retrieving the users
    $users = $db->values($db->query(
      'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id, r.nom FROM lpcms_user u
      INNER JOIN lpcms_role r ON u.role_id = r.id
      ORDER BY id_user
      LIMIT 3'
    ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);
    // elseif(empty($users))
    //   $users = array();

    $count = $db->single($db->query('SELECT COUNT(id_user) FROM lpcms_user'));
    // var_dump($count);die;

    echo $this->renderView('users.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id, nom FROM lpcms_role ORDER BY nom ASC')),
      'count' => (!empty($count)) ? current($count) : '',
      'limit' => 3
    ));
  }
}
?>
