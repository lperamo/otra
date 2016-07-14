<?
/**
 * LPCMS - Backend - AjaxUsers - Edit
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxUsers;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\User, services\backendService, services\usersService};

class editAction extends Controller
{
  /** POST[id_user, mail, pwd, pseudo, role, oldMail] */
  public function editAction()
  {
    backendService::checkConnection($this->action);
    usersService::securityCheck();

    // TODO ip to ban
    if (false === isset($_POST['id_user'], $_POST['mail'], $_POST['pwd'], $_POST['pseudo'], $_POST['role'], $_POST['oldMail'], $_POST['oldPseudo']) || 7 < count($_POST))
    {
      echo '{"success": false, "msg": "Hack."}';
      return;
    }

    /**
     * Will be extracted ...
     *
     * @var $id_user
     * @var $mail
     * @var $oldMail
     * @var $oldPseudo
     * @var $pseudo
     * @var $pwd
     * @var $role
     */
    extract($_POST);
    Sql::getDB();
    $mail = Sql::$instance->quote($mail);
    $pseudo = Sql::$instance->quote($pseudo);

    User::checkMailEdit($mail, $oldMail) && exit('{"success": false, "msg": "This mail already exists !"}');
    User::checkPseudoEdit($pseudo, $oldPseudo) && exit('{"success": false, "msg": "This pseudo already exists !"}');

    // We can now update the user
    $pwd = crypt($pwd, FWK_HASH);

    echo (false === User::updateUser($id_user, $mail, $pwd, $pseudo, $role))
      ? '{"success": false, "msg": "Database problem !"}'
      : '{"success":true, "oldMail": "' . $oldMail . '", "msg": "User edited.","pwd": "' . $pwd . '"}';

    return;
  }
}
?>
