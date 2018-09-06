<?
/** LPCMS - Backend - AjaxStats - Index
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\ajaxStats;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\services\BackendService;

class indexAction extends Controller
{
  public function indexAction()
  {
    BackendService::checkConnection($this->action);
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
