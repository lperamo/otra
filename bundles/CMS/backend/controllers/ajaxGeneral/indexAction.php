<?
/**
 * LPCMS - Backend - AjaxGeneral - Index
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxGeneral;

use lib\myLibs\Controller,
  bundles\CMS\services\backendService;

class indexAction extends Controller
{
  public function indexAction()
  {
    backendService::checkConnection($this->action);
    echo $this->renderView('index.phtml', \bundles\CMS\services\configService::getConfigTab(), true);
  }
}
?>
