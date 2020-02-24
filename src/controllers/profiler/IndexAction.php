<?php
/**
 * LPFramework - Core - Profiler - Index
 *
 * @author Lionel Péramo */

namespace src\controllers\profiler;

use src\{Controller, services\ProfilerService};

class IndexAction extends Controller
{
  public function indexAction()
  {
    ProfilerService::securityCheck();
    require CORE_PATH . 'tools/translate.php';

    echo $this->renderView(
      'profiler.phtml',
      ['sqlLogs' => ProfilerService::getLogs(BASE_PATH . 'logs/' . $_SERVER['APP_ENV'] . '/sql.txt')]
    );
  }
}
?>
