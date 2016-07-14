<?
/**
 * LPCMS - Backend - AjaxUsers - Index
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxUsers;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\User, services\backendService, services\usersService};

class addAction extends Controller
{
  public function addAction()
  {
    backendService::checkConnection($this->action);
    usersService::securityCheck();

    // TODO ip to ban
    if (false === isset($_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role']) || 4 < count($_POST))
    {
      echo '{"success": false, "msg": "Hack."}';
      return;
    }

    /**
     * Will be extracted ...
     *
     * @var $mail
     * @var $pseudo
     * @var $pwd
     * @var $role
     */
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
}
?>
