<?php
declare(strict_types=1);

namespace otra\controllers\profiler;

use otra\{Controller, OtraException, services\ProfilerService};

/**
 * @author  Lionel Péramo
 * @package otra\controllers\profiler
 */
class RoutesAction extends Controller
{
  /**
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css, internalRedirect]
   * @param array $params     [...getParams, ...postParams, etc.]
   *
   * @throws OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    ProfilerService::securityCheck();
    echo $this->renderView('routes/index.phtml', ['route' => $this->route]);
  }
}
