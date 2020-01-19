<?php
/**
 * LPFramework - Core - Profiler - RefreshSQLLogs
 *
 * @author Lionel Péramo */

namespace lib\otra\controllers\profiler;

use lib\otra\{Controller, services\ProfilerService};

class RefreshSQLLogsAction extends Controller
{
  public function refreshSQLLogsAction()
  {
    ProfilerService::securityCheck();
    ProfilerService::writeLogs(BASE_PATH . 'logs/' . $_SERVER['APP_ENV'] . '/sql.txt');
  }
}
?>
