<?php
declare(strict_types=1);
namespace otra\controllers\profiler;

use otra\
{Controller, OtraException, services\ProfilerService};

/**
 * @author Lionel Péramo
 * @package otra\controllers\profiler
 */
class IndexAction extends Controller
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
    require CORE_PATH . 'tools/translate.php';

    echo $this->renderView(
      'profiler.phtml',
      ['sqlLogs' => ProfilerService::getLogs(BASE_PATH . 'logs/' . $_SERVER[APP_ENV] . '/sql.txt')]
    );
  }
}

