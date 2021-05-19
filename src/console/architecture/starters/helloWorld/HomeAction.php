<?php
declare(strict_types=1);

namespace bundles\HelloWorld\frontend\controllers\index;

use otra\Controller;
use otra\OtraException;

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
   * @throws OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    echo $this->renderView('home.phtml', []);
  }
}

