<?
/** LPCMS - Backend - AjaxModules - SearchModule
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace bundles\CMS\backend\controllers\ajaxModules;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{models\Module, services\backendService, models\GlobalConstants};

class searchModuleAction extends Controller
{
  public function searchModuleAction()
  {
    backendService::checkConnection($this->action);
    $db = Sql::getDB();

    echo $this->renderView('modulesPartial.phtml', [
      'moduleTypes' => Module::$moduleTypes,
      'right' => GlobalConstants::$rights,
      'items' => $db->values($db->query('
        SELECT id, type, position, ordre, droit, contenu
        FROM lpcms_module WHERE contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''))
    ], true);
  }
}
?>
