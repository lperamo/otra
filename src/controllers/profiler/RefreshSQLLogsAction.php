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
    ProfilerService::writeLogs(BASE_PATH . 'logs/' . $_SERVER['APP_ENV'] . '/sql.txt');
  }
}
?>
