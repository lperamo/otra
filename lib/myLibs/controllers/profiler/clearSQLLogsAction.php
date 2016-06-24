<?
/**
 * LPFramework - Core - Profiler - ClearSQLLogs
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers;

use lib\myLibs\{Controller, services\profilerService};

class clearSQLLogsAction extends Controller
{
  public function clearSQLLogsAction()
  {
    profilerService::securityCheck();
    $file = BASE_PATH . 'logs/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    echo 'No more stored queries in ', $file, '.';
  }
}
?>
