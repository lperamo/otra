<?php
/**
 * OTRA starter action
 */
namespace bundles\HelloWorld\frontend\controllers\index;

use otra\{Controller, MasterController};

class HomeAction extends Controller
{
  /**
   * @throws \otra\OtraException
   */
  public function homeAction():void {
    MasterController::$featurePolicy['dev']['sync-script'] = "'self'";
    echo $this->renderView('home.phtml', []);
  }
}
?>
