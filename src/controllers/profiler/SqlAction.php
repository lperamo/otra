<?php
declare(strict_types=1);

namespace otra\controllers\profiler;

use otra\{Controller, OtraException, services\ProfilerService};
use const otra\cache\php\APP_ENV;
use const otra\cache\php\{BASE_PATH, CORE_PATH};

/**
 * Class CssAction
 *
 * @author  Lionel Péramo
 * @package otra\controllers\profiler
 */
class SqlAction extends Controller
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
    require CORE_PATH . 'tools/translate.php';
    ProfilerService::securityCheck();
    echo $this->renderView(
      'sql/index.phtml',
      [
        'route' => $this->route,
        'sqlLogs' => ProfilerService::getLogs(BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt')
      ]
    );
  }
}
