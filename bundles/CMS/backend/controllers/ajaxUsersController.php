<?
/**
* Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router,
    bundles\CMS\models\User;

class ajaxUsersController extends Controller
{
  public function preExecute()
  {
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  /** Called when we click on tab 'users', if it's not already loaded */
  public function indexAction()
  {
    $db = Session::get('dbConn');
    $db->selectDb();
    $limit = 3;

    $users = User::getFirstUsers($db, $limit);

    // Fixes the bug where there is only one user
    if(isset($users['id_user']))
      $users = [$users];

    $count = $db->single($db->query('SELECT COUNT(id_user) FROM lpcms_user'));

    echo $this->renderView('index.phtml', [
      'users' => $users,
      'roles' => $db->values($db->query('SELECT id_role, nom FROM lpcms_role ORDER BY nom ASC')),
      'count' => (!empty($count)) ? current($count) : '',
      'limit' => $limit
    ], true);
  }

  public static function securityCheck($params)
  {
    if(!isset($_SESSION['sid']['role']))
      die('{"success": false, "msg": "Deconnected"}');

    if('1' !== $_SESSION['sid']['role'])
      die('{"success": false, "msg": "Lack of rights."}');
  }

  public function addAction()
  {
    self::securityCheck();

    if(! isset($_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role']) || 4 < count($_POST))  // TODO ip to ban
      die('{"success": false, "msg": "Hack."}');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    User::checkMailAdd($db, $mail) && exit('{"success": false, "msg": "This mail already exists !"}');
    User::checkPseudo($pseudo) && exit('{"success": false, "msg": "This pseudo already exists !"}');

    // We can now insert the new user
    $pwd = crypt($pwd, FWK_HASH);

    if(false === User::addUser($mail, $pwd, $pseudo, $role))
      die('{"error": true, "msg": "Database problem"}');

    echo '{"success":true, "msg":"User added.", "pwd":"' . $pwd . '", "id":"' . $db->lastInsertedId() . '"}';

    return;
  }

  /** POST[id_user, mail, pwd, pseudo, role, oldMail] */
  public function editAction()
  {
    self::securityCheck();

    if(! isset($_POST['id_user'], $_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role'], $_POST['oldMail']) || 6 < count($_POST))  // TODO ip to ban
      die('{"success": false, "msg": "Hack."}');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    $mail = mysql_real_escape_string($mail);
    $pseudo = mysql_real_escape_string($pseudo);

    User::checkMailEdit($db, $mail, $oldMail) && exit('{"success": false, "msg": "This mail already exists !"}');
    User::checkPseudo($db, $pseudo) && exit('{"success": false, "msg": "This pseudo already exists !"}');

    // We can now update the user
    $pwd = crypt($pwd, FWK_HASH);

    false === User::updateUser($db, $id_user, $mail, $pwd, $pseudo, $role) && die('{"success": false, "msg": "Database problem !"}');

    echo '{"success":true, "oldMail": "' . $_POST['oldMail'] . '", "msg": "User edited.","pwd": "' . $pwd . '"}';

    return;
  }

  public function deleteAction()
  {
    self::securityCheck();

    if(! isset($_POST['id_user']) || 1 < count($_POST))  // TODO ip to ban
      die('{"success": false, "msg": "Hack."}');

    extract($_POST);
    $db = Session::get('dbConn');
    $db->selectDb();

    if(false === $db->query(
      'DELETE FROM lpcms_user WHERE `id_user` = ' . intval($id_user)))
      echo '{"success":false,"msg":"Database problem !"}';return;

    echo '{"success":true, "msg": "User deleted."}';return;
  }

  public function searchAction()
  {
    self::securityCheck();

    if(! isset($_POST['type'], $_POST['mail'], $_POST['pseudo'], $_POST['role'], $_POST['limit'], $_POST['prev'], $_POST['last']) || 7 < count($_POST))  // TODO ip to ban
      die('{"success": false, "msg": "Hack."}');

    $db = Session::get('dbConn');
    $db->selectDb();

    $users = User::search($db, $_POST);

    if(!empty($users))
    {
      $users = $db->values($users);
      sort($users);

      // Fixes the bug where there is only one user
      if(isset($users['id_user']))
        $users = [$users];

      end($users); $last = current($users); reset($users);
      echo '{"success": true, "msg":' . json_encode($this->renderView('singleUser.phtml', ['users' => $users], true)) . ', "first":' . $users[0]['id_user'] . ', "last":' . $last['id_user'] . '}';
    } else
      echo '{"success": true, "msg": ""}';

    return;
  }
}
?>
