<?php
/**
 * OTRA starter action
 */
namespace bundles\HelloWorld\frontend\controllers\index;

use otra\{Controller, MasterController};

class HomeAction extends Controller
{
  /**
   * HomeAction constructor.
   *
   * @param array $baseParams
   * @param array $getParams
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    MasterController::$featurePolicy['dev']['sync-script'] = "'self'";
    echo $this->renderView('home.phtml', []);
  }
}
?>
