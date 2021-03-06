<?php
declare(strict_types=1);

namespace bundles\HelloWorld\frontend\controllers\index;

use otra\Controller;

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
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    echo $this->renderView('home.phtml', []);
  }
}

