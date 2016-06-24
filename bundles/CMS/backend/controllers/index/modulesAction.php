<?
/**
 * LPCMS - Backend - Index - Modules
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\{Controller, bdd\Sql};
use \bundles\CMS\{models\Module, services\backendService};

class modulesAction extends Controller
{
  public function modulesAction()
  {
    backendService::checkConnection($this->action);
    Sql::getDB();

    echo $this->renderView('modules.phtml', [
      'moduleTypes' => Module::$moduleTypes,
      'right' => Module::$rights,
      'items' => Module::getAll()
    ]);
  }
}
?>
