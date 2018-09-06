<?
/**
 * LPCMS - Backend - AjaxUsers - Delete
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxUsers;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\User, services\BackendService, services\UsersService};

class deleteAction extends Controller
{
  public function deleteAction()
  {
    BackendService::checkConnection($this->action);
    UsersService::securityCheck();

    // TODO ip to ban
    if (false === isset($_POST['id_user']) || 1 < count($_POST))
    {
      echo '{"success": false, "msg": "Hack."}';
      return;
    }

    Sql::getDB();

    echo (false === User::delete($_POST['id_user']))
      ? '{"success":false,"msg":"Database problem !"}'
      : '{"success":true, "msg": "User deleted.", "count": ' . User::count() . '}';

    return;
  }
}
?>
