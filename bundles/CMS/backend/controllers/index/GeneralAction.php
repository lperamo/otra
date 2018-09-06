<?
/**
 * LPCMS - Backend - Index - General
 *
 * @author Lionel Péramo */

namespace bundles\CMS\backend\controllers\index;

use lib\myLibs\Controller;
use bundles\CMS\services\{BackendService, ConfigService};

class generalAction extends Controller
{
  public function generalAction()
  {
    if (BackendService::checkConnection($this->route) === false)
      return false;

    echo $this->renderView('general.phtml', ConfigService::getConfigTab());
  }
}
?>
