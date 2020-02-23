<?php
/**
 * LPFramework - Core - Profiler - RefreshSQLLogs
 *
 * @author Lionel Péramo */

namespace src\controllers\profiler;

use src\{Controller, services\ProfilerService};

class RefreshSQLLogsAction extends Controller
{
  public function refreshSQLLogsAction()
  {
    ProfilerService::securityCheck();
    require CORE_PATH . 'tools/translate.php';
    ProfilerService::writeLogs(BASE_PATH . 'logs/' . $_SERVER['APP_ENV'] . '/sql.txt');
  }
}
?>
