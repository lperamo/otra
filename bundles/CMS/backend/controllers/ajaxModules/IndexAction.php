<?
/** LPCMS - Backend - AjaxModules - Index
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace bundles\CMS\backend\controllers\ajaxModules;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\Module, models\GlobalConstants, services\BackendService};

class indexAction extends Controller
{
  public function indexAction()
  {
    BackendService::checkConnection($this->action);
    Sql::getDB();

    $modules = Module::getAll();

    echo $this->renderView('modules.phtml', [
      'moduleTypes' => Module::$moduleTypes,
      'right' => GlobalConstants::$rights,
      'items' => $modules
    ], true);
  }
}
?>
