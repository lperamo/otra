<?php
declare(strict_types=1);

namespace bundles\HelloWorld\frontend\controllers\index;

use otra\{Controller, MasterController};

/**
 * OTRA starter action
 *
 * @package bundles\HelloWorld\frontend\controllers\index
 */
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

