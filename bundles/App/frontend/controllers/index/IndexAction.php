<?
/**
 * OTRA example page
 *
 * @author Lionel Péramo
 */
namespace bundles\App\frontend\controllers\index;

use lib\myLibs\Controller;

class IndexAction extends Controller
{
  /**
   * @throws \lib\myLibs\LionelException
   */
  public function indexAction() {
    echo $this->renderView('index.phtml', []);
  }
}
?>
