<?php
declare(strict_types=1);

/**
 * OTRA starter action
 */
namespace bundles\HelloWorld\frontend\controllers\index;

use otra\{Controller, MasterController};

/**
 * @package bundles\HelloWorld\frontend\controllers\index
 */
class HomeAction extends Controller
{
  /**
   * HomeAction constructor.
   *
   * @param array $baseParams
   * @param array $getParams
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    MasterController::$featurePolicy['dev']['sync-script'] = "'self'";
    echo $this->renderView('home.phtml', []);
  }
}

