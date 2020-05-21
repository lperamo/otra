<?php
/**
 * LPFramework - Core - Profiler - Index
 *
 * @author Lionel Péramo */

namespace otra\controllers\profiler;

use otra\{Controller, services\ProfilerService};

class IndexAction extends Controller
{
  /**
   * @param array $baseParams
   * @param array $getParams
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
    ProfilerService::securityCheck();
    require CORE_PATH . 'tools/translate.php';

    echo $this->renderView(
      'profiler.phtml',
      ['sqlLogs' => ProfilerService::getLogs(BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt')]
    );
  }
}
?>
