<?
/** LPCMS AJAX Articles management
 *
 * @author Lionel Péramo */

namespace bundles\CMS\frontend\controllers;
use lib\myLibs\Controller;

class ajaxArticleController extends Controller
{
  public function showAction($article) {
    require_once(BASE_PATH . 'bundles/' . $this->bundle . '/articles/' . $article . '.html');
  }
}
?>
