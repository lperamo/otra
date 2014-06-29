<?php
/** Backend of the LPCMS
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\core\Controller,
    lib\myLibs\core\bdd\Sql,
    lib\myLibs\core\Session,
    config\Router;

class ajaxModulesController extends Controller
{
  public static $moduleTypes = array(
    0 => 'Connection',
    1 => 'Vertical menu',
    2 => 'Horizontal menu',
    3 => 'Article',
    4 => 'Arbitrary'
  );

  public static $rights = array(
    0 => 'Admin',
    1 => 'Saved',
    2 => 'Public'
  );

  public function preExecute(){
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    $modules = $db->values($db->query('SELECT * FROM lpcms_module'));

    echo $this->renderView('modules.phtml', array(
      'moduleTypes' => self::$moduleTypes,
      'right' => self::$rights,
      'items' => $modules
    ), true);
  }

  public function searchModuleAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    echo $this->renderView('modules.phtml', array(
      'moduleTypes' => self::$moduleTypes,
      'right' => self::$rights,
      'items' => $db->values($db->query('
        SELECT id, type, position, ordre, droit, contenu
        FROM lpcms_module WHERE contenu LIKE \'%' . mysql_real_escape_string($_GET['search']). '%\''))
    ), true);
  }

  public function searchElementAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    echo $this->renderView('elements.phtml', array(
      'right' => self::$rights,
      'moduleList' => $db->values($db->query('SELECT id, contenu FROM lpcms_module')),
      'items' => $db->values($db->query('
        SELECT id, parent, aEnfants, droit, contenu
        FROM lpcms_elements_menu
        WHERE contenu LIKE \'%' . mysql_real_escape_string($_GET['search']). '%\''))
    ), true);
  }

  public function searchArticleAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    $article = $db->values($db->query('SELECT id, titre, contenu, droit, date_creation, cree_par, derniere_modif, der_modif_par, derniere_visualisation, der_visualise_par, nb_vu, date_publication, meta, rank_sum, rank_count
     FROM lpcms_article WHERE contenu LIKE \'%' . mysql_real_escape_string($_GET['search']). '%\''));
    // var_dump($article);die;
  }

  public function getElementsAction(){
    $db = Session::get('dbConn');
    $db->selectDb();

    // $element = $db->values($db->query('SELECT id_elementsmenu, fk_id_module, fk_id_article, parent, aEnfants, droit, ordre, contenu
    //  FROM lpcms_elements_menu WHERE fk_id_module = ' . intval($_GET['id'])));
  }
}
?>
