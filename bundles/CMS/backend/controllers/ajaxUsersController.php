<?
/**
* Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxUsersController extends Controller
{
  public function preExecute()
  {
    if($this->action != 'index' && !isset($_SESSION['sid'])) {
      Router::get('backend');
      die;
    }
  }

  /** Called when we click on tab 'users', if it's not already loaded */
  public function indexAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();

    // Retrieving the users
     $users = $db->values($db->query(
       'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
       INNER JOIN lpcms_role r ON u.role_id = r.id_role
       ORDER BY id_user
       LIMIT 3'
     ));

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = array($users);

    $count = $db->single($db->query('SELECT COUNT(id_user) FROM lpcms_user'));

    echo $this->renderView('index.phtml', array(
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC')),
      'count' => (!empty($count)) ? current($count) : '',
      'limit' => 3
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

    // We check whether the email exists
    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . mysql_real_escape_string($mail) . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    if(is_array($users))
      die(json_encode(array('success' => false, 'msg' => 'This mail already exists !')));

    // We can now insert the new user
    $pwd = crypt($pwd, FWK_HASH);
    $dbError = array('error' => true, 'msg' => 'Database problem !');

    if(false === $db->query(
      'INSERT INTO lpcms_user (`mail`, `pwd`, `pseudo`, `role_id`) VALUES (\'' . mysql_real_escape_string($mail) . '\', \'' . mysql_real_escape_string($pwd) . '\', \'' . mysql_real_escape_string($pseudo) . '\', ' . intval($role) . ');'
    ))
      die(json_encode($dbError));

    // array('success' => true, 'msg' => 'User created.', 'pwd' => $pwd, 'id' => $id));
    echo '{"success":true, "msg":"User added.", "pwd":"' . $pwd . '", "id":"' . $db->lastInsertedId() . '"}';

    return;
  }

  public function editAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['id_user'], $_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role'], $_POST['oldMail']) || 6 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    // We check whether the email exists
    $dbUsers = $db->query(
      'SELECT mail FROM lpcms_user
       WHERE mail = \'' . mysql_real_escape_string($mail) . '\' LIMIT 1'
    );
    $users = $db->values($dbUsers);
    $db->freeResult($dbUsers);

    if(is_array($users) && $oldMail != $users[0]['mail'])
      exit('{"success":false,"msg":"This mail already exists !"}');

    // We can now update the user
    $pwd = crypt($pwd, FWK_HASH);

    if(false === $db->query(
      'UPDATE lpcms_user SET
      mail = \'' . mysql_real_escape_string($mail) . '\',
      pwd = \'' . mysql_real_escape_string($pwd) . '\',
      pseudo = \'' . mysql_real_escape_string($pseudo) . '\' WHERE id_user = ' . intval($id_user)))
      die('{"success":false,"msg":"Database problem !"}');

    // and his role
    if(false === $db->query(
      'UPDATE lpcms_user_role SET
      fk_id_role = ' . intval($role) . '
      WHERE fk_id_user = ' . intval($id_user)))
      die('{"success":false,"msg":"Database problem !"}');

    echo '{"success":true,"oldMail":' . $_POST['oldMail'] . ',"msg":"User edited.","pwd","' . $pwd . '"}';

    return;
  }

  public function deleteAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['id_user']) || 1 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    // if(false === $db->query(
    //   'DELETE FROM lpcms_mailing_list_user WHERE fk_id_user = ' . intval($id_user)))
    //   echo '{"success":false,"msg":"Database problem !"}';return;

    // if(false === $db->query(
    //   'DELETE FROM lpcms_user_role WHERE fk_id_user = ' . intval($id_user)))
    //   echo '{"success":false,"msg":"Database problem !"}';return;

    if(false === $db->query(
      'DELETE FROM lpcms_user WHERE `id_user` = ' . intval($id_user)))
      echo '{"success":false,"msg":"Database problem !"}';return;

    echo '{"success":true,"msg":"User deleted."}';return;
  }

  public function searchAction()
  {
    if(!isset($_SESSION['sid']['role']) || 1 !== $_SESSION['sid']['role'])
      die('Deconnected or lack of rights.');

    if(! isset($_POST['type'], $_POST['mail'], $_POST['pseudo'], $_POST['role'], $_POST['limit'], $_POST['prev'], $_POST['last']) || 7 < count($_POST))  // TODO ip to ban
      die('Hack.');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $limit = intval($limit);
    $req = 'SELECT u.id_user, u.mail, u.pwd, u.pseudo, r.id_role, r.nom FROM lpcms_user u
      INNER JOIN lpcms_user_role ur ON ur.fk_id_user = u.id_user
      INNER JOIN lpcms_role r ON ur.fk_id_role = r.id_role
      WHERE id_user ';

    if('search' == $type)
      $req .= '> ' . (intval($last) - $limit);
    else
      $req .= ('next' == $type)
        ? '> ' . intval($last)
        : '< ' . intval($prev);

    if('' != $mail)
      $req .= ' AND u.mail LIKE \'%' . mysql_real_escape_string($mail) . '%\'';

    if('' != $pseudo)
      $req .= ' AND u.pseudo LIKE \'%' . mysql_real_escape_string($pseudo) . '%\'';

    if('' != $role)
      $req .= ' AND r.nom LIKE \'%' . mysql_real_escape_string($role) . '%\'';

    if(false === ($users = $db->query(
      $req . ' ORDER BY u.id_user ' .
      (('next' == $type) ? 'LIMIT ' : 'DESC LIMIT ') . $limit
    ))) {
      echo('{"success":false,"msg":"Database problem !"}');return;
    }

    if(!empty($users)) {
      $users = $db->values($users);
      sort($users);

      // Fixes the bug where there is only one user
      if(isset($users['id_user']))
        $users = array($users);

      end($users); $last = current($users); reset($users);
      echo '{"success":true,"msg":' . json_encode($this->renderView('search.phtml', array('users' => $users), true)) . ',"first":' . $users[0]['id_user'] . ',"last":' . $last['id_user'] . '}';
    } else
      echo '{"success":true,"msg":""}';

    return;
  }
}
?>
