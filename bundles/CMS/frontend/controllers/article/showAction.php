<?
/** LPCMS - Frontend - Article - Show
 *
 * @author Lionel Péramo */
namespace bundles\CMS\frontend\controllers\article;

use lib\myLibs\Controller,
  lib\myLibs\bdd\Sql;
//  lib\myLibs\Session,
use bundles\CMS\models\{Header, Footer};

class showAction extends Controller
{
  /** Shows an article as main content.
   *
   * @param string $article Article's id
   */
  public function showAction($article = 'article2')
  {
    if (false === $this->checkCache(['show.phtml']))
    {
      $db = Sql::getDB();

      if (false === isset($_SESSION['headers']))
        $_SESSION['headers'] = Header::getAll();

      if (false === isset($_SESSION['footers']))
        $_SESSION['footers'] = Footer::getAll();

      // Retrieving the modules content
      $query = $db->query('SELECT
          m.id, type, position, m.ordre as m_ordre, m.droit as m_droit, m.contenu as m_contenu,
          bemm.fk_id_module as em_fk_id_module, bemm.order as em_ordre,
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

      $modules = [];

      if (false === empty($result))
      {
        foreach($result as $module)
        {
          $position = $module['position'];
          $typeModule = $module['type'];
          $mContenu = $module['m_contenu'];

          unset($module['position'], $module['type'], $module['m_contenu']);

          $modules[$position][$typeModule][$mContenu][] = $module;
        }
      }

      if (true === file_exists($article = BASE_PATH . 'bundles/' . $this->bundle . '/articles/' . $article . '.html'))
      {
        ob_start();
        require($article);
        $article = ob_get_clean();
      } else
        $article = 'Cet article n\'existe pas. Cet article n\'existe pas.Cet article n\'existe pas.Cet article n\'existe pas.Cet article n\'existe pas';

      echo $this->renderView(
        'show.phtml',
        [
          'headers' => $_SESSION['headers'],
          'footers' => $_SESSION['footers'],
          'modules' => $modules,
          'lpcms_body' => 'page_t.jpg',
          'article' => $article
        ]);
    } else
      echo $this->renderView('show.phtml', []);
  }
}
?>
