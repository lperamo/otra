<?php
/**
 * OTRA starter action
 */
namespace bundles\HelloWorld\frontend\controllers\index;

use otra\{Controller, MasterController};

class HomeAction extends Controller
{
  public function homeAction() {
    MasterController::$featurePolicy['dev']['sync-script'] = "'self'";
    echo $this->renderView('home.phtml', []);
  }
}
?>
