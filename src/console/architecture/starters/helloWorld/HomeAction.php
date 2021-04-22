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
   * @param array $otraParams
   * @param array $params
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    echo $this->renderView('home.phtml', []);
  }
}

