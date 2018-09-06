<?
/**
 * LPCMS - Backend - Index - Users
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\Controller;
use bundles\CMS\services\{UsersService, BackendService};

class usersAction extends Controller
{
  public function usersAction()
  {
    if (BackendService::checkConnection($this->route) === false)
      return false;

    echo $this->renderView('users.phtml', UsersService::getUsersTab());
  }
}
?>
