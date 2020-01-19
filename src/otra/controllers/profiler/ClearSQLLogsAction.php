<?php
/**
 * LPFramework - Core - Profiler - ClearSQLLogs
 *
 * @author Lionel Péramo */

namespace lib\otra\controllers\profiler;

use lib\otra\{Controller, services\ProfilerService};

class ClearSQLLogsAction extends Controller
{
  public function clearSQLLogsAction()
  {
    ProfilerService::securityCheck();
    $file = BASE_PATH . 'logs/' . $_SERVER['APP_ENV'] . '/sql.txt';
    $handle = fopen($file, 'r+');
    ftruncate($handle, 0);
    fclose($handle);

    echo t('No more stored queries in '), $file, '.';
  }
}
?>
