<?
/** LPCMS - Backend - AjaxStats - Index
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\services\backendService;

class indexAction extends Controller
{
  public function indexAction()
  {
    backendService::checkConnection($this->action);
    Sql::getDB();

    echo $this->renderView(
      'index.phtml', [
        'items' => []
      ],
      true
    );
  }
}
?>
