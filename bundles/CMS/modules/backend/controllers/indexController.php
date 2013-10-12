<?php
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\modules\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    lib\myLibs\core\Router;

class indexController extends Controller
{
  public function preExecute(){
    // var_dump($controller, $chunks);die;
    // var_dump($_SESSION['sid'], $this->action);die;
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction()
  {
    if(isset($_SESSION['sid'])){
      $this->modulesAction();
      die;
    }

    /** @var Sql $db */
    //    $db = new Sql();
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    if((!isset($_SESSION['headers'])) )
      $_SESSION['headers'] = $db->values($db->query('SELECT * FROM lpcms_header'));

    // Retrieving the footers
    if (!isset($_SESSION['footers']))
      $_SESSION['footers'] = $db->values($db->query('SELECT * FROM lpcms_footer'));

    echo $this->renderView('index.phtml', array(
      'headers' => $_SESSION['headers'],
      'footers' => $_SESSION['footers'],
      'lpcms_body' => 'page_t.jpg'
    ));
  }

  public function modulesAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    $modules = $db->values($db->query('SELECT * FROM lpcms_module'));

    $_SESSION['js'] = array();

    echo $this->renderView('modules.phtml', array(
      'items' => array()
    ));
  }

  public function generalAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = array();

    echo $this->renderView('general.phtml', array(
      'items' => array()
    ));
  }

  public function statsAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    // $users = $db->fetchAssoc($db->query('SELECT * FROM lpcms_user'));
    // dump($users);

    $_SESSION['js'] = array();

    echo $this->renderView('stats.phtml', array(
      'items' => array()
    ));
  }

  public function usersAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // Puts the UTF-8 encoding in order to correctly render accents
    $db->query('SET NAMES UTF8');

    // Retrieving the headers
    $users = $db->values($db->query(
      'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_user_role ur ON ur.fk_id_user = u.id_user
      INNER JOIN lpcms_role r ON ur.fk_id_role = r.id_role'
    ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    $_SESSION['js'] = array();

    echo $this->renderView('users.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC'))
    ));
  }
}
?>
