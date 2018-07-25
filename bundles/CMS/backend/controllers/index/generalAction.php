<?
/**
 * LPCMS - Backend - Index - General
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\Controller;
use bundles\CMS\services\{backendService, configService};

class generalAction extends Controller
{
  public function generalAction()
  {
    if (backendService::checkConnection($this->route) === false)
      return false;

    echo $this->renderView('general.phtml', configService::getConfigTab());
  }
}
?>
