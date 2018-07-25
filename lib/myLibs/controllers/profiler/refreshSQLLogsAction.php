<?
/**
 * LPFramework - Core - Profiler - RefreshSQLLogs
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers\profiler;

use lib\myLibs\{Controller, services\profilerService};

class refreshSQLLogsAction extends Controller
{
  public function refreshSQLLogsAction()
  {
    profilerService::securityCheck();
    profilerService::writeLogs(BASE_PATH . 'logs/' . XMODE . '/sql.txt');
  }
}
?>
