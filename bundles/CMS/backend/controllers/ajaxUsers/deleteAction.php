<?
/**
 * LPCMS - Backend - AjaxUsers - Delete
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxUsers;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\User, services\backendService, services\usersService};

class deleteAction extends Controller
{
  public function deleteAction()
  {
    backendService::checkConnection($this->action);
    usersService::securityCheck();

    // TODO ip to ban
    (false === isset($_POST['id_user']) || 1 < count($_POST)) && die('{"success": false, "msg": "Hack."}');

    Sql::getDB();

    echo (false === User::delete($_POST['id_user']))
      ? '{"success":false,"msg":"Database problem !"}'
      : '{"success":true, "msg": "User deleted.", "count": ' . User::count() . '}';

    return;
  }
}
?>
