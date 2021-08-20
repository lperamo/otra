<?php
declare(strict_types=1);

namespace otra\controllers\profiler;

use otra\{Controller, OtraException, services\ProfilerService};

/**
 * @author  Lionel Péramo
 * @package otra\controllers\profiler
 */
class RequestsAction extends Controller
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
    echo $this->renderView('requests/index.phtml', ['route' => $this->route]);
  }
}
