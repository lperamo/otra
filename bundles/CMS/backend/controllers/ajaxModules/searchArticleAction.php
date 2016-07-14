<?
/** LPCMS - Backend - AjaxModules - SearchArticle
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace bundles\CMS\backend\controllers\ajaxModules;

use lib\myLibs\{Controller, bdd\Sql};
use bundles\CMS\{services\backendService, models\GlobalConstants};
//use bundles\CMS\models\Module;

class searchArticleAction extends Controller
{
  public function searchArticleAction()
  {
    backendService::checkConnection($this->action);
    $db = Sql::getDB();

    $article = $db->values($db->query('SELECT id, titre, contenu, droit, date_creation, cree_par, derniere_modif, der_modif_par, derniere_visualisation, der_visualise_par, nb_vu, date_publication, meta, rank_sum, rank_count
     FROM lpcms_article WHERE contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''));

    echo $this->renderView('articles.phtml', [
      'right' => GlobalConstants::$rights,
      // 'moduleList' => $db->values($db->query('SELECT id, contenu FROM lpcms_module')),
      'items' => $db->values($db->query('
        SELECT em.id, parent, aEnfants, droit, contenu,
               bem.order
        FROM lpcms_elements_menu em
        INNER JOIN lpcms_bind_em_module bem ON bem.fk_id_elements_menu = em.id
        WHERE contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''))
    ], true);
  }
}
?>
