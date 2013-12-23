<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxUsersController extends Controller
{
  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
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

// dump($db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC')));
    echo $this->renderView('index.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC'))
    ), true);
  }

  public function addUser(){

  }

  public function editUser(){

  }

  public function deleteUser(){

  }
}
?>
