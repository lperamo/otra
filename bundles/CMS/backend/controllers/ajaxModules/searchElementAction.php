<?
/** LPCMS - Backend - AjaxModules - SearchElement
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace bundles\CMS\backend\controllers\ajaxModules;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{services\backendService, models\GlobalConstants};
//use bundles\CMS\models\Module;

class searchElementAction extends Controller
{
  public function searchElementAction()
  {
    backendService::checkConnection($this->action);
    $db = Sql::getDB();

    echo $this->renderView('elements.phtml', [
      'right' => GlobalConstants::$rights,
      'moduleList' => $db->values($db->query('SELECT id, contenu FROM lpcms_module')), // utile ?
      'items' => $db->values($db->query('
        SELECT em.id, em.parent, em.aEnfants, em.droit, em.contenu,
               bem.order
        FROM lpcms_elements_menu em
        INNER JOIN lpcms_bind_em_module bem ON bem.fk_id_elements_menu = em.id
        WHERE em.contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''))
    ], true);
  }
}
?>
