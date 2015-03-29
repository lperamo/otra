<?
/**
 * Backend of the LPCMS
 *
 * @author Lionel Péramo
 */

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
    echo $this->renderView('index.phtml', \bundles\CMS\services\usersService::getUsersTab(), true);
  }

  public static function securityCheck($params)
  {
    if(!isset($_SESSION['sid']['role']))
    {
      echo '{"success": false, "msg": "Deconnected"}';
      return ;
    }

    if('1' !== $_SESSION['sid']['role'])
    {
      echo '{"success": false, "msg": "Lack of rights."}';
      return;
    }
  }

  public function addAction()
  {
    self::securityCheck();

    if(! isset($_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role']) || 4 < count($_POST))  // TODO ip to ban
      die('{"success": false, "msg": "Hack."}');

    extract($_POST);
    $db = Sql::getDB();

    User::checkMail($mail) && exit('{"success": false, "msg": "This mail already exists !"}');
    User::checkPseudo($pseudo) && exit('{"success": false, "msg": "This pseudo already exists !"}');

    // We can now insert the new user
    $pwd = crypt($pwd, FWK_HASH);

    echo (false === User::addUser($mail, $pwd, $pseudo, $role))
      ? '{"error": true, "msg": "Database problem"}'
      : '{"success":true, "msg":"User added.", "id":"' . $db->lastInsertedId() . '"}';

    return;
  }

  /** POST[id_user, mail, pwd, pseudo, role, oldMail] */
  public function editAction()
  {
    self::securityCheck();

    // TODO ip to ban
    if (! isset($_POST['id_user'], $_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role'], $_POST['oldMail'], $_POST['oldPseudo']) || 7 < count($_POST))
      die('{"success": false, "msg": "Hack."}');

    extract($_POST);
    $db = Sql::getDB();
    $mail = mysql_real_escape_string($mail);
    $pseudo = mysql_real_escape_string($pseudo);

    User::checkMailEdit($mail, $oldMail) && exit('{"success": false, "msg": "This mail already exists !"}');
    User::checkPseudoEdit($pseudo, $oldPseudo) && exit('{"success": false, "msg": "This pseudo already exists !"}');

    // We can now update the user
    $pwd = crypt($pwd, FWK_HASH);

    echo (false === User::updateUser($id_user, $mail, $pwd, $pseudo, $role))
      ? '{"success": false, "msg": "Database problem !"}'
      : '{"success":true, "oldMail": "' . $oldMail . '", "msg": "User edited.","pwd": "' . $pwd . '"}';

    return;
  }

  public function deleteAction()
  {
    self::securityCheck();

    // TODO ip to ban
    (!isset($_POST['id_user']) || 1 < count($_POST)) && die('{"success": false, "msg": "Hack."}');

    Sql::getDB();

    echo (false === User::delete($_POST['id_user']))
     ? '{"success":false,"msg":"Database problem !"}'
     : '{"success":true, "msg": "User deleted.", "count": ' . User::count() . '}';

    return;
  }

  /** A criteria based search or a previous/next pagination */
  public function searchAction()
  {
    self::securityCheck();

    if(! isset($_POST['type'], $_POST['mail'], $_POST['pseudo'], $_POST['role'], $_POST['limit'], $_POST['prev'], $_POST['last']) || 7 < count($_POST))  // TODO ip to ban
      die('{"success": false, "msg": "Hack."}');

    $db = Sql::getDB();

    $data = User::search($_POST);
    $users = (isset($data[0])) ? $data[0] : $data;

    if(!empty($users))
    {
      $users = $db->values($users);
      sort($users);

      if(empty($users))
        echo '{"success": true, "msg": "", "count": 0, "first": 0, "last":0}';
      else
      {
        end($users);
        $last = current($users);
        reset($users);

        echo '{"success": true, "msg":' . json_encode($this->renderView(
            'singleUser.phtml',
            ['users' => $users],
            true)
          ) . ', "count":' . (isset($data[1]) ? $data[1] : '-1') .
           ', "first":' . $users[0]['id_user'] .
           ', "last":' . $last['id_user'] . '}';
      }
    } else
      echo '{"success": true, "msg": ""}';

    return;
  }
}
?>
