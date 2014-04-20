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

    // Retrieving the headers
    $users = $db->values($db->query(
      'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_user_role ur ON ur.fk_id_user = u.id_user
      INNER JOIN lpcms_role r ON ur.fk_id_role = r.id_role'
    ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    echo $this->renderView('index.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC'))
    ), true);
  }

  public function addAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role']) || 4 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . mysql_real_escape_string($mail) . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    if(is_array($users))
      die(json_encode(array('success' => false, 'msg' => 'This mail already exists !')));

    $pwd = crypt($pwd, FWK_HASH);
    $dbError = array('error' => true, 'msg' => 'Database problem !');

    if(false === $db->query(
      'INSERT INTO lpcms_user (`mail`, `pwd`, `pseudo`) VALUES (\'' . mysql_real_escape_string($mail) . '\', \'' . mysql_real_escape_string($pwd) . '\', \'' . mysql_real_escape_string($pseudo) . '\');'
    ))
      die(json_encode($dbError));

    $id = $db->lastInsertedId();

    die(json_encode((false === $db->query(
      'INSERT INTO lpcms_user_role (`fk_id_user`, `fk_id_role`) VALUES (' . $id . ', ' . $role . ');'))
    ? $dbError
    : array('success' => true, 'msg' => 'User created.', 'pwd' => $pwd, 'id' => $id)));
  }

  public function editAction() // TODO roles association
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['id_user'], $_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role'], $_POST['oldMail']) || 6 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE `mail` = \'' . mysql_real_escape_string($mail) . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    if(is_array($users) && $oldMail != $users[0]['mail'])
      die(json_encode(array('success' => false, 'msg' => 'This mail already exists !')));

    $pwd = crypt($pwd, FWK_HASH);

    if(false === $db->query(
      'UPDATE lpcms_user SET
      `mail` = \'' . mysql_real_escape_string($mail) . '\',
      `pwd` = \'' . mysql_real_escape_string($pwd) . '\',
      `pseudo` = \'' . mysql_real_escape_string($pseudo) . '\' WHERE id_user = ' . intval($id_user)))
      die(json_encode(array('success' => false, 'msg' => 'Database problem !')));

    die(json_encode(array('success' => true, 'oldMail' => $_POST['oldMail'], 'msg' => 'User edited.', 'pwd' => $pwd)));
  }

  public function deleteAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['id_user']))  // TODO ip to ban
      return false;

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    if(false === $db->query(
      'DELETE FROM lpcms_user WHERE `id_user` = ' . intval($id_user)))
      die(json_encode(array('success' => false, 'msg' => 'Database problem !')));

    if(false === $db->query(
      'DELETE FROM lpcms_user_role WHERE fk_id_user = ' . intval($id_user)))
      die(json_encode(array('success' => false, 'msg' => 'Database problem !')));

    die(json_encode(array('success' => true, 'msg' => 'User deleted.')));
  }
}
?>
