<?php
declare(strict_types=1);

namespace otra\controllers\profiler;

use otra\{Controller, OtraException, services\ProfilerService};
use const otra\cache\php\{APP_ENV,BASE_PATH,CORE_PATH};

/**
 * @author Lionel Péramo
 * @package otra\controllers\profiler
 */
class RefreshSQLLogsAction extends Controller
{
  /**
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css]
   * @param array $params     [...getParams, ...postParams, etc.]
   *
   * @throws OtraException
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
    ProfilerService::securityCheck();
    require CORE_PATH . 'tools/translate.php';
    echo ProfilerService::getLogs(BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt');
  }
}
