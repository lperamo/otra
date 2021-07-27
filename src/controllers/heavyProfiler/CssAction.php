<?php
declare(strict_types=1);

namespace otra\controllers\heavyProfiler;

use otra\{Controller, OtraException, services\ProfilerService};

/**
 * Class CssAction
 *
 * @author  Lionel Péramo
 * @package otra\controllers\heavyProfiler
 */
class CssAction extends Controller
{
  /**
   * @param array $otraParams
   * @param array $params
   *
   * @throws OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    ProfilerService::securityCheck();
    echo $this->renderView('sass/index.phtml', ['route' => $this->route]);
  }
}
