<?
/** LPCMS - Backend - AjaxModules - GetElements
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace bundles\CMS\backend\controllers\ajaxModules;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\services\backendService;

class getElementsAction extends Controller
{
  public function getElementsAction()
  {
    backendService::checkConnection($this->action);
    $db = Sql::getDB();

    $element = $db->values($db->query('SELECT id, fk_id_module, fk_id_article, parent, aEnfants, droit, ordre, contenu
      FROM lpcms_elements_menu WHERE fk_id_module = ' . intval($_GET['id'])));
  }
}
?>
