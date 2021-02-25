<?php
declare(strict_types=1);
namespace otra\controllers\profiler;

use otra\{Controller, services\ProfilerService};

/**
 * @author Lionel Péramo
 * @package otra\controllers\profiler
 */
class IndexAction extends Controller
{
  /**
   * @param array $baseParams
   * @param array $getParams
   *
   * @throws \otra\OtraException
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

