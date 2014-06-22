<?php
/** LPCMS Articles management
 *
 * @author Lionel Péramo */
namespace bundles\CMS\frontend\controllers;

use lib\myLibs\core\Controller,
		lib\myLibs\core\bdd\Sql,
		lib\myLibs\core\Session,
		config\Router;

class articleController extends Controller
{
  /** Shows an article as main content.
   *
   * @param int $article Article's id
   */
  public function showAction($article = 'article2')
  {
    if(!$this->checkCache(array('show.phtml')))
    {
      //    $db = new Sql();
      /* @var $db \lib\myLibs\core\bdd\Sql */

      /* @var $db Sql */
      $db = Session::get('dbConn');
      $db->selectDb();

      // Puts the UTF-8 encoding in order to correctly render accents
      $db->query('SET NAMES UTF8');

      // Retrieving the headers
      if(!isset($_SESSION['headers']))
        $_SESSION['headers'] = $db->values($db->query('SELECT * FROM lpcms_header'));

      // Retrieving the footers
      if (!isset($_SESSION['footers']))
        $_SESSION['footers'] = $db->values($db->query('SELECT * FROM lpcms_footer'));

      // Retrieving the modules content
      $query = $db->query('SELECT
          m.id, type, position, m.ordre as m_ordre, m.droit as m_droit, m.contenu as m_contenu,
          bemm.fk_id_module as em_fk_id_module, bemm.ordre as em_ordre,
          a.id, titre, a.contenu as a_contenu, a.droit as a_droit, date_creation, cree_par,
            derniere_modif, der_modif_par, derniere_visualisation, der_visualise_par, nb_vu, date_publication,
            meta, rank_sum, rank_count,
          em.id, fk_id_article, parent, aEnfants, em.droit as em_droit,
            em.contenu as em_contenu
        FROM
          lpcms_module m
          LEFT OUTER JOIN lpcms_bind_em_module bemm ON m.id = bemm.fk_id_module
          LEFT OUTER JOIN lpcms_elements_menu em ON bemm.fk_id_elements_menu = em.id
          LEFT OUTER JOIN lpcms_article a ON em.fk_id_article = a.id
        ORDER BY m.position, m.ordre, em_ordre
        ');
      $result = $db->values($query);
      unset ($query);

      $modules = array();
      foreach($result as $module)
      {
        $position = $module['position'];
        $typeModule = $module['type'];
        $mContenu = $module['m_contenu'];

        unset($module['position'], $module['type'], $module['m_contenu']);

        $modules[$position][$typeModule][$mContenu][] = $module;
      }

      if(file_exists($article = BASE_PATH . 'bundles/' . $this->bundle . '/articles/' . $article . '.html'))
      {
        ob_start();
        require_once($article);
        $article = ob_get_clean();
      }else
        $article = 'Cet article n\'existe pas. Cet article n\'existe pas.Cet article n\'existe pas.Cet article n\'existe pas.Cet article n\'existe pas';

      echo $this->renderView('show.phtml', array(
        'headers' => $_SESSION['headers'],
        'footers' => $_SESSION['footers'],
        'modules' => $modules,
        'lpcms_body' => 'page_t.jpg',
        'article' => $article
      ));
    }else
      echo $this->renderView('show.phtml', array());
	}
}
