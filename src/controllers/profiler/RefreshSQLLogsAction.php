<?php
declare(strict_types=1);
namespace otra\controllers\profiler;

use otra\{Controller, services\ProfilerService};

/**
 * @author Lionel Péramo
 * @package otra\controllers\profiler
 */
class RefreshSQLLogsAction extends Controller
{
  /**
   * @param array $otraParams
   * @param array $params
   *
   * @throws \otra\OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    ProfilerService::securityCheck();
    require CORE_PATH . 'tools/translate.php';
    echo ProfilerService::getLogs(BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt');
  }
}

