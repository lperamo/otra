<?
/**
 *  Backend of the LPCMS
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers;

use lib\myLibs\Controller,
    lib\myLibs\bdd\Sql,
    lib\myLibs\Session,
    config\Router;

class ajaxGeneralController extends Controller
{
  public function preExecute()
  {
    if($this->action != 'index' && !isset($_SESSION['sid']))
    {
      Router::get('backend');
      die;
    }
  }

  public function indexAction(){
    echo $this->renderView('index.phtml', \bundles\CMS\services\configService::getConfigTab(), true);
  }
}
?>
