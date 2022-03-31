<?php
declare(strict_types=1);

namespace otra\controllers\profiler;

use otra\{Controller, OtraException, services\ProfilerService, Session};

/**
 * Class TemplateStructureAction
 *
 * @author  Lionel Péramo
 * @package otra\controllers\profiler
 */
class TemplateStructureAction extends Controller
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
    Session::init();
    $templateVisualization = Session::getIfExists('templateVisualization');

    echo $this->renderView(
      'templateStructure/index.phtml',
      [
        'route' => $this->route,
        'templateVisualization' => $templateVisualization
      ]
    );
  }
}
