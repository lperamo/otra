<?
/**
 * LPCMS - Backend - Index - Users
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\Controller;
use bundles\CMS\services\{usersService, backendService};

class usersAction extends Controller
{
  public function usersAction()
  {
    if (backendService::checkConnection($this->route) === false)
      return false;

    echo $this->renderView('users.phtml', usersService::getUsersTab());
  }
}
?>
