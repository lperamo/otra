<?
/**
 * LPCMS - Backend - AjaxUsers - Index
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxUsers;

use lib\myLibs\Controller;
use bundles\CMS\services\{BackendService, UsersService};

class indexAction extends Controller
{
  /** Called when we click on tab 'users', if it's not already loaded */
  public function indexAction()
  {
    BackendService::checkConnection($this->action);
    echo $this->renderView('index.phtml', UsersService::getUsersTab(), true);
  }
}
?>
