<?
/**
 * LPFramework - Core - Profiler - RefreshSQLLogs
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers\profiler;

use lib\myLibs\{Controller, services\ProfilerService};

class RefreshSQLLogsAction extends Controller
{
  public function refreshSQLLogsAction()
  {
    ProfilerService::securityCheck();
    ProfilerService::writeLogs(BASE_PATH . 'logs/' . XMODE . '/sql.txt');
  }
}
?>
