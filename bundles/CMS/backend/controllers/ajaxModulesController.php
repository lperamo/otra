<?
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */
declare(strict_types=1);
namespace bundles\CMS\backend\controllers;

use lib\myLibs\{Controller, bdd\Sql, Session, Router};
use bundles\CMS\models\Module;

class ajaxModulesController extends Controller
{
  public static $moduleTypes = [
    0 => 'Connection',
    1 => 'Vertical menu',
    2 => 'Horizontal menu',
    3 => 'Article',
    4 => 'Arbitrary'
  ], $rights = [
    0 => 'Admin',
    1 => 'Saved',
    2 => 'Public'
  ];

  public function preExecute()
  {
    if($this->action !== 'index' && false === isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction()
  {
    Sql::getDB();

    $modules = Module::getAll();

    echo $this->renderView('modules.phtml', [
      'moduleTypes' => self::$moduleTypes,
      'right' => self::$rights,
      'items' => $modules
    ], true);
  }

  public function searchModuleAction()
  {
    $db = Sql::getDB();

    echo $this->renderView('modulesPartial.phtml', [
      'moduleTypes' => self::$moduleTypes,
      'right' => self::$rights,
      'items' => $db->values($db->query('
        SELECT id, type, position, ordre, droit, contenu
        FROM lpcms_module WHERE contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''))
    ], true);
  }

  public function searchElementAction()
  {
    $db = Sql::getDB();

    //var_dump($db->values($db->query('
    //    SELECT em.id, em.parent, em.aEnfants, em.droit, em.contenu
    //          bem.ordre
    //    FROM lpcms_elements_menu em
    //    INNER JOIN lpcms_bind_em_module bem ON bem.fk_id_elements_menu = em.id
    //    WHERE em.contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\'')));

    echo $this->renderView('elements.phtml', [
      'right' => self::$rights,
      'moduleList' => $db->values($db->query('SELECT id, contenu FROM lpcms_module')), // utile ?
      'items' => $db->values($db->query('
        SELECT em.id, em.parent, em.aEnfants, em.droit, em.contenu,
               bem.order
        FROM lpcms_elements_menu em
        INNER JOIN lpcms_bind_em_module bem ON bem.fk_id_elements_menu = em.id
        WHERE em.contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''))
    ], true);
  }

  public function searchArticleAction()
  {
    $db = Sql::getDB();

    $article = $db->values($db->query('SELECT id, titre, contenu, droit, date_creation, cree_par, derniere_modif, der_modif_par, derniere_visualisation, der_visualise_par, nb_vu, date_publication, meta, rank_sum, rank_count
     FROM lpcms_article WHERE contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''));

    echo $this->renderView('articles.phtml', [
      'right' => self::$rights,
      // 'moduleList' => $db->values($db->query('SELECT id, contenu FROM lpcms_module')),
      'items' => $db->values($db->query('
        SELECT em.id, parent, aEnfants, droit, contenu,
               bem.order
        FROM lpcms_elements_menu em
        INNER JOIN lpcms_bind_em_module bem ON bem.fk_id_elements_menu = em.id
        WHERE contenu LIKE \'%' . Sql::$instance->quote($_GET['search']). '%\''))
    ], true);
  }

  public function getElementsAction()
  {
    $db = Sql::getDB();

    $element = $db->values($db->query('SELECT id_elementsmenu, fk_id_module, fk_id_article, parent, aEnfants, droit, ordre, contenu
      FROM lpcms_elements_menu WHERE fk_id_module = ' . intval($_GET['id'])));
  }
}
?>
