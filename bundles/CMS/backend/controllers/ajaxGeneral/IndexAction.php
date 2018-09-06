<?
/**
 * LPCMS - Backend - AjaxGeneral - Index
 *
 * @author Lionel Péramo
 */

namespace bundles\CMS\backend\controllers\ajaxGeneral;

use lib\myLibs\Controller,
  bundles\CMS\services\BackendService;

class indexAction extends Controller
{
  public function indexAction()
  {
    BackendService::checkConnection($this->action);
    echo $this->renderView('index.phtml', \bundles\CMS\services\ConfigService::getConfigTab(), true);
  }
}
?>
