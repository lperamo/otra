<?
/**
 * LPFramework - Core - Profiler - ClearSQLLogs
 *
 * @author Lionel Péramo */

namespace lib\myLibs\controllers\profiler;

use lib\myLibs\{Controller, services\profilerService};

class clearSQLLogsAction extends Controller
{
  public function clearSQLLogsAction()
  {
    profilerService::securityCheck();
    $file = BASE_PATH . 'logs/' . XMODE . '/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    echo t('No more stored queries in '), $file, '.';
  }
}
?>
