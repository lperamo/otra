<?
/**
 * LPCMS - Backend - Index - Modules
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\GlobalConstants, models\Module, services\BackendService};

class modulesAction extends Controller
{
  public function modulesAction()
  {
    if (BackendService::checkConnection($this->route) === false)
      return false;

    Sql::getDB();

    echo $this->renderView('modules.phtml', [
      'moduleTypes' => Module::$moduleTypes,
      'right' => GlobalConstants::$rights,
      'items' => Module::getAll()
    ]);
  }
}
?>